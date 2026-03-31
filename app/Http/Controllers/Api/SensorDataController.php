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

        // Dispatch AI decision job at most once per configured interval
        $throttleKey = 'ai_decision_throttle';

        if (! Cache::has($throttleKey)) {
            Cache::put($throttleKey, true, now()->addMinutes($settings->ai_decision_interval_minutes));
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

        // Tell the device how long to wait before the next send.
        // Alert mode (20s): pump is running, soil is dry, or tank is empty.
        // Normal mode: use configured send interval.
        $isAlertState = $pumpCommand === 'ON'
            || $reading->tank_status === 'EMPTY'
            || $reading->moisture_percent < $settings->moisture_threshold;

        $nextInterval = $isAlertState ? 20 : $settings->send_interval_seconds;

        // Store so dashboard can show the correct countdown
        Cache::put('device_next_interval', $nextInterval, now()->addHours(1));

        return response()->json(['pump' => $pumpCommand, 'next_interval' => $nextInterval]);
    }
}
