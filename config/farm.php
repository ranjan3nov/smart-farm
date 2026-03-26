<?php

return [
    'name' => env('FARM_NAME', 'My Plant'),
    'latitude' => env('FARM_LATITUDE', null),
    'longitude' => env('FARM_LONGITUDE', null),

    /*
     * Tank height in cm. Used to calculate fill percentage from ultrasonic distance.
     * Set to the interior height of your water tank.
     */
    'tank_height_cm' => env('FARM_TANK_HEIGHT_CM', 200),

    /*
     * Soil moisture threshold (0–100 %) below which the AI is nudged to turn the pump ON.
     */
    'moisture_threshold' => env('FARM_MOISTURE_THRESHOLD', 30),

    /*
     * How often (in minutes) the AI makes a new pump decision.
     */
    'ai_decision_interval_minutes' => env('FARM_AI_DECISION_INTERVAL', 5),

    'ai' => [
        'endpoint' => env('FARM_AI_ENDPOINT', null),
        'api_key' => env('FARM_AI_API_KEY', null),
        'driver' => env('FARM_AI_DRIVER', 'http'), // http | openai | anthropic
    ],
];
