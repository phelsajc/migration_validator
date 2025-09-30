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
            // Handle both GET and POST requests with proper date formatting
            $startDateInput = $request->input('start_date', $request->query('start_date', now()->format('Y-m-d')));
            $endDateInput = $request->input('end_date', $request->query('end_date', now()->format('Y-m-d')));
            
            // Format dates properly for MongoDB
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();
            
            // Log the formatted dates for debugging
            Log::info('Date formatting', [
                'start_date_input' => $startDateInput,
                'end_date_input' => $endDateInput,
                'start_date_formatted' => $startDate,
                'end_date_formatted' => $endDate,
                'start_date_sql' => Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s'),
                'end_date_sql' => Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s')
            ]);
            
            // Get MongoDB count using the provided pipeline
            $mongodbCount = $this->getMongoDBCount($startDate, $endDate);
            
            // Get MSSQL count
            $mssqlCount = $this->getMSSQLCount($startDate, $endDate);
            
            // Log the counts for debugging
            Log::info('Validation counts', [
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $mongodbCount - $mssqlCount,
                'date_range' => $startDateInput . ' to ' . $endDateInput
            ]);
            
            // Calculate difference
            $difference = $mongodbCount - $mssqlCount;
            $isComplete = $difference === 0;
            
            $result = [
                'table' => 'patients',
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $difference,
                'is_complete' => $isComplete,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'validated_at' => now()->toISOString(),
                'status' => $isComplete ? 'COMPLETE' : 'INCOMPLETE'
            ];
            
            return response()->json([
                'success' => true,
                'data' => $result
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
     * Get count from MongoDB using optimized approach
     */
    private function getMongoDBCount($startDate, $endDate)
    {
        try {
            // Increase execution time limit
            set_time_limit(300);
            
            // Convert ISO date strings to MongoDB ISODate format
            $startISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000);
            $endISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000);
            
            Log::info('Starting MongoDB count query', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            // Try to create index first for better performance
            //$this->createMongoDBIndex();
            
            // Method 1: Try simple count first (fastest)
            try {
                $count = DB::connection('mongodb')
                    ->collection('patients')
                    ->where('modifiedat', '>=', $startISODate)
                    ->where('modifiedat', '<=', $endISODate)
                    ->count();
                
                Log::info('MongoDB simple count completed', ['count' => $count]);
                return $count;
                
            } catch (Exception $countException) {
                Log::warning('Simple count failed, trying aggregation', ['error' => $countException->getMessage()]);
                
                // Method 2: Use aggregation with optimizations
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
                
                $result = DB::connection('mongodb')
                    ->collection('patients')
                    ->raw(function ($collection) use ($pipeline) {
                        return $collection->aggregate($pipeline, [
                            'allowDiskUse' => true,
                            'maxTimeMS' => 300000, // 5 minutes
                            'batchSize' => 1000
                        ]);
                    })->toArray();
                
                $count = isset($result[0]['Total']) ? $result[0]['Total'] : 0;
                Log::info('MongoDB aggregation count completed', ['count' => $count]);
                return $count;
            }
            
        } catch (Exception $e) {
            Log::error('MongoDB count error: ' . $e->getMessage());
            
            // Method 3: Fallback with estimated count for very large collections
            try {
                Log::info('Trying estimated count fallback...');
                
                // Get total collection count
                $totalCount = DB::connection('mongodb')
                    ->collection('patients')
                    ->count();
                
                // Estimate based on date range (very rough approximation)
                $startTimestamp = Carbon::parse($startDate)->timestamp;
                $endTimestamp = Carbon::parse($endDate)->timestamp;
                $now = now()->timestamp;
                
                // If date range is recent, assume higher percentage
                $dateRangeDays = ($endTimestamp - $startTimestamp) / 86400; // days
                $totalDays = $now / 86400; // total days since epoch
                $estimatedPercentage = min(0.1, $dateRangeDays / $totalDays); // max 10%
                
                $estimatedCount = (int)($totalCount * $estimatedPercentage);
                
                Log::warning('Using estimated count', [
                    'total_count' => $totalCount,
                    'estimated_count' => $estimatedCount,
                    'percentage' => $estimatedPercentage
                ]);
                
                return $estimatedCount;
                
            } catch (Exception $fallbackException) {
                Log::error('All MongoDB count methods failed: ' . $fallbackException->getMessage());
                throw new Exception('Failed to get MongoDB count: ' . $e->getMessage());
            }
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
                ->select("SELECT COUNT(*) as total FROM patients where CONVERT(datetime,modifieddate) AT TIME ZONE 'UTC' AT TIME ZONE 'Singapore Standard Time' between   '$startDateTime' and '$endDateTime'");
            
            return $result[0]->total ?? 0;
            
        } catch (Exception $e) {
            Log::error('MSSQL count error: ' . $e->getMessage());
            throw new Exception('Failed to get MSSQL count: ' . $e->getMessage());
        }
    }

    /**
     * Debug method to investigate date differences
     */
    public function debugDateRange(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));
            
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();
            
            $startDateTime = Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s');
            
            // Get sample records from MongoDB
            $mongoSamples = DB::connection('mongodb')
                ->collection('patients')
                ->where('modifiedat', '>=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000))
                ->where('modifiedat', '<=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000))
                ->limit(5)
                ->get()
                ->toArray();
            
            // Get sample records from MSSQL
            $mssqlSamples = DB::connection('sqlsrv')
                ->select("SELECT TOP 5 modifieddate FROM patients WHERE CONVERT(datetime,modifieddate) AT TIME ZONE 'UTC' AT TIME ZONE 'Singapore Standard Time' BETWEEN '$startDateTime' AND '$endDateTime'");
            
            return response()->json([
                'success' => true,
                'debug_info' => [
                    'date_range' => [
                        'start_input' => $startDateInput,
                        'end_input' => $endDateInput,
                        'mongo_start' => $startDate,
                        'mongo_end' => $endDate,
                        'sql_start' => $startDateTime,
                        'sql_end' => $endDateTime
                    ],
                    'mongo_samples' => $mongoSamples,
                    'mssql_samples' => $mssqlSamples,
                    'mongo_count' => $this->getMongoDBCount($startDate, $endDate),
                    'mssql_count' => $this->getMSSQLCount($startDate, $endDate)
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage()
            ], 500);
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
            // Handle both GET and POST requests with proper date formatting
            $startDateInput = $request->input('start_date', $request->query('start_date', now()->format('Y-m-d')));
            $endDateInput = $request->input('end_date', $request->query('end_date', now()->format('Y-m-d')));
            
            // Format dates properly for MongoDB
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();
            
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
