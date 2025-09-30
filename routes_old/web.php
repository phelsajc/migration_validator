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
    Route::post('/log_me_in', 'UserAuthController@log_me_in')->name('log_me_in');
    Route::get('/logout', 'UserAuthController@logout')->name('logout');
});