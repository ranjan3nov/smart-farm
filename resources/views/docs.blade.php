@extends('layouts.app')
@section('title', 'How It Works')

@section('content')
<div class="p-6 max-w-4xl flex flex-col gap-8">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-white">How It Works</h1>
        <p class="text-sm text-gray-500 mt-0.5">Architecture, data flow, sensors, and configuration reference.</p>
    </div>

    {{-- Data flow diagram --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <h2 class="text-sm font-semibold text-white">System Overview</h2>
        <p class="text-sm text-gray-400 leading-relaxed">
            Smart Plant is a closed-loop IoT system that monitors a single plant and automatically controls a water pump.
            An ESP32 microcontroller reads four sensors every 20 seconds and sends the data to this Laravel web app,
            which runs an AI decision engine and broadcasts live updates to the dashboard.
        </p>

        {{-- Flow steps --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-0 mt-2">
            @php
                $steps = [
                    ['icon' => 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18', 'label' => 'ESP32', 'sub' => 'reads sensors', 'color' => 'text-amber-400 bg-amber-500/10'],
                    ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'label' => 'POST /api/sensor-data', 'sub' => 'every 20 seconds', 'color' => 'text-gray-500 bg-gray-800', 'arrow' => true],
                    ['icon' => 'M5 12h14M12 5l7 7-7 7', 'label' => 'Laravel', 'sub' => 'stores + dispatches AI job', 'color' => 'text-red-400 bg-red-500/10'],
                    ['icon' => 'M5 12h14M12 5l7 7-7 7', 'label' => 'AI Endpoint', 'sub' => 'returns pump decision', 'color' => 'text-purple-400 bg-purple-500/10', 'arrow' => true],
                    ['icon' => 'M5 12h14M12 5l7 7-7 7', 'label' => 'Reverb', 'sub' => 'broadcasts live to browser', 'color' => 'text-blue-400 bg-blue-500/10', 'arrow' => true],
                    ['icon' => 'M5 12h14M12 5l7 7-7 7', 'label' => 'Dashboard', 'sub' => 'updates in real time', 'color' => 'text-emerald-400 bg-emerald-500/10', 'arrow' => true],
                ];
            @endphp
            @foreach($steps as $step)
                @if(!empty($step['arrow']))
                    <div class="hidden sm:flex items-center px-1 text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                @endif
                <div class="flex sm:flex-col items-center gap-3 sm:gap-2 sm:text-center">
                    <div class="w-9 h-9 rounded-xl {{ $step['color'] }} flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-white">{{ $step['label'] }}</div>
                        <div class="text-xs text-gray-500">{{ $step['sub'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Hardware --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-5">
        <h2 class="text-sm font-semibold text-white">Hardware Components</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
                $hardware = [
                    ['name' => 'ESP32', 'pin' => null, 'desc' => 'Main microcontroller. Connects to WiFi, reads all sensors, and POSTs data to the Laravel API every 20 seconds.', 'color' => 'bg-amber-500/10 text-amber-400'],
                    ['name' => 'Soil Moisture Sensor', 'pin' => 'GPIO 34 (ADC)', 'desc' => 'Capacitive or resistive sensor. Outputs analog value 0–4095. High = dry, Low = wet. Converted to 0–100% on the server.', 'color' => 'bg-amber-500/10 text-amber-400'],
                    ['name' => 'Rain Sensor Module', 'pin' => 'GPIO 32 (ADC)', 'desc' => 'Analog rain detection. Same polarity as soil sensor — High (~4095) = dry/no rain, Low = raining. Server inverts to 0–100%.', 'color' => 'bg-blue-500/10 text-blue-400'],
                    ['name' => 'DHT11', 'pin' => 'GPIO 4 (Digital)', 'desc' => 'Temperature and air humidity sensor. Data is validated for NaN before sending — a bad read skips the cycle entirely.', 'color' => 'bg-orange-500/10 text-orange-400'],
                    ['name' => 'HC-SR04 Ultrasonic', 'pin' => 'Trig: GPIO 5 · Echo: GPIO 18', 'desc' => 'Measures distance to water surface in cm. Mounted at the top of the tank. Distance > 20 cm = tank empty. 30ms timeout prevents false readings.', 'color' => 'bg-teal-500/10 text-teal-400'],
                    ['name' => 'Relay + Water Pump', 'pin' => 'GPIO 25 (Relay IN)', 'desc' => 'Relay switches the pump on/off. Starts LOW (pump off) on boot. Pump only turns ON if Laravel says ON and the tank is not empty — checked on both sides.', 'color' => 'bg-emerald-500/10 text-emerald-400'],
                ];
            @endphp
            @foreach($hardware as $hw)
                <div class="flex gap-3">
                    <div class="w-2 rounded-full {{ $hw['color'] }} shrink-0 mt-0.5" style="width:3px"></div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-white">{{ $hw['name'] }}</span>
                            @if($hw['pin'])
                                <code class="text-xs px-1.5 py-0.5 bg-gray-800 text-gray-400 rounded font-mono">{{ $hw['pin'] }}</code>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $hw['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Sensor formulas --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <h2 class="text-sm font-semibold text-white">Sensor Calculations</h2>
        <p class="text-xs text-gray-500">The ESP32 sends raw ADC values (0–4095). All percentage conversions happen server-side in <code class="bg-gray-800 px-1 rounded text-gray-300">SensorReading.php</code>.</p>

        <div class="flex flex-col gap-3">
            @php
                $formulas = [
                    ['label' => 'Soil Moisture %', 'formula' => '(1 − raw / 4095) × 100', 'note' => 'Inverted — 4095 = bone dry (0%), 0 = fully wet (100%)'],
                    ['label' => 'Rain %', 'formula' => '(1 − raw / 4095) × 100', 'note' => 'Same inversion as soil — sensor outputs high when dry'],
                    ['label' => 'Tank Fill %', 'formula' => '(1 − distance / tank_height_cm) × 100', 'note' => 'distance > tank_height_cm or ≤ 0 → 0% (empty). tank_height_cm must match ESP32 threshold (20 cm)'],
                    ['label' => 'Tank Empty (ESP32)', 'formula' => 'distance > 20.0 || distance ≤ 0', 'note' => 'Hardcoded in firmware — requires re-flash to change. Keep FARM_TANK_HEIGHT_CM in Settings equal to this value so the fill % display stays accurate.'],
                ];
            @endphp
            @foreach($formulas as $f)
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-4 py-2.5 border-b border-gray-800/60 last:border-0">
                    <span class="text-xs font-medium text-gray-300 sm:w-40 shrink-0">{{ $f['label'] }}</span>
                    <code class="text-xs text-emerald-400 font-mono bg-gray-950 px-2 py-1 rounded">{{ $f['formula'] }}</code>
                    <span class="text-xs text-gray-500">{{ $f['note'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Pump decision logic --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <h2 class="text-sm font-semibold text-white">Pump Decision Logic</h2>

        <div class="flex flex-col gap-3 text-sm">
            <div class="flex gap-3">
                <span class="w-5 h-5 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">1</span>
                <div>
                    <p class="text-gray-300 font-medium">ESP32 POSTs sensor data</p>
                    <p class="text-xs text-gray-500 mt-0.5">Every 20 seconds. Immediately gets back the last known pump command from the cache so it stays responsive even between AI decisions.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="w-5 h-5 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">2</span>
                <div>
                    <p class="text-gray-300 font-medium">AI job is throttled</p>
                    <p class="text-xs text-gray-500 mt-0.5">The <code class="bg-gray-800 px-1 rounded">MakePumpDecision</code> job dispatches at most once per configured interval (default: 5 minutes) to avoid hammering your AI endpoint. Between calls, the last known command is reused.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="w-5 h-5 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">3</span>
                <div>
                    <p class="text-gray-300 font-medium">AI receives full context</p>
                    <p class="text-xs text-gray-500 mt-0.5">Your AI endpoint gets sensor readings, live weather forecast (if lat/long configured), and context including <code class="bg-gray-800 px-1 rounded">plant_name</code>, last pump state, and the dry soil threshold.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="w-5 h-5 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">!</span>
                <div>
                    <p class="text-gray-300 font-medium">Safety overrides (two layers)</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <strong class="text-gray-300">Server side:</strong> if <code class="bg-gray-800 px-1 rounded">tank_status = EMPTY</code>, pump is forced OFF regardless of AI decision.<br>
                        <strong class="text-gray-300">Device side:</strong> if ultrasonic distance &gt; 20 cm, pump relay stays LOW regardless of what the server returns.
                        Both checks must pass for the pump to run.
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">4</span>
                <div>
                    <p class="text-gray-300 font-medium">Manual override</p>
                    <p class="text-xs text-gray-500 mt-0.5">Dashboard override buttons write directly to the pump cache for 1 hour. The AI resumes control on its next decision cycle. Pump sessions are tracked on every ON→OFF transition.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Configuration reference --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-white">Configuration Reference</h2>
            <a href="{{ route('settings') }}" class="text-xs text-emerald-400 hover:text-emerald-300 transition-colors">Open Settings →</a>
        </div>

        <div class="flex flex-col gap-0">
            @php
                $config = [
                    ['env' => 'FARM_NAME', 'default' => 'My Plant', 'desc' => 'Display name shown in the dashboard header'],
                    ['env' => 'FARM_TANK_HEIGHT_CM', 'default' => '20', 'desc' => 'Controls the tank fill % shown on the dashboard. Must equal the empty threshold in the ESP32 firmware (default 20 cm). The firmware value is hardcoded — if you change the physical tank, update both here and in the .ino file and re-flash.'],
                    ['env' => 'FARM_MOISTURE_THRESHOLD', 'default' => '30', 'desc' => 'Soil moisture % below which AI is nudged to water. Does not force the pump — AI makes the final call.'],
                    ['env' => 'FARM_AI_DECISION_INTERVAL', 'default' => '5', 'desc' => 'Minutes between AI calls. Sensor data is still stored every 20 seconds regardless.'],
                    ['env' => 'FARM_AI_ENDPOINT', 'default' => 'null', 'desc' => 'Your AI model endpoint. Leave blank to disable AI — pump defaults to OFF.'],
                    ['env' => 'FARM_AI_API_KEY', 'default' => 'null', 'desc' => 'Bearer token sent with every AI request. Optional.'],
                    ['env' => 'FARM_LATITUDE / FARM_LONGITUDE', 'default' => 'null', 'desc' => 'GPS coordinates for live weather context sent to AI. Leave blank to disable weather.'],
                ];
            @endphp
            @foreach($config as $c)
                <div class="flex flex-col sm:flex-row gap-1 sm:gap-6 py-3 border-b border-gray-800/60 last:border-0">
                    <div class="sm:w-64 shrink-0">
                        <code class="text-xs text-amber-400 font-mono">{{ $c['env'] }}</code>
                        <span class="text-xs text-gray-600 ml-2">default: {{ $c['default'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ $c['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ESP32 first-time setup --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <h2 class="text-sm font-semibold text-white">ESP32 First-Time Setup</h2>

        <ol class="flex flex-col gap-4">
            @php
                $steps = [
                    ['title' => 'Flash the firmware', 'body' => 'Open smart-plant-iot.ino in Arduino IDE. Install libraries: WiFiManager, ArduinoJson, DHT sensor library. Select board "ESP32 Dev Module" and upload.'],
                    ['title' => 'Connect to the setup hotspot', 'body' => 'On first boot with no saved WiFi, the ESP32 creates a hotspot named "Smart-Plant". Connect to it from your phone or laptop.'],
                    ['title' => 'Configure WiFi', 'body' => 'A captive portal opens at 192.168.4.1. Select your WiFi network and enter the password. The device saves credentials and connects automatically on future boots.'],
                    ['title' => 'Verify the API URL', 'body' => 'Once on your network, open the ESP32\'s IP in a browser (shown in Serial Monitor). The debug page shows live sensor readings and the current API URL. The default is http://smart-farm.test/api/sensor-data — update it if your server runs on a different IP/port.'],
                    ['title' => 'Check the dashboard', 'body' => 'Within 20 seconds of the ESP32 connecting, you should see the first reading appear on the dashboard with the update timer resetting to 0.'],
                ];
            @endphp
            @foreach($steps as $i => $step)
                <li class="flex gap-4">
                    <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">{{ $i + 1 }}</span>
                    <div>
                        <p class="text-sm font-medium text-white">{{ $step['title'] }}</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $step['body'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

</div>
@endsection
