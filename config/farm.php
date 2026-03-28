<?php

return [
    'name' => env('FARM_NAME', 'My Plant'),
    'latitude' => env('FARM_LATITUDE', null),
    'longitude' => env('FARM_LONGITUDE', null),

    /*
     * Tank height in cm — must match the ESP32 empty threshold (hardcoded at 20 cm).
     * This is the distance from the ultrasonic sensor to the bottom of the tank.
     * Default 20 aligns with: bool tankEmpty = (waterDistance > 20.0)
     */
    'tank_height_cm' => env('FARM_TANK_HEIGHT_CM', 20),

    /*
     * Soil moisture threshold (0–100 %) below which the AI is nudged to turn the pump ON.
     */
    'moisture_threshold' => env('FARM_MOISTURE_THRESHOLD', 30),

    /*
     * How often (in minutes) the AI makes a new pump decision.
     */
    'ai_decision_interval_minutes' => env('FARM_AI_DECISION_INTERVAL', 5),

    /*
     * How often (in seconds) the device sends sensor readings in normal (non-alert) mode.
     * Alert mode always uses 20s regardless of this setting.
     */
    'send_interval_seconds' => env('FARM_SEND_INTERVAL', 300),

    'ai' => [
        'endpoint' => env('FARM_AI_ENDPOINT', null),
        'api_key' => env('FARM_AI_API_KEY', null),
        'driver' => env('FARM_AI_DRIVER', 'http'), // http | openai | anthropic
    ],
];
