<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Exports\FilteredResultsExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use MongoDB\Client;
use DateTime;
use Carbon\Carbon;

class AuditLogsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    
    public function index0(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        $date_range = explode(" - ", $request->input('auditDate'));
        $mrn = $request->input('mrn');
        $role = $request->input('role');
        if ($request->has('auditDate')) {
            $from = Carbon::createFromFormat('m/d/Y', $date_range[0])->format('Y-m-d');
            $to = Carbon::createFromFormat('m/d/Y', $date_range[1])->format('Y-m-d');
        } else {
            $from = now()->subDays(1)->toDateString();
            $to = now()->toDateString();
        }        

        $page = request()->get('page', 1);
        $perPage = 100;
        $skip = ($page - 1) * $perPage;
       
        // Count total matching documents (only $match stage used for count)
        $countPipeline = [
            [
                '$match' => [
                    'auditdate' => [
                        '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                        '$lt'  => new \MongoDB\BSON\UTCDateTime(strtotime("$to 00:00:00") * 1000)
                    ],
                ],
            ],
            [ '$count' => 'total' ],
        ];

        /* $totalResult = DB::collection('auditlogs')->raw(function ($collection) use ($countPipeline) {
            return $collection->aggregate($countPipeline, ['allowDiskUse' => true]);
        })->toArray(); */

        $totalResult = DB::connection('mongodb')
        ->collection('auditlogs')
        ->raw(function ($collection) use ($countPipeline) {
            return $collection->aggregate($countPipeline, ['allowDiskUse' => true]);
        })->toArray();
        
        $total = $totalResult[0]['total'] ?? 0;

        $pipeline = [
            // 1. Date filtering first
            [
                '$match' => [
                    'auditdate' => [
                        '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                        '$lt'  => new \MongoDB\BSON\UTCDateTime(strtotime("$to 00:00:00") * 1000)
                    ]
                ]
            ],
            // 2. Lookups and unwinds
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => 'useruid',
                    'foreignField' => '_id',
                    'as' => 'userDetails'
                ]
            ],
            [ '$unwind' => [ 'path' => '$userDetails', 'preserveNullAndEmptyArrays' => true ] ],
            [
                '$lookup' => [
                    'from' => 'patientvisits',
                    'localField' => 'patientvisituid',
                    'foreignField' => '_id',
                    'as' => 'visitDetails'
                ]
            ],
            [ '$unwind' => [ 'path' => '$visitDetails', 'preserveNullAndEmptyArrays' => true ] ],
            [
                '$lookup' => [
                    'from' => 'patients',
                    'localField' => 'visitDetails.patientuid',
                    'foreignField' => '_id',
                    'as' => 'patientDetails'
                ]
            ],
            [ '$unwind' => [ 'path' => '$patientDetails', 'preserveNullAndEmptyArrays' => true ] ],
        ];
        
        $matchConditions = [];

        if (!empty($mrn)) {
            $matchConditions['patientDetails.mrn'] = $mrn;
        }
        
        if (!empty($role)) {
            $matchConditions['userDetails.defaultrole.name'] = [
                '$regex' => $role,
                '$options' => 'i' // case-insensitive
            ];
        }
        
        if (!empty($matchConditions)) {
            $pipeline[] = [
                '$match' => $matchConditions
            ];
        }
        
        // 3. Project + Sort + Limit
        $pipeline[] = [
            '$project' => [
                '_id' => 0,
                'auditdate' => [
                    '$dateToString' => [
                        'format' => '%Y-%m-%d %H:%M:%S',
                        'date' => '$auditdate',
                        'timezone' => '+08:00'
                    ]
                ],
                'dataset' => 1,
                'printname' => '$userDetails.printname',
                'datasetcode' => 1,
                'patientvisituid' => 1,
                'visitno' => '$visitDetails.visitid',
                'role' => '$userDetails.defaultrole.name',
                'mrn' => '$patientDetails.mrn'
            ]
        ];
        
        $pipeline[] = [ '$sort' => [ 'auditdate' => -1 ] ];
        $pipeline[] = [ '$limit' => 100 ];
   

        ini_set('max_execution_time', 300); // Optional safeguard
        $results = DB::connection('mongodb')
        ->collection('auditlogs')
        ->raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
        })->toArray();
    
        $paginated = new LengthAwarePaginator(
            collect($results), // Items
            $total,            // Total count
            $perPage,          // Per page
            $page,             // Current page
            ['path' => request()->url(), 'query' => request()->query()] // For pagination links
        ); 

        return view('auditLogs', [
            'auditDate' => $request->input('auditDate'),
            'results' => $paginated,
        ]);
    }
    
    public function index(Request $request){
        return view('auditLogs', [
            'auditDate' => '',
            'results' => [],
        ]);
    }
   
    public function exportReport(Request $request)
    {    
        date_default_timezone_set('Asia/Manila');
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1G');

        $mrn = $request->input('mrn');
        $role = $request->input('role');
        $dateRange = $request->input('auditDate');
    
       // Initial match: only for auditdate and mrn
        $preMatch = [];

        if (!empty($mrn)) {
            $preMatch['patientDetails.mrn'] = $mrn;
        }

        if (!empty($dateRange)) {

            $date_range = explode(" - ", $dateRange);
            $from = str_replace('/', '-', trim($date_range[0]));
            $to = str_replace('/', '-', trim($date_range[1]));
        
            // Match the actual format: 'm-d-Y H:i:s'
            $fromDate = Carbon::createFromFormat('m-d-Y H:i:s', "$from 00:00:00");
            $toDate   = Carbon::createFromFormat('m-d-Y H:i:s', "$to 23:59:59");
        
            $preMatch['auditdate'] = [
                '$gte' => new \MongoDB\BSON\UTCDateTime($fromDate->toDateTimeImmutable()),
                '$lt'  => new \MongoDB\BSON\UTCDateTime($toDate->toDateTimeImmutable())
            ];
        }

        $pipeline = [];

        // First filter on auditdate and mrn
        if (!empty($preMatch)) {
            $pipeline[] = ['$match' => $preMatch];
        }

        // Look up user details
        $pipeline[] = [
            '$lookup' => [
                'from' => 'users',
                'localField' => 'useruid',
                'foreignField' => '_id',
                'as' => 'userDetails'
            ]
        ];

        $pipeline[] = [ '$unwind' => '$userDetails' ];

        if (!empty($role)) {
            // Match AFTER lookup
            $pipeline[] = [
                '$match' => [
                    'userDetails.defaultrole.name' => [
                        '$regex' => $role,
                        '$options' => 'i'
                    ]
                ]
            ];
        }

        
        // Look up user details
        $pipeline[] = [
            '$lookup' => [
                'from' => 'referencevalues',
                'localField' => 'userDetails.specialtyuid',
                'foreignField' => '_id',
                'as' => 'specialtyDetails'
            ]
        ];

        $pipeline[] = [ '$unwind' => '$specialtyDetails' ];


        // Then project all fields we need
        $pipeline[] = ['$project' => [
            '_id' => 0,
            'auditdate' => [
                '$dateToString' => [
                    'format' => '%Y-%m-%d %H:%M:%S',
                    'date' => '$auditdate',
                    'timezone' => '+08:00'
                ]
            ],
            'dataset' => 1,
            'printname' => '$userDetails.printname',
            'datasetcode' => 1,
            'patientvisituid' => 1,
            'visitno' => '$visitDetails.visitid',
            'role' => '$userDetails.defaultrole.name',  // Flatten to top-level for easier match
            'mrn' => '$patientDetails.mrn',
            //'specialty' => '$specialtyDetails.valuedescription',
            'specialty' => [
                '$ifNull' => ['$specialtyDetails.valuedescription', '']
            ]
        ]];

        $pipeline[] = ['$sort' => ['auditdate' => -1]];
        //$pipeline[] = ['$count' => 'total'];

        /* $cursor = DB::connection('mongodb')
            ->collection('auditlogs')
            ->raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline);
        }); */
        /* dd(
            DB::connection('mongodb')
                ->collection('auditlogs')
                ->orderBy('auditdate', 'desc')
                ->limit(1)
                ->first()
        ); */
        //dd(iterator_to_array($cursor));

        // Prepare download headers
        $filename = 'auditlogs_export_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
    
        // Define column headers
        $columns = ['Audit Date', 'Dataset', 'Print Name', 'Dataset Code', 'Patient Visit UID', 'Visit No', 'Role', 'MRN', 'Specialty'];
    
        // Stream export
        $callback = function () use ($pipeline, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns); // header row
    
            $cursor = DB::connection('mongodb')
                ->collection('auditlogs')
                ->raw(function ($collection) use ($pipeline) {
                    return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
                });
    
            foreach ($cursor as $row) {
                fputcsv($handle, [
                    $row['auditdate'] ?? '',
                    $row['dataset'] ?? '',
                    $row['printname'] ?? '',
                    $row['datasetcode'] ?? '',
                    $row['patientvisituid'] ?? '',
                    $row['visitno'] ?? '',
                    $row['role'] ?? '',
                    $row['mrn'] ?? '',
                    $row['specialty'] ?? '',
                ]);
            }
    
            fclose($handle);
        };
    
        return response()->stream($callback, 200, $headers);
    }

    
    public function export(Request $request)
    {
        $type = "";
        $department = "";

        if ($request->input('visitdate')!=null) {
            $date_range = explode(" - ", $request->input('visitdate'));
            $from = trim($date_range[0]);
            $to = trim($date_range[1]);
        } else {
            $from = now()->subDays(7)->toDateString();
            $to = now()->toDateString();
        }

        if ($request->input('type') != 'All' && $request->input('type') != null) {
            $type = "and po.entypedescription = '" . $request->input('type') . "'";
        }

        if ($request->input('department') != 'All' && $request->input('department') != null) {
            $department = "and po.ordertodepartmentname = '" . $request->input('department') . "'";
        }

        $results = $this->getFilteredQuery($from, $to, $type, $department);
        return Excel::download(new FilteredResultsExport($results), 'filtered-results.xlsx');

    }
    
    public function getFilteredQuery($from, $to, $type = null, $department = null)
    {
        return DB::connection('sqlsrv')->select("
            SELECT DISTINCT  
            p.mrn, v.startdate, po.orderdate, CONCAT(p.firstname,' ',p.lastname) as name, po.entypedescription as entype,
            po.ordertodepartmentname, pbo.orderitemname,
            pbo.quantity, pbo.unitprice, pbo.patientorderitems_id, pbo.statusdescription
            FROM patientorderitems pbo
            LEFT JOIN patientorders po ON pbo.patientorders_id = po.patientorders_id
            LEFT JOIN patients p ON p.patient_id = po.patient_id
            LEFT JOIN patientvisits v ON v.patientvisits_id = po.patientvisits_id
            WHERE pbo.org_id = '67b0ec656c4beff86dab216a'
            AND CONVERT(DATE, v.startdate) BETWEEN ? AND ?
            $type
            $department
            ORDER BY v.startdate ASC
        ", [$from, $to]);
    }

}