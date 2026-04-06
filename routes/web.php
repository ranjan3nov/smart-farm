<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PumpOverrideController;
use App\Http\Controllers\SettingsController;
use App\Models\SensorReading;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/latest', [DashboardController::class, 'latest'])->name('dashboard.latest');
    Route::get('/dashboard/raw', [DashboardController::class, 'rawData'])->name('dashboard.raw');
    Route::get('/docs', fn () => view('docs'))->name('docs');
    Route::get('/ai-tester', fn () => view('ai-tester', ['latest' => SensorReading::latest()->first()]))->name('ai-tester');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/plant/reset', [SettingsController::class, 'resetPlant'])->name('settings.plant.reset');

    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/password/change', [AuthController::class, 'changePassword'])->name('password.update');

    Route::post('/pump/override', PumpOverrideController::class)->name('pump.override');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
