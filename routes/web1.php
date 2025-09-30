<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/* Route::get('/', function () {
    return view('welcome');
}); */

//Auth::routes();
/* Auth::routes(['register' => false]);
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->middleware('auth');
Route::get('/readersfee', [App\Http\Controllers\ReaderFeeController::class, 'index'])->name('readersfee')->middleware('auth');


Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/home'); 
    }
    return redirect()->route('login');
});

 */

Route::group(['middleware'=>'revalidate'], function() {
    # Login Sessions and verifications
    //Route::get('/home','UserAuthController@index')->name('home');   
    Route::get('/','UserAuthController@index')->name('login');   
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/readersfee', [App\Http\Controllers\ReaderFeeController::class, 'index'])->name('readersfee');
    Route::match(['get','post'],'/readersfee', [App\Http\Controllers\ReaderFeeController::class, 'index'])->name('readersfee');
    Route::get('/test-date', [App\Http\Controllers\ReaderFeeController::class, 'testDate'])->name('test.date');
    
    // Test route for debugging database connections
    Route::get('/test-db', function() {
        try {
            // Test SQL Server connection
            $sqlsrv_test = DB::connection('sqlsrv')->select('SELECT 1 as test');
            $sqlsrv_status = 'Connected';
        } catch (\Exception $e) {
            $sqlsrv_status = 'Failed: ' . $e->getMessage();
        }
        
        try {
            // Test Bizbox connection
            $bizbox_test = DB::connection('bizbox')->select('SELECT 1 as test');
            $bizbox_status = 'Connected';
        } catch (\Exception $e) {
            $bizbox_status = 'Failed: ' . $e->getMessage();
        }
        
        return [
            'sqlsrv_connection' => $sqlsrv_status,
            'bizbox_connection' => $bizbox_status,
            'current_time' => now(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    });
    
    //Route::get('/episodes', [App\Http\Controllers\EpisodesController::class, 'index'])->name('episodes');
    Route::match(['get','post'],'episodes',[App\Http\Controllers\EpisodesController::class, 'index'])->name('episodes');
    Route::match(['get','post'],'audit_logs',[App\Http\Controllers\AuditLogsController::class, 'index'])->name('audit_logs');
    Route::match(['get','post'],'export-export',[App\Http\Controllers\AuditLogsController::class, 'exportReport'])->name('export_audit_logs');
    
    // OECB Routes
    Route::get('/oecb', [App\Http\Controllers\OECBController::class, 'show'])->name('oecb.show');
    Route::post('/oecb', [App\Http\Controllers\OECBController::class, 'index'])->name('oecb.index');
    Route::post('/oecb/patient', [App\Http\Controllers\OECBController::class, 'showPatientOECB'])->name('oecb.patient');
    Route::post('/oecb/export', [App\Http\Controllers\OECBController::class, 'export'])->name('oecb.export');
    Route::post('/oecb/csf', [App\Http\Controllers\CsfControllerv3::class, 'generateCSF'])->name('oecb.csf');
Route::get('/oecb/debug/{visitId}', [App\Http\Controllers\OECBController::class, 'debugPatientVisitDetails'])->name('oecb.debug');
    Route::post('/log_me_in', 'UserAuthController@log_me_in')->name('log_me_in');
    Route::get('/logout', 'UserAuthController@logout')->name('logout');
    Route::get('/export-results', [App\Http\Controllers\EpisodesController::class, 'export'])->name('export.excel');
    Route::get('/exportnow-results', [App\Http\Controllers\EpisodesController::class, 'export'])->name('exportnow.excel');
    Route::get('/export-large', [App\Http\Controllers\EpisodesController::class, 'exportLarge'])->name('export.large');
    Route::get('/export-episodes-xlsx', [App\Http\Controllers\EpisodesController::class, 'exportXlsx'])->name('export.episodes.xlsx');
    
    /* Route::get('/transfer', function () {
       $sid = session()->getId();
        return redirect()->away("http://192.168.110.14:8090?sid=$sid");
    });

    Route::get('/check-redis', function () {
        try {
            \Illuminate\Support\Facades\Redis::set('test_key', 'test_value');
            return 'Redis is working!';
        } catch (\Exception $e) {
            return 'Redis not working: ' . $e->getMessage();
        }
    });

    Route::get('/test-driver', function () {
        return session()->getHandler();
    });

    Route::get('/session-driver', function () {
        return config('session.driver');
    });

    Route::get('/store-session', function () {
        session(['user_id' => 'check-redis']);
        return [
            'session_id' => session()->getId(),
            'user_id' => session('user_id'),
            'driver' => config('session.driver')
        ];
    });

    Route::get('/session-handler', function () {
        return get_class(session()->getHandler());
    });

    Route::get('/debug-env', function () {
        return [
            'SESSION_DRIVER' => env('SESSION_DRIVER'),
            'session.driver' => config('session.driver'),
            'handler' => get_class(session()->getHandler())
        ];
    });

    Route::get('/env-check', function () {
        return [
            'APP_NAME' => env('APP_NAME'),
            'SESSION_DRIVER' => env('SESSION_DRIVER'),
            'session.driver' => config('session.driver'),
            'handler' => get_class(session()->getHandler()),
        ];
    }); */

    Route::get('/check-session', function () {

        //session(['user_id' => 'check-redis']);
        return [
            'session_id' => session()->getId(),
            'user_id' => session('user_id'),
        ];

        /* return [
            'session_id' => session()->getId(),
            'user_id' => session('user_id'),
        ]; */
    });
    
});