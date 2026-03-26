<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PumpOverrideController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $command = $request->input('command') === 'ON' ? 'ON' : 'OFF';

        // Store the override — ESP32 will receive it on next poll
        Cache::put('pump_command', $command, now()->addHour());

        return back()->with('success', "Pump manually set to {$command}.");
    }
}
