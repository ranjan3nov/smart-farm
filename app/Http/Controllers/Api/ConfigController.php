<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmSetting;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'tank_height_cm' => FarmSetting::current()->tank_height_cm,
        ]);
    }
}
