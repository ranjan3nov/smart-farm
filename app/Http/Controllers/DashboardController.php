<?php

namespace App\Http\Controllers;

use App\Models\PumpSession;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function latest(): JsonResponse
    {
        $latest = SensorReading::latest()->first();
        $lastSeenTimestamp = Cache::get('device_last_seen');

        return response()->json([
            'reading' => $latest,
            'next_interval' => (int) Cache::get('device_next_interval', 300),
            'last_seen' => $lastSeenTimestamp,
        ]);
    }

    public function __invoke(): View
    {
        $user = Auth::user();
        $plantStartedAt = $user->plant_started_at;

        $readingQuery = SensorReading::latest();
        if ($plantStartedAt) {
            $readingQuery->where('created_at', '>=', $plantStartedAt);
        }

        $latest = (clone $readingQuery)->first();
        $recentReadings = (clone $readingQuery)->limit(40)->get()->reverse()->values();
        $activityLog = (clone $readingQuery)->whereNotNull('ai_reason')->limit(10)->get();

        $lastSeenTimestamp = Cache::get('device_last_seen');
        $lastSeen = $lastSeenTimestamp ? Carbon::createFromTimestamp($lastSeenTimestamp) : null;

        // Scope pump sessions to the current plant period
        $sessionQuery = PumpSession::whereNotNull('pump_off_at');
        if ($plantStartedAt) {
            $sessionQuery->where('pump_on_at', '>=', $plantStartedAt);
        }

        $pumpSessions = (clone $sessionQuery)->latest('pump_on_at')->limit(5)->get();

        // Drying trend: find how long it took soil to go dry after last completed irrigation
        $lastSession = (clone $sessionQuery)->latest('pump_on_at')->first();
        $dryingHours = null;
        $nextIrrigationEstimate = null;

        if ($lastSession) {
            $threshold = (int) config('farm.moisture_threshold', 30);
            $dryReading = SensorReading::where('created_at', '>', $lastSession->pump_off_at)
                ->whereRaw('ROUND((1 - moisture / 4095) * 100) <= ?', [$threshold])
                ->oldest()
                ->first();

            if ($dryReading) {
                $dryingHours = round($lastSession->pump_off_at->diffInMinutes($dryReading->created_at) / 60, 1);
                $nextIrrigationEstimate = $lastSession->pump_off_at->addHours($dryingHours);
            }
        }

        return view('dashboard', [
            'latest' => $latest,
            'recentReadings' => $recentReadings,
            'activityLog' => $activityLog,
            'lastSeen' => $lastSeen,
            'farmName' => config('farm.name'),
            'plantName' => $user->plant_name,
            'plantStartedAt' => $plantStartedAt,
            'pumpSessions' => $pumpSessions,
            'dryingHours' => $dryingHours,
            'nextIrrigationEstimate' => $nextIrrigationEstimate,
            'nextInterval' => (int) Cache::get('device_next_interval', 300),
        ]);
    }
}
