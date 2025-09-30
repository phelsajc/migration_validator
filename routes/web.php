<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MigrationValidationController;
use App\Http\Controllers\UserAuthController;

// Authentication Routes
Route::get('/', [UserAuthController::class, 'index'])->name('login');
Route::post('/login', [UserAuthController::class, 'log_me_in'])->name('login.submit');
Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout');

// Home route (after login)
Route::get('/home', function () {
    return view('welcome');
})->name('home');

// Migration Validation Routes (CSRF excluded)
Route::prefix('migration-validation')->group(function () {
    Route::get('/', [MigrationValidationController::class, 'index'])->name('migration-validation.dashboard');
    Route::get('/validate/patients', [MigrationValidationController::class, 'validatePatients'])->name('migration-validation.patients.get');
    Route::post('/validate/patients', [MigrationValidationController::class, 'validatePatients'])->name('migration-validation.patients');
    Route::get('/history', [MigrationValidationController::class, 'getValidationHistory'])->name('migration-validation.history');
    Route::post('/validate/all', [MigrationValidationController::class, 'validateAllTables'])->name('migration-validation.all');
});
