<?php

use App\Http\Controllers\AiTestRunController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PumpOverrideController;
use App\Http\Controllers\SettingsController;
use App\Models\AiTestRun;
use App\Models\FarmSetting;
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
    Route::get('/ai-tester', fn () => view('ai-tester', [
        'latest'   => SensorReading::latest()->first(),
        'pastRuns' => AiTestRun::latestPerScenario(),
        'settings' => FarmSetting::current(),
    ]))->name('ai-tester');
    Route::post('/ai-tester/runs', [AiTestRunController::class, 'store'])->name('ai-tester.runs.store');
    Route::post('/ai-tester/proxy', [AiTestRunController::class, 'proxy'])->name('ai-tester.proxy');
    Route::patch('/ai-tester/endpoint', [AiTestRunController::class, 'updateEndpoint'])->name('ai-tester.endpoint.update');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/plant/reset', [SettingsController::class, 'resetPlant'])->name('settings.plant.reset');

    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/password/change', [AuthController::class, 'changePassword'])->name('password.update');

    Route::post('/pump/override', PumpOverrideController::class)->name('pump.override');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
