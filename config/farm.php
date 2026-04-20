<?php

/*
 * Farm configuration is stored in the database via the FarmSetting model.
 * Use FarmSetting::current() to access settings — do not add env() calls here.
 *
 * This file is intentionally empty and kept only so the config namespace exists.
 */

return [
    'ai_endpoint' => env('FARM_AI_ENDPOINT'),
    'ai_api_key' => env('FARM_AI_API_KEY'),
];
