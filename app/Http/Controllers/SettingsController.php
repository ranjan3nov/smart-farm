<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        return view('settings');
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Auth::user()->update(['plant_name' => $data['plant_name'] ?? null]);

        $this->writeEnv([
            'FARM_NAME' => $data['farm_name'],
            'FARM_LATITUDE' => $data['latitude'] ?? '',
            'FARM_LONGITUDE' => $data['longitude'] ?? '',
            'FARM_TANK_HEIGHT_CM' => $data['tank_height_cm'],
            'FARM_MOISTURE_THRESHOLD' => $data['moisture_threshold'],
            'FARM_AI_DECISION_INTERVAL' => $data['ai_decision_interval'],
            'FARM_AI_ENDPOINT' => $data['ai_endpoint'] ?? '',
            'FARM_AI_API_KEY' => $data['ai_api_key'] ?? '',
        ]);

        Artisan::call('config:clear');

        return back()->with('success', 'Settings saved.');
    }

    public function resetPlant(): RedirectResponse
    {
        Auth::user()->update(['plant_started_at' => now()]);

        return back()->with('success', 'Plant reset. History will now be tracked from today.');
    }

    /** @param array<string, string|int|float|null> $values */
    private function writeEnv(array $values): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escapedValue = str_contains((string) $value, ' ') ? "\"{$value}\"" : (string) $value;
            $line = "{$key}={$escapedValue}";

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", $line, $content);
            } else {
                $content .= "\n{$line}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
