<?php

namespace App\Services\Ai;

use App\Models\SensorReading;
use App\Models\User;
use App\Services\Weather\OpenMeteoClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiDecisionService
{
    public function __construct(
        private readonly AiDriverInterface $driver,
        private readonly OpenMeteoClient $weather,
    ) {}

    /**
     * Build payload, call AI, store result, update pump cache.
     */
    public function decide(SensorReading $reading): void
    {
        if (! config('farm.ai.endpoint')) {
            Log::info('AI decision skipped — no endpoint configured.');

            return;
        }

        $weatherData = null;
        $lat = config('farm.latitude');
        $lng = config('farm.longitude');

        if ($lat && $lng) {
            $weatherData = $this->weather->getForecast((float) $lat, (float) $lng);
        }

        $lastCommand = Cache::get('pump_command', 'OFF');
        $lastCommandAt = Cache::get('pump_command_at');
        $plantName = User::value('plant_name');

        $payload = [
            'sensor' => [
                'moisture_percent' => $reading->moisture_percent,
                'soil_status' => $reading->soil_status,
                'rain_percent' => $reading->rain_percent,
                'rain_status' => $reading->rain_status,
                'temp_celsius' => $reading->temp,
                'humidity_percent' => $reading->humidity,
                'tank_status' => $reading->tank_status,
                'tank_fill_percent' => $reading->tank_fill_percent,
            ],
            'weather' => $weatherData,
            'context' => [
                'plant_name' => $plantName,
                'last_pump_command' => $lastCommand,
                'last_pump_command_at' => $lastCommandAt,
                'moisture_threshold' => config('farm.moisture_threshold', 30),
            ],
        ];

        $decision = $this->driver->decide($payload);

        // Safety override — never turn pump ON when tank is empty
        if ($reading->tank_status === 'EMPTY') {
            $decision['pump'] = 'OFF';
            $decision['reason'] = 'Pump disabled: water tank is empty.';
        }

        $reading->update([
            'pump_command' => $decision['pump'],
            'ai_reason' => $decision['reason'],
        ]);

        Cache::put('pump_command', $decision['pump']);
        Cache::put('pump_command_at', now()->toISOString());

        Log::info('AI pump decision', [
            'pump' => $decision['pump'],
            'reason' => $decision['reason'],
        ]);
    }
}
