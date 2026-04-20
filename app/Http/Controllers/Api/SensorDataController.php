<?php

namespace App\Http\Controllers\Api;

use App\Events\SensorDataReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSensorDataRequest;
use App\Jobs\MakePumpDecision;
use App\Models\FarmSetting;
use App\Models\PumpSession;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SensorDataController extends Controller
{
    public function store(StoreSensorDataRequest $request): JsonResponse
    {
        $reading = SensorReading::create($request->validated());
        $settings = FarmSetting::current();

        // Track last-seen for offline detection
        Cache::put('device_last_seen', now()->timestamp, now()->addMinutes(5));

        // When pump is ON, run AI on every reading so it can turn off quickly.
        // Otherwise throttle to the configured interval.
        $pumpIsOn = Cache::get('pump_command') === 'ON';
        $throttleKey = 'ai_decision_throttle';

        if ($pumpIsOn || ! Cache::has($throttleKey)) {
            if (! $pumpIsOn) {
                Cache::put($throttleKey, true, now()->addMinutes($settings->ai_decision_interval_minutes));
            }
            MakePumpDecision::dispatch($reading);
        }

        // Broadcast live reading to dashboard
        SensorDataReceived::dispatch($reading);

        // Safety: always OFF when tank is empty, otherwise return last known command
        $pumpCommand = $reading->tank_status === 'EMPTY'
            ? 'OFF'
            : Cache::get('pump_command', 'OFF');

        // Track pump sessions: detect ON↔OFF transitions
        $previousCommand = Cache::get('pump_command_previous');

        if ($pumpCommand === 'ON' && $previousCommand !== 'ON') {
            PumpSession::create(['pump_on_at' => now()]);
        } elseif ($pumpCommand === 'OFF' && $previousCommand === 'ON') {
            $openSession = PumpSession::whereNull('pump_off_at')->latest('pump_on_at')->first();
            if ($openSession) {
                $openSession->update([
                    'pump_off_at' => now(),
                    'duration_seconds' => (int) $openSession->pump_on_at->diffInSeconds(now()),
                ]);
            }
        }

        Cache::put('pump_command_previous', $pumpCommand, now()->addHours(1));

        // Pump ON → poll every 3s so the AI stop command reaches the device quickly.
        // Other alert states (dry soil, empty tank) → 20s.
        // Normal → configured interval.
        if ($pumpCommand === 'ON') {
            $nextInterval = 3;
        } elseif ($reading->tank_status === 'EMPTY' || $reading->moisture_percent < $settings->moisture_threshold) {
            $nextInterval = 20;
        } else {
            $nextInterval = $settings->send_interval_seconds;
        }

        // If the dashboard requested an immediate refresh, send data again very soon
        if (Cache::pull('device_force_immediate')) {
            $nextInterval = 2;
        }

        // Store so dashboard can show the correct countdown
        Cache::put('device_next_interval', $nextInterval, now()->addHours(1));

        return response()->json(['pump' => $pumpCommand, 'next_interval' => $nextInterval]);
    }
}
