<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MigrationValidationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Migration Validation API Routes (no CSRF protection)
Route::prefix('migration-validation')->group(function () {
    // Legacy routes
    Route::get('/validate/patients', [MigrationValidationController::class, 'validatePatients']);
    Route::post('/validate/patients', [MigrationValidationController::class, 'validatePatients']);
    
    // Generic validation routes
    Route::get('/validate/table', [MigrationValidationController::class, 'validateTable']);
    Route::post('/validate/table', [MigrationValidationController::class, 'validateTable']);
    
    // Specific table routes
    Route::get('/validate/careproviders', [MigrationValidationController::class, 'validateTable'])->defaults('table', 'careproviders');
    Route::get('/validate/patientorderitems', [MigrationValidationController::class, 'validateTable'])->defaults('table', 'patientorderitems');
    
    // Other routes
    Route::get('/tables', [MigrationValidationController::class, 'getAvailableTables']);
    Route::get('/history', [MigrationValidationController::class, 'getValidationHistory']);
    Route::post('/validate/all', [MigrationValidationController::class, 'validateAllTables']);
    Route::post('/create-index', [MigrationValidationController::class, 'createIndex']);
    Route::get('/debug', [MigrationValidationController::class, 'debugDateRange']);
    Route::get('/missing-records', [MigrationValidationController::class, 'findMissingRecords']);
    Route::get('/extra-records', [MigrationValidationController::class, 'findExtraRecords']);
});
