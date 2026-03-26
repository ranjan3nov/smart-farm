<?php

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\SensorDataController;
use Illuminate\Support\Facades\Route;

Route::get('/config', [ConfigController::class, 'show']);
Route::post('/sensor-data', [SensorDataController::class, 'store']);
