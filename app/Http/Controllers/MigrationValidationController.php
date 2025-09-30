<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class MigrationValidationController extends Controller
{
    /**
     * Display the migration validation dashboard
     */
    public function index()
    {
        return view('migration-validation.dashboard');
    }

    /**
     * Validate patients table migration completeness
     */
    public function validatePatients(Request $request)
    {
        try {
            $startDate = $request->input('start_date', '2025-09-28T00:00:00Z');
            $endDate = $request->input('end_date', '2025-09-28T23:59:59Z');
            
            // Get MongoDB count using the provided pipeline
            $mongodbCount = $this->getMongoDBCount($startDate, $endDate);
            
            // Get MSSQL count
            $mssqlCount = $this->getMSSQLCount($startDate, $endDate);
            
            // Calculate difference
            //$difference = $mongodbCount - $mssqlCount;
            //$isComplete = $difference === 0;
            
            /* $result = [
                'table' => 'patients',
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $difference,
                'is_complete' => $isComplete,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'validated_at' => now()->toISOString(),
                'status' => $isComplete ? 'COMPLETE' : 'INCOMPLETE'
            ]; */
            
            return response()->json([
                'success' => true,
                'data' => $mongodbCount
            ]);
            
        } catch (Exception $e) {
            Log::error('Migration validation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count from MongoDB using the provided pipeline
     */
    private function getMongoDBCount($startDate, $endDate)
    {
        try {
            
            // Convert ISO date strings to MongoDB ISODate format
            $startISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000);
            $endISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000);
            
            // Build the aggregation pipeline
            $pipeline = [
                [
                    '$match' => [
                        'modifiedat' => [
                            '$gte' => $startISODate,
                            '$lte' => $endISODate
                        ]
                    ]
                ],
                [
                    '$count' => 'Total'
                ]
            ];
            
            // Execute the aggregation pipeline
            $result = DB::connection('mongodb')
                ->collection('patients')
                ->aggregate($pipeline)
                ->toArray();

                $totalResult = DB::connection('mongodb')
            ->collection('patients')
            ->raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
            })->toArray();
        
        $total = $totalResult[0]['Total'] ?? 0;
                
        return $total;
            
            // Return the count or 0 if no results
            return isset($result[0]['Total']) ? $result[0]['Total'] : 0;
            
        } catch (Exception $e) {
            Log::error('MongoDB count error: ' . $e->getMessage());
            throw new Exception('Failed to get MongoDB count: ' . $e->getMessage());
        }
    }

    /**
     * Get count from MSSQL
     */
    private function getMSSQLCount($startDate, $endDate)
    {
        try {
            // Convert ISO date strings to SQL Server datetime format
            $startDateTime = Carbon::parse($startDate)->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDate)->format('Y-m-d H:i:s');
            
            // Execute the SQL query
            $result = DB::connection('sqlsrv')
                ->select("SELECT COUNT(*) as total FROM patients");
            
            return $result[0]->total ?? 0;
            
        } catch (Exception $e) {
            Log::error('MSSQL count error: ' . $e->getMessage());
            throw new Exception('Failed to get MSSQL count: ' . $e->getMessage());
        }
    }

    /**
     * Get validation history
     */
    public function getValidationHistory()
    {
        try {
            // This would typically come from a database table storing validation history
            // For now, return a sample response
            return response()->json([
                'success' => true,
                'data' => [
                    'validations' => [
                        [
                            'id' => 1,
                            'table' => 'patients',
                            'mongodb_count' => 1500,
                            'mssql_count' => 1500,
                            'difference' => 0,
                            'is_complete' => true,
                            'validated_at' => now()->subHours(1)->toISOString(),
                            'status' => 'COMPLETE'
                        ],
                        [
                            'id' => 2,
                            'table' => 'patients',
                            'mongodb_count' => 2000,
                            'mssql_count' => 1995,
                            'difference' => 5,
                            'is_complete' => false,
                            'validated_at' => now()->subHours(2)->toISOString(),
                            'status' => 'INCOMPLETE'
                        ]
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Get validation history error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get validation history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate multiple tables at once
     */
    public function validateAllTables(Request $request)
    {
        try {
            $startDate = $request->input('start_date', '2025-03-28T00:00:00Z');
            $endDate = $request->input('end_date', '2025-09-28T23:59:59Z');
            
            $tables = ['patients']; // Add more tables as needed
            
            $results = [];
            
            foreach ($tables as $table) {
                $mongodbCount = $this->getMongoDBCount($startDate, $endDate);
                $mssqlCount = $this->getMSSQLCount($startDate, $endDate);
                $difference = $mongodbCount - $mssqlCount;
                
                $results[] = [
                    'table' => $table,
                    'mongodb_count' => $mongodbCount,
                    'mssql_count' => $mssqlCount,
                    'difference' => $difference,
                    'is_complete' => $difference === 0,
                    'status' => $difference === 0 ? 'COMPLETE' : 'INCOMPLETE'
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'validations' => $results,
                    'summary' => [
                        'total_tables' => count($tables),
                        'complete_tables' => count(array_filter($results, function($r) { return $r['is_complete']; })),
                        'incomplete_tables' => count(array_filter($results, function($r) { return !$r['is_complete']; }))
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Validate all tables error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
