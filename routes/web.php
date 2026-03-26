<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Dashboard Frontend
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

// Dashboard API Actions
Route::group(['prefix' => 'dashboard-api', 'middleware' => ['web']], function () {
    Route::get('/stats', [\App\Http\Controllers\DashboardController::class, 'apiStats']);
    Route::get('/trades', [\App\Http\Controllers\DashboardController::class, 'apiTrades']);
    Route::get('/errors', [\App\Http\Controllers\DashboardController::class, 'apiErrors']);
    Route::post('/run-bot', [\App\Http\Controllers\DashboardController::class, 'runBot']);
    Route::get('/check-telegram', [\App\Http\Controllers\DashboardController::class, 'checkTelegram']);
    Route::post('/send-telegram', [\App\Http\Controllers\DashboardController::class, 'sendTelegram']);
});
