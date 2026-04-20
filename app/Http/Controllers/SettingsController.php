<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\FarmSetting;
use App\Services\PlantApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(PlantApiClient $plants): View
    {
        return view('settings', [
            'settings' => FarmSetting::current(),
            'plants' => $plants->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Auth::user()->update(['plant_name' => $data['plant_name'] ?? null]);

        FarmSetting::updateSettings([
            'name' => $data['farm_name'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'tank_height_cm' => $data['tank_height_cm'],
            'moisture_threshold' => $data['moisture_threshold'],
            'moisture_max' => $data['moisture_max'],
            'ai_decision_interval_minutes' => $data['ai_decision_interval'],
            'send_interval_seconds' => $data['send_interval'],
        ]);

        return back()->with('success', 'Settings saved.');
    }

    public function resetPlant(): RedirectResponse
    {
        Auth::user()->update(['plant_started_at' => now()]);

        // Clear pump state so the old plant's last command doesn't carry over
        Cache::forget('pump_command');
        Cache::forget('pump_command_previous');
        Cache::forget('pump_command_at');
        Cache::forget('ai_decision_throttle');

        return back()->with('success', 'Plant reset. History and pump state cleared from today.');
    }
}
