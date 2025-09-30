<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use DB;
use MongoDB\Client;
use DateTime;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class OECBController extends Controller
{
    /**
     * Safely convert mixed/BSON values to a scalar string for CSV.
     */
    private function toCsvValue($value)
    {
        if ($value === null) {
            return '';
        }
        // Handle ObjectId
        if ($value instanceof \MongoDB\BSON\ObjectId) {
            return (string) $value;
        }
        // Handle UTCDateTime
        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            try {
                return $value->toDateTime()->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                return '';
            }
        }
        // Handle BSONDocument/arrays by JSON encoding
        if ($value instanceof \MongoDB\Model\BSONDocument || is_array($value) || $value instanceof \MongoDB\Model\BSONArray) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        // Scalars / fallback
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string) $value;
    }
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
     * Show the OECB dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        
        // Get date range from request or default to last 7 days
        if ($request->has('dateRange')) {
            $date_range = explode(" - ", $request->input('dateRange'));
            $from = Carbon::createFromFormat('m/d/Y', $date_range[0])->format('Y-m-d');
            $to = Carbon::createFromFormat('m/d/Y', $date_range[1])->format('Y-m-d');
        } else {
            $from = now()->subDays(7)->toDateString();
            $to = now()->toDateString();
        }

        // Get other filters
        $billingGroup = $request->input('billingGroup', 'Medicines');
        $orgId = $request->input('orgId', '68afd805c1138ac88013a73c');
        $mrn = $request->input('mrn');// ? $request->input('mrn') : $request->input('vvvisitId');
        $orderItemCodeType = $request->input('orderItemCodeType', '68bcd135fd1274d28ea7b997');

        // If no MRN provided, show patient list
        if (empty($mrn)) {
            return $this->showPatientList($request, $from, $to, $billingGroup, $orgId,$mrn);
        }

        $page = request()->get('page', 1);
        $perPage = 100;
        $skip = ($page - 1) * $perPage;

        // Count total matching documents
        $countPipeline = $this->buildCountPipelineOrderDate($from, $to, $billingGroup, $orgId, $mrn);
        
        $totalResult = DB::connection('mongodb')
            ->collection('patientorders')
            ->raw(function ($collection) use ($countPipeline) {
                return $collection->aggregate($countPipeline, ['allowDiskUse' => true]);
            })->toArray();
        
        $total = $totalResult[0]['total'] ?? 0;

        // Build main pipeline
        $pipeline = $this->buildMainPipelineOrderDate($from, $to, $billingGroup, $orgId, $orderItemCodeType, $mrn);
        
        // Add pagination
        $pipeline[] = ['$skip' => $skip];
        $pipeline[] = ['$limit' => $perPage];

        ini_set('max_execution_time', 300);
        $results = DB::connection('mongodb')
            ->collection('patientorders')
            ->raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
            })->toArray();

        $paginated = new LengthAwarePaginator(
            collect($results),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get patient name for display
        $patientName = $this->getPatientNameByVisitId($mrn);
        
        // For MRN searches, we need to find the visitId first
        // Try to get visitId from the results
        $visitId = null;
        if (!empty($results)) {
            $firstResult = $results[0];
            if (isset($firstResult['visitId'])) {
                $visitId = $firstResult['visitiuidd'];
            }
        }
        
        //return dd($firstResult);
        // Get detailed patient and visit information for SOA
        $patientVisitDetails = $visitId ? $this->getPatientVisitDetails($visitId) : [];
        //$patientVisitDetails = $this->getPatientVisitDetails('68c8bc18710ed71399429321');
        return view('oecb.index', [
            'dateRange' => $request->input('dateRange'),
            'billingGroup' => $billingGroup,
            'orgId' => $orgId,
            'mrn' => $request->input('mrn'),
            'visitId' => $visitId ?: $request->input('mrn'), // Use actual visitId if found, otherwise fallback to mrn
            'patientName' => $patientName,
            'patientVisitDetails' => $patientVisitDetails,
            'results' => $paginated,
            'vvisitId' => $request->input('vvvisitId'),
            'viewType' => 'oecb_results',
        ]);
    }

    /**
     * Show the initial OECB view without data.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show(Request $request)
    {
        // If we have query parameters (from pagination), process them
        if ($request->has('dateRange') && $request->has('billingGroup')) {
            return $this->index($request);
        }

        return view('oecb.index', [
            'dateRange' => '',
            'mrn' => '',
            'visitId' => '',
            'billingGroup' => 'Medicines',
            'orgId' => '68afd805c1138ac88013a73c',
            'results' => [],
            'viewType' => 'patient_list',
        ]);
    }

    /**
     * Show patient list when no MRN is provided.
     *
     * @param Request $request
     * @param string $from
     * @param string $to
     * @param string $billingGroup
     * @param string $orgId
     * @return \Illuminate\Contracts\Support\Renderable
     */
    private function showPatientList(Request $request, $from, $to, $billingGroup, $orgId, $mrn)
    {
        $page = request()->get('page', 1);
        $perPage = 50;
        $skip = ($page - 1) * $perPage;



        // Build pipeline to get unique patients with their order counts
        $pipeline = [
            /* [
                '$match' => [
                    'startdate' => [
                        '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                        '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime("$to 23:59:59") * 1000),
                    ],
                ]
            ], */
            /* [
                '$match' => [
                    '$or' => [
                        [
                            'visitDetails.startdate' => [
                                '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                                '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime("$to 23:59:59") * 1000),
                            ]
                        ],
                        [
                            'pxDetails.mrn' => $mrn
                        ]
                    ]
                ]
            ], */
            
            [
                '$lookup' => [
                    'from' => 'patients',
                    'localField' => 'patientuid',
                    'foreignField' => '_id',
                    'as' => 'pxDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$pxDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            /* [
                '$lookup' => [
                    'from' => 'patientvisits',
                    'localField' => 'patientvisituid',
                    'foreignField' => '_id',
                    'as' => 'visitDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$visitDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ], */
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'entypeuid',
                    'foreignField' => '_id',
                    'as' => 'entypeDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$entypeDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$match' => [
                    'mrn' => ['$ne' => null, '$ne' => ''],
                    'entypeDetails.valuedescription' => 'Emergency'
                ],
            ],
            [
                '$match' => [
                    'startdate' => [
                        '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                        '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime("$to 23:59:59") * 1000),
                    ],
                    //'pxDetails.mrn' => 'R25049102'
                ]
            ],
            
            [
                '$group' => [
                    '_id' => '$pxDetails.mrn',
                    'patientName' => ['$first' => '$pxDetails.patientname'],
                    'firstname' => ['$first' => '$pxDetails.firstname'],
                    'lastname' => ['$first' => '$pxDetails.lastname'],
                    'mrn' => ['$first' => '$pxDetails.mrn'],
                    'visitId' => ['$first' => '$visitid'],
                    'visitUid' => ['$first' => '$_id'],
                    'orderCount' => ['$sum' => 1],
                    'lastOrderDate' => ['$max' => '$startdate'],
                    'entype' => ['$first' => '$entypeDetails.valuedescription'],
                ],
            ],
            [
                '$sort' => ['orderCount' => -1, 'lastOrderDate' => -1],
            ],
        ];

        // Count total patients - create a separate pipeline for counting
        $countPipeline = [
            [
                '$match' => [
                    'startdate' => [
                        '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                        '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime("$to 23:59:59") * 1000),
                    ],
                ]
            ],
            
            /* [
                '$match' => [
                    '$or' => [
                        [
                            'visitDetails.startdate' => [
                                '$gte' => new \MongoDB\BSON\UTCDateTime(strtotime("$from 00:00:00") * 1000),
                                '$lte' => new \MongoDB\BSON\UTCDateTime(strtotime("$to 23:59:59") * 1000),
                            ]
                        ],
                        [
                            'pxDetails.mrn' => $mrn
                        ]
                    ]
                ]
            ], */
            [
                '$lookup' => [
                    'from' => 'patients',
                    'localField' => 'patientuid',
                    'foreignField' => '_id',
                    'as' => 'pxDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$pxDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            /* [
                '$lookup' => [
                    'from' => 'patientvisits',
                    'localField' => 'patientvisituid',
                    'foreignField' => '_id',
                    'as' => 'visitDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$visitDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ], */
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'entypeuid',
                    'foreignField' => '_id',
                    'as' => 'entypeDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$entypeDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$match' => [
                    'mrn' => ['$ne' => null, '$ne' => ''],
                    'entypeDetails.valuedescription' => 'Emergency'
                ],
            ],
            [
                '$group' => [
                    '_id' => '$pxDetails.mrn',
                    'mrn' => ['$first' => '$pxDetails.mrn'],
                    'visitId' => ['$first' => '$visitid'],
                    'visitUid' => ['$first' => '$_id'],
                ],
            ],
            ['$count' => 'total']
        ];
        
        $totalResult = DB::connection('mongodb')
            ->collection('patientvisits')
            ->raw(function ($collection) use ($countPipeline) {
                return $collection->aggregate($countPipeline, ['allowDiskUse' => true]);
            })->toArray();
        
        $total = $totalResult[0]['total'] ?? 0;

        // Add pagination to main pipeline
        $pipeline[] = ['$skip' => $skip];
        $pipeline[] = ['$limit' => $perPage];

        $results = DB::connection('mongodb')
            ->collection('patientvisits')
            ->raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
            })->toArray();



        // Create pagination with proper URL and query parameters
        $paginated = new LengthAwarePaginator(
            collect($results),
            $total,
            $perPage,
            $page,
            [
                'path' => route('oecb.show'),
                'query' => array_merge($request->query(), [
                    'dateRange' => $request->input('dateRange'),
                    'billingGroup' => $billingGroup,
                    'orgId' => $orgId,
                    'mrn' => ''
                ])
            ]
        );

        return view('oecb.index', [
            'dateRange' => $request->input('dateRange'),
            'billingGroup' => $billingGroup,
            'orgId' => $orgId,
            'mrn' => '',
            'visitId' => '',
            'results' => $paginated,
            'viewType' => 'patient_list',
        ]);
    }

    /**
     * Show OECB results for a specific patient MRN.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function showPatientOECB(Request $request)
    {
        date_default_timezone_set('Asia/Manila');
        
        $visitId = $request->input('visitId');
        $vvisitId = $request->input('vvisitId');
        
        if (empty($visitId)) {
            return redirect()->route('oecb.show')->with('error', 'Visit ID is required.');
        }

        // Get patient name for display - we'll get this from the visit data
        $patientName = $this->getPatientNameByVisitId($visitId);
        
        // Get detailed patient and visit information for SOA
        $patientVisitDetails = $this->getPatientVisitDetails($visitId);

        //dd($visitId);

        // Get date range from request or default to last 7 days
        if ($request->has('dateRange')) {
            $date_range = explode(" - ", $request->input('dateRange'));
            $from = Carbon::createFromFormat('m/d/Y', $date_range[0])->format('Y-m-d');
            $to = Carbon::createFromFormat('m/d/Y', $date_range[1])->format('Y-m-d');
        } else {
            $from = now()->subDays(7)->toDateString();
            $to = now()->toDateString();
        }

        // Get other filters
        $billingGroup = $request->input('billingGroup', 'Medicines');
        $orgId = $request->input('orgId', '68afd805c1138ac88013a73c');
        $orderItemCodeType = $request->input('orderItemCodeType', '68bcd135fd1274d28ea7b997');

        $page = request()->get('page', 1);
        $perPage = 100;
        $skip = ($page - 1) * $perPage;

        // Count total matching documents
        $countPipeline = $this->buildCountPipelineOrderDate($from, $to, $billingGroup, $orgId, $visitId);
        
        $totalResult = DB::connection('mongodb')
            ->collection('patientorders')
            ->raw(function ($collection) use ($countPipeline) {
                return $collection->aggregate($countPipeline, ['allowDiskUse' => true]);
            })->toArray();
        
        $total = $totalResult[0]['total'] ?? 0;

        // Build main pipeline
        $pipeline = $this->buildMainPipelineOrderDate($from, $to, $billingGroup, $orgId, $orderItemCodeType, $visitId);
        
        // Add pagination
        $pipeline[] = ['$skip' => $skip];
        $pipeline[] = ['$limit' => $perPage];

        ini_set('max_execution_time', 300);
        $results = DB::connection('mongodb')
            ->collection('patientorders')
            ->raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
            })->toArray();

        $paginated = new LengthAwarePaginator(
            collect($results),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('oecb.index', [
            'dateRange' => $request->input('dateRange'),
            'billingGroup' => $billingGroup,
            'orgId' => $orgId,
            'visitId' => $visitId,
            'vvisitId' => $vvisitId,
            'patientName' => $patientName,
            'patientVisitDetails' => $patientVisitDetails,
            'results' => $paginated,
            'viewType' => 'oecb_results',
        ]);
    }

    /**
     * Get patient name by MRN.
     *
     * @param string $mrn
     * @return string
     */
    private function getPatientName($mrn)
    {
        try {
            $pipeline = [
                [
                    '$match' => [
                        'mrn' => $mrn
                    ]
                ],
                [
                    '$project' => [
                        'patientname' => 1,
                        'firstname' => 1,
                        'lastname' => 1
                    ]
                ],
                [
                    '$limit' => 1
                ]
            ];

            $result = DB::connection('mongodb')
                ->collection('patients')
                ->raw(function ($collection) use ($pipeline) {
                    return $collection->aggregate($pipeline);
                })->toArray();

            if (!empty($result)) {
                $patient = $result[0];
                $name = '';
                
                if (!empty($patient['patientname'])) {
                    $name = $patient['patientname'];
                } elseif (!empty($patient['firstname']) || !empty($patient['lastname'])) {
                    $name = trim(($patient['firstname'] ?? '') . ' ' . ($patient['lastname'] ?? ''));
                }
                
                return !empty($name) ? $name : 'Unknown Patient';
            }
            
            return 'Unknown Patient';
        } catch (\Exception $e) {
            return 'Unknown Patient';
        }
    }
    
    private function buildCountPipelineOrderDate($from, $to, $billingGroup, $orgId, $searchValue)
    {
        return [
            [
                '$unwind' => [
                    'path' => '$patientorderitems',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'billinggroups',
                    'localField' => 'patientorderitems.billinggroupuid',
                    'foreignField' => '_id',
                    'as' => 'billinggroupDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$billinggroupDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'billinggroups',
                    'localField' => 'patientorderitems.billingsubgroupuid',
                    'foreignField' => '_id',
                    'as' => 'billingsubgroupDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$billingsubgroupDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'billingsubgroupDetails.chargegroupcodeuid',
                    'foreignField' => '_id',
                    'as' => 'itemgroupDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$itemgroupDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'departments',
                    'localField' => 'patientorderitems.ordertodepartmentuid',
                    'foreignField' => '_id',
                    'as' => 'departmentDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$departmentDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'organisations',
                    'localField' => 'orguid',
                    'foreignField' => '_id',
                    'as' => 'orgDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$orgDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'patientorderitems.statusuid',
                    'foreignField' => '_id',
                    'as' => 'statusDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$statusDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'patients',
                    'localField' => 'patientuid',
                    'foreignField' => '_id',
                    'as' => 'pxDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$pxDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'orderitems',
                    'localField' => 'patientorderitems.orderitemuid',
                    'foreignField' => '_id',
                    'as' => 'orderItemDetails',
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'patientvisits',
                    'localField' => 'patientvisituid',
                    'foreignField' => '_id',
                    'as' => 'visitDetails',
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'visitDetails.entypeuid',
                    'foreignField' => '_id',
                    'as' => 'entypeDetails',
                ],
            ],
            [
                '$addFields' => [
                    'valueHash' => [
                        '$function' => [
                            'body' => new \MongoDB\BSON\Javascript('function (doc) { return hex_md5(JSON.stringify(doc)); }'),
                            'args' => ['$$ROOT'],
                            'lang' => 'js',
                        ],
                    ],
                ],
            ],
            [
                '$match' => array_filter([
                    'patientvisituid' => new \MongoDB\BSON\ObjectId($searchValue),
                ], function($value) { return $value !== null; }), // Remove null values
            ],
            ['$count' => 'total'],
        ];
    }

    private function buildMainPipelineOrderDate($from, $to, $billingGroup, $orgId, $orderItemCodeType, $searchValue)
    {
        return [
            [
                '$unwind' => [
                    'path' => '$patientorderitems',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'billinggroups',
                    'localField' => 'patientorderitems.billinggroupuid',
                    'foreignField' => '_id',
                    'as' => 'billinggroupDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$billinggroupDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'billinggroups',
                    'localField' => 'patientorderitems.billingsubgroupuid',
                    'foreignField' => '_id',
                    'as' => 'billingsubgroupDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$billingsubgroupDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'billingsubgroupDetails.chargegroupcodeuid',
                    'foreignField' => '_id',
                    'as' => 'itemgroupDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$itemgroupDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'departments',
                    'localField' => 'patientorderitems.ordertodepartmentuid',
                    'foreignField' => '_id',
                    'as' => 'departmentDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$departmentDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'organisations',
                    'localField' => 'orguid',
                    'foreignField' => '_id',
                    'as' => 'orgDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$orgDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'patientorderitems.statusuid',
                    'foreignField' => '_id',
                    'as' => 'statusDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$statusDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'patients',
                    'localField' => 'patientuid',
                    'foreignField' => '_id',
                    'as' => 'pxDetails',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$pxDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            /* [
                '$unwind' => [
                    'path' => '$entypeDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ], */
            [
                '$lookup' => [
                    'from' => 'orderitems',
                    'localField' => 'patientorderitems.orderitemuid',
                    'foreignField' => '_id',
                    'as' => 'orderItemDetails',
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'patientvisits',
                    'localField' => 'patientvisituid',
                    'foreignField' => '_id',
                    'as' => 'visitDetails',
                ],
            ],
            /* [
                '$unwind' => [
                    'path' => '$visitDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ], */
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'visitDetails.entypeuid',
                    'foreignField' => '_id',
                    'as' => 'entypeDetails',
                ],
            ],
            [
                '$lookup' => [
                    'from' => 'referencevalues',
                    'localField' => 'patientorderitems.quantityUOM',
                    'foreignField' => '_id',
                    'as' => 'uomDetails',
                ],
            ],[
                '$unwind' => [
                    'path' => '$uomDetails',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ], 
            [
                '$addFields' => [
                    'valueHash' => [
                        '$function' => [
                            'body' => new \MongoDB\BSON\Javascript('function (doc) { return hex_md5(JSON.stringify(doc)); }'),
                            'args' => ['$$ROOT'],
                            'lang' => 'js',
                        ],
                    ],
                ],
            ],
            [
                '$match' => array_filter([
                    'patientvisituid' => new \MongoDB\BSON\ObjectId($searchValue),
                ], function($value) { return $value !== null; }), // Remove null values
            ],
            [
                '$project' => [
                    'orderItemCode' => [
                        '$arrayElemAt' => [
                            [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => '$orderItemDetails.orderitemcodes',
                                            'as' => 'inner',
                                            'in' => [
                                                '$filter' => [
                                                    'input' => '$$inner',
                                                    'as' => 'code',
                                                    'cond' => [
                                                        '$eq' => [
                                                            '$$code.orderitemcodetypeuid',
                                                            //new \MongoDB\BSON\ObjectId('68bcd135fd1274d28ea7b997'),
                                                            new \MongoDB\BSON\ObjectId('68b68e570c337bd2523aa0d6'),
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'initialValue' => [],
                                    'in' => ['$concatArrays' => ['$$value', '$$this']],
                                ],
                            ],
                            0,
                        ],
                    ],
                    /* 'orderItemNo' => [
                        '$arrayElemAt' => [
                            [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => '$orderItemDetails.orderitemcodes',
                                            'as' => 'inner',
                                            'in' => [
                                                '$filter' => [
                                                    'input' => '$$inner',
                                                    'as' => 'code',
                                                    'cond' => [
                                                        '$eq' => [
                                                            '$$code.orderitemcodetypeuid',
                                                            //new \MongoDB\BSON\ObjectId('68c9002eecf58a11d5e90208'),
                                                            new \MongoDB\BSON\ObjectId('68c92fa50c337bd2526a1613'),
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'initialValue' => [],
                                    'in' => ['$concatArrays' => ['$$value', '$$this']],
                                ],
                            ],
                            0,
                        ],
                    ], */
                    'orderItemNo' => [
        '$arrayElemAt' => [
            [
                '$reduce' => [
                    'input' => [
                        '$map' => [
                            'input' => '$orderItemDetails.orderitemcodes',
                            'as' => 'inner',
                            'in' => [
                                '$filter' => [
                                    'input' => '$$inner',
                                    'as' => 'code',
                                    'cond' => [
                                        '$in' => [
                                            '$$code.orderitemcodetypeuid',
                                            [
                                                new \MongoDB\BSON\ObjectId("68c92fa50c337bd2526a1613"),
                                                new \MongoDB\BSON\ObjectId("68c92fa50c337bd2526a15f8"),
                                                new \MongoDB\BSON\ObjectId("68c92fa50c337bd2526a15e5"),
                                                new \MongoDB\BSON\ObjectId("68c92fa50c337bd2526a15d4"),
                                                new \MongoDB\BSON\ObjectId("68c92fa50c337bd2526a15be"),
                                                new \MongoDB\BSON\ObjectId("68c92fa50c337bd2526a1434"),
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'initialValue' => [],
                    'in' => [
                        '$concatArrays' => ['$$value', '$$this']
                    ]
                ]
            ],
            0
        ]
        ],
                    /* 'uom' => [
                        '$ifNull' => [
                            [ '$arrayElemAt' => [ '$uomDetails.valuedescription', 0 ] ],
                            '$patientorderitems.ordercattype'
                        ]
                    ], */
                    'uom' => ['$ifNull' => ['$uomDetails.valuedescription', '$patientorderitems.ordercattype']],
                    'careproviderName' => [
                        '$ifNull' => [
                            '$careproviderDetails.printname',
                            [
                                '$concat' => [
                                    '$careproviderDetails.name',
                                    ' ',
                                    '$careproviderDetails.lastname',
                                ],
                            ],
                        ],
                    ],
                    'valueHash' => 1,
                    //'careproviderCode' => '$careproviderDetails.code',
                    'visitId' => [
                        '$arrayElemAt' => [
                            '$visitDetails.visitid',
                            0
                        ]
                    ],
                    'visitiuidd' => [
                        '$arrayElemAt' => [
                            '$visitDetails._id',
                            0
                        ]
                    ],
                    'patientorders_id' => '$_id',
                    //'useruid' => '$latestLog.useruid',
                    'patientorderitems_id' => '$patientorderitems._id',
                    '_id' => 0,
                    'patientorderuid' => '$patientorderuid',
                    'patientorderitemuid' => '$patientorderitemuid',
                    'billinggroups' => '$billinggroupDetails.description',
                    'billinggroupscode' => '$billinggroupDetails.code',
                    'billingsubgroups' => '$billingsubgroupDetails.description',
                    'billingsubgroupscode' => '$billingsubgroupDetails.code',
                    //'chargegroupcodeid' => '$patientorderitems.chargecodeuid',
                    'canceldatetime' => 1,
                    'orderitemname' => '$patientorderitems.orderitemname',
                    'orderitemuid' => '$patientorderitems.orderitemuid',
                    //'ordertodepartmentuid' => '$patientbilleditems.ordertodepartmentuid',
                    'ordertodepartment_desc' => '$departmentDetails.description',
                    'ordertodepartment_name' => '$departmentDetails.name',
                    'totalprice' => ['$ifNull' => ['$patientorderitems.totalprice', 0]],
                    'unitprice' => ['$ifNull' => ['$patientorderitems.unitprice', 0]],
                    'netamount' => ['$ifNull' => ['$patientorderitems.netamount', 0]],
                    'quantity' => ['$ifNull' => ['$patientorderitems.quantity', 0]],
                    'payordiscount' => ['$ifNull' => ['$patientorderitems.payordiscount', 0]],
                    'specialdiscount' => ['$ifNull' => ['$patientorderitems.specialdiscount', 0]],
                    'chargedate' => '$patientorderitems.chargedate',
                    //'agreementdiscounttypeuid' => ['$ifNull' => ['$patientbilleditems.agreementdiscounttypeuid', '']],
                    'itemgroup' => ['$ifNull' => ['$itemgroupDetails.valuedescription', '']],
                    'modifiedat' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d %H:%M:%S',
                            'date' => '$modifiedat',
                        ],
                    ],
                    'createdat' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d %H:%M:%S',
                            'date' => '$createdat',
                        ],
                    ],
                    'cancelat' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d %H:%M:%S',
                            'date' => '$patientorderitems.canceldatetime',
                        ],
                    ],
                    'orderdate' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d %H:%M:%S',
                            'date' => '$orderdate',
                        ],
                    ],
                    /* 'ward' => '$wards.name',
                    'patientvisitpayoruid' => '$patientorderitems.patientvisitpayoruid', */
                    'orguid' => '$orguid',
                    'statusDesc' => '$statusDetails.valuedescription',
                    //'doctorshare' => '$tariffDetails.doctorshare',
                    'isactive' => '$patientorderitems.isactive',
                    'orgcode' => '$orgDetails.code',
                    //'patientpackageuid' => '$patientorderitems.patientpackageuid',
                    //'patientpackageitemuid' => '$patientorderitems.patientpackageitemuid',
                    //'isotcsale' => ['$ifNull' => ['$patientorderitems.isotcsale', 0]],
                    /* 'taxamount' => '$patientorderitems.taxamount',
                    'taxpercentage' => '$patientorderitems.taxpercentage', */
                    'iscontinuousorder' => '$patientorderitems.iscontinuousorder',
                    'discontinueddatetime' => 1,
                ],
            ],
            ['$sort' => ['modifiedat' => -1]],
        ];
    }

    /**
     * Get patient name by visit ID
     *
     * @param string $visitId
     * @return string
     */
    private function getPatientNameByVisitId($visitId)
    {
        try {
            \Log::info('Getting patient name for visitId: ' . $visitId);
            
            $visit = DB::connection('mongodb')
                ->collection('patientvisits')
                //->where('visitid', $visitId)
                ->where('_id', new \MongoDB\BSON\ObjectId($visitId))
                ->first();

            //\Log::info('Visit found: ', $visit ? $visit->toArray() : 'null');

            if ($visit && isset($visit['patientuid'])) {
                $patient = DB::connection('mongodb')
                    ->collection('patients')
                    ->where('_id', $visit['patientuid'])
                    ->first();

              //  \Log::info('Patient found: ', $patient ? $patient->toArray() : 'null');

                if ($patient) {
                    if (!empty($patient['patientname'])) {
                        \Log::info('Returning patientname: ' . $patient['patientname']);
                        return $patient['patientname'];
                    } else {
                        $firstname = $patient['firstname'] ?? '';
                        $lastname = $patient['lastname'] ?? '';
                        $fullName = trim($firstname . ' ' . $lastname);
                        \Log::info('Returning full name: ' . $fullName);
                        return $fullName;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in getPatientNameByVisitId: ' . $e->getMessage());
        }

        \Log::info('Returning Unknown Patient for visitId: ' . $visitId);
        return 'Unknown Patient';
    }

    /**
     * Get patient and visit details for SOA
     *
     * @param string $visitId
     * @return array
     */
    private function getPatientVisitDetails($visitId)
    {
        try {
            // Optimized pipeline - match first to reduce dataset, then do lookups
            $pipeline = [
                // First, do a simple lookup to get visit details and filter early
                [
                    '$lookup' => [
                        'from' => 'patientvisits',
                        'localField' => 'patientvisituid',
                        'foreignField' => '_id',
                        'as' => 'visitDetails'
                    ]
                ],
                [
                    '$unwind' => [
                        'path' => '$visitDetails',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                // Match early to reduce dataset
                [
                    '$match' => [
                        //'visitDetails.visitid' => $visitId,
                        'patientvisituid' => new \MongoDB\BSON\ObjectId($visitId),
                        //'iscancelled' => false
                    ]
                ],
                // Now do the patient lookup on the smaller dataset
                [
                    '$lookup' => [
                        'from' => 'patients',
                        'localField' => 'patientuid',
                        'foreignField' => '_id',
                        'as' => 'patientDetails'
                    ]
                ],
                [
                    '$unwind' => [
                        'path' => '$patientDetails',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'patientvisits',
                        'localField' => 'patientvisituid',
                        'foreignField' => '_id',
                        'as' => 'visitDetails'
                    ]
                ],
                [
                    '$unwind' => [
                        'path' => '$visitDetails',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'payoragreements',
                        'localField' => 'visitDetails.visitpayors.payoragreementuid',
                        'foreignField' => '_id',
                        'as' => 'payorDetails'
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'diagnoses',
                        'let' => [ 'visitId' => '$visitDetails._id' ],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => [ '$eq' => [ '$patientvisituid', '$$visitId' ] ]
                                ]
                            ],
                            [
                                '$project' => [
                                    'diagnosis' => [
                                        '$filter' => [
                                            'input' => '$diagnosis',
                                            'as' => 'd',
                                            'cond' => [ '$eq' => [ '$$d.isprimary', true ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'as' => 'diagnosisDetails'
                    ]
                ],
                [
                    '$unwind' => [
                        'path' => '$diagnosisDetails',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'diagnoses',
                        'let' => [ 'visitId' => '$visitDetails._id' ],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => [ '$eq' => [ '$patientvisituid', '$$visitId' ] ]
                                ]
                            ],
                            [
                                '$project' => [
                                    'diagnosis' => [
                                        '$filter' => [
                                            'input' => '$diagnosis',
                                            'as' => 'd',
                                            'cond' => [ '$eq' => [ '$$d.isprimary', false ] ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'as' => 'diagnosisDetailsFalse'
                    ]
                ],
                [
                    '$unwind' => [
                        'path' => '$diagnosisDetailsFalse',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'problems',
                        'localField' => 'diagnosisDetails.diagnosis.problemuid',
                        'foreignField' => '_id',
                        'as' => 'problemDetails'
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'problems',
                        'localField' => 'diagnosisDetailsFalse.diagnosis.problemuid',
                        'foreignField' => '_id',
                        'as' => 'problemDetailsFalse'
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'users',
                        'localField' => 'modifiedby',
                        'foreignField' => '_id',
                        'as' => 'userDetails'
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'users',
                        'let' => ['careProviders' => '$visitDetails.visitcareproviders'],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => [
                                        '$in' => [
                                            '$_id',
                                            [
                                                '$map' => [
                                                    'input' => [
                                                        '$filter' => [
                                                            'input' => '$$careProviders',
                                                            'as' => 'cp',
                                                            'cond' => [
                                                                '$eq' => ['$$cp.isprimarycareprovider', true]
                                                            ]
                                                        ]
                                                    ],
                                                    'as' => 'fcp',
                                                    'in' => '$$fcp.careprovideruid'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'as' => 'primaryCareProviderDetails'
                    ]
                ],
                

                [
                    '$project' => [
                        '_id' => 0,
                        'modifiedby' => [
                            '$arrayElemAt' => [
                                '$userDetails.printname',
                                0
                            ]
                        ],
                        'soano' => '$sequencenumber',
                        'payorsArr' => '$payorDetails.name',
                        'payors' => [
                            '$reduce' => [
                                'input' => [
                                    '$map' => [
                                        'input' => '$payorDetails',
                                        'as' => 'pd',
                                        'in' => '$$pd.name',
                                    ],
                                ],
                                'initialValue' => '',
                                'in' => [
                                    '$cond' => [
                                        [ '$eq' => ['$$value', ''] ],
                                        '$$this',
                                        [ '$concat' => ['$$value', ', ', '$$this'] ]
                                    ]
                                ]
                            ]
                                    ],
                        'name' => [
                            '$concat' => [
                                '$patientDetails.firstname',
                                ' ',
                                '$patientDetails.lastname'
                            ]
                        ],
                        'address' => [
                            '$concat' => [
                                '$patientDetails.address.address', ' ',
                                '$patientDetails.address.area', ' ',
                                '$patientDetails.address.city', ' ',
                                '$patientDetails.address.state', ' ',
                                '$patientDetails.address.country', ' ',
                                '$patientDetails.address.zipcode'
                            ]
                        ],
                        'age' => [
                            '$dateDiff' => [
                                'startDate' => '$patientDetails.dateofbirth',
                                'endDate'   => '$$NOW',
                                'unit'      => 'year'
                            ]
                        ],
                        'admittedat' => [
                            '$dateToString' => [
                                'format' => '%Y-%m-%d',
                                'date'   => '$visitDetails.startdate'
                            ]
                        ],
                        'dischargeat' => [
                            '$dateToString' => [
                                'format' => '%Y-%m-%d',
                                'date'   => '$visitDetails.medicaldischargedate'
                            ]
                        ],
                            'finalDiagnosis' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => '$problemDetails',
                                            'as' => 'pd',
                                            'in' => [
                                                '$concat' => ['$$pd.code', ' ', '$$pd.name']
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$cond' => [
                                            [ '$eq' => [ '$$value', '' ] ],
                                            '$$this',
                                            [ '$concat' => [ '$$value', ' | ', '$$this' ] ]
                                        ]
                                    ]
                                ]
                            ],
                            'otherDiagnosis' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => '$problemDetailsFalse',
                                            'as' => 'pd',
                                            'in' => [
                                                '$concat' => ['$$pd.code', ' ', '$$pd.name']
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$cond' => [
                                            [ '$eq' => [ '$$value', '' ] ],
                                            '$$this',
                                            [ '$concat' => [ '$$value', ' | ', '$$this' ] ]
                                        ]
                                    ]
                                ]
                                        ],
                            'doctor' => [
                                '$arrayElemAt' => [
                                    '$primaryCareProviderDetails.printname',
                                    0
                                ]
                            ]
                    ]
                ],
                [
                    '$limit' => 1
                ]
            ];

            // Set timeout and memory limits
            ini_set('max_execution_time', 60);
            ini_set('memory_limit', '512M');
            
            $result = DB::connection('mongodb')
                ->collection('patientbills')
                ->raw(function ($collection) use ($pipeline) {
                    return $collection->aggregate($pipeline, [
                        'allowDiskUse' => true,
                        'maxTimeMS' => 30000 // 30 second timeout
                    ]);
                })->toArray();

            // Debug: Log the result to see what we're getting
            \Log::info('Patient Visit Details Result (exact pipeline):', $result);

            if (!empty($result)) {
                $data = $result[0];
                
                // Convert BSONDocument to array if needed
                if ($data instanceof \MongoDB\Model\BSONDocument) {
                    $data = iterator_to_array($data);
                }
                
                // Convert ObjectId to string if needed
                if (isset($data['soano']) && $data['soano'] instanceof \MongoDB\BSON\ObjectId) {
                    $data['soano'] = (string) $data['soano'];
                }
                
                return $data;
            }
            
            return [];
        } catch (\Exception $e) {
            \Log::error('Error in getPatientVisitDetails: ' . $e->getMessage());
            
            // Try a simpler approach - direct queries instead of aggregation
            try {
                \Log::info('Trying simpler approach for visitId: ' . $visitId);
                
                // Get visit details directly
                $visit = DB::connection('mongodb')
                    ->collection('patientvisits')
                    ->where('visitid', $visitId)
                    ->first();
                
                if ($visit) {
                    // Get patient details
                    $patient = DB::connection('mongodb')
                        ->collection('patients')
                        ->where('_id', $visit['patientuid'])
                        ->first();
                    
                    if ($patient) {
                        $name = '';
                        if (!empty($patient['patientname'])) {
                            $name = $patient['patientname'];
                        } else {
                            $firstname = $patient['firstname'] ?? '';
                            $lastname = $patient['lastname'] ?? '';
                            $name = trim($firstname . ' ' . $lastname);
                        }
                        
                        return [
                            'soano' => $visit['sequencenumber'] ?? 'catch error SOA-' . date('Ymd') . '-' . substr($visitId, -4),
                            'name' => $name ?: 'Unknown Patient',
                            'address' => $this->buildAddress($patient),
                            'age' => $this->calculateAge($patient['dateofbirth'] ?? null),
                            'admittedat' => $this->formatDate($visit['startdate'] ?? null),
                            'dischargeat' => $this->formatDate($visit['medicaldischargedate'] ?? null)
                        ];
                    }
                }
            } catch (\Exception $e2) {
                \Log::error('Error in fallback method: ' . $e2->getMessage());
            }
            
            // Final fallback - just return basic data
            return [
                'soano' => 'catch error SOA-' . date('Ymd') . '-' . substr($visitId, -4),
                'name' => 'Unknown Patient',
                'address' => 'Address not available',
                'age' => 0,
                'admittedat' => date('Y-m-d H:i:s'),
                'dischargeat' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Build address string from patient data
     */
    private function buildAddress($patient)
    {
        if (!isset($patient['address'])) {
            return 'Address not available';
        }
        
        $address = $patient['address'];
        $parts = [
            $address['address'] ?? '',
            $address['area'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['country'] ?? '',
            $address['zipcode'] ?? ''
        ];
        
        return trim(implode(' ', array_filter($parts)));
    }

    /**
     * Calculate age from date of birth
     */
    private function calculateAge($dateOfBirth)
    {
        if (!$dateOfBirth) {
            return 0;
        }
        
        try {
            if ($dateOfBirth instanceof \MongoDB\BSON\UTCDateTime) {
                $dob = $dateOfBirth->toDateTime();
            } else {
                $dob = new \DateTime($dateOfBirth);
            }
            
            $now = new \DateTime();
            return $now->diff($dob)->y;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format date for display
     */
    private function formatDate($date)
    {
        if (!$date) {
            return date('Y-m-d H:i:s');
        }
        
        try {
            if ($date instanceof \MongoDB\BSON\UTCDateTime) {
                return $date->toDateTime()->format('Y-m-d H:i:s');
            } else {
                return (new \DateTime($date))->format('Y-m-d H:i:s');
            }
        } catch (\Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }

}