@extends('layouts.app')
@section('title', 'How It Works')

@section('content')
<div class="p-6 max-w-4xl flex flex-col gap-8">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-white">How It Works</h1>
        <p class="text-sm text-gray-500 mt-0.5">End-to-end architecture, API contracts, sensor formulas, and configuration reference.</p>
    </div>

    {{-- System Overview --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <h2 class="text-sm font-semibold text-white">System Overview</h2>
        <p class="text-sm text-gray-400 leading-relaxed">
            Smart Plant is a closed-loop IoT system that monitors a single plant and automatically controls a water pump.
            An ESP32 microcontroller reads four sensors and POSTs raw data to this Laravel web app.
            Laravel stores the reading, dispatches an AI job (throttled), broadcasts live updates to the dashboard via WebSockets,
            and returns a pump command back to the device in the same HTTP response.
        </p>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-0 mt-2 flex-wrap">
            @php
                $steps = [
                    ['label' => 'ESP32', 'sub' => 'reads sensors', 'color' => 'text-amber-400 bg-amber-500/10'],
                    ['label' => 'POST /api/sensor-data', 'sub' => 'adaptive interval', 'color' => 'text-gray-500 bg-gray-800', 'arrow' => true],
                    ['label' => 'Laravel', 'sub' => 'stores + dispatches AI job', 'color' => 'text-red-400 bg-red-500/10', 'arrow' => true],
                    ['label' => 'AI Endpoint', 'sub' => 'returns pump decision', 'color' => 'text-purple-400 bg-purple-500/10', 'arrow' => true],
                    ['label' => 'Reverb', 'sub' => 'broadcasts to browser', 'color' => 'text-blue-400 bg-blue-500/10', 'arrow' => true],
                    ['label' => 'Dashboard', 'sub' => 'updates in real time', 'color' => 'text-emerald-400 bg-emerald-500/10', 'arrow' => true],
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
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
                    ['name' => 'ESP32 Dev Module', 'pin' => null, 'desc' => 'Main microcontroller. Connects to WiFi via WiFiManager, hosts a debug web UI on port 80, and manages all sensor reads and HTTP POSTs.', 'color' => 'bg-amber-500/10 text-amber-400'],
                    ['name' => 'Capacitive Soil Sensor', 'pin' => 'GPIO 34 (ADC)', 'desc' => 'Reads 0–4095. High (~4095) = bone dry, Low (~0) = fully wet. Raw value is sent as-is; server converts to moisture %.', 'color' => 'bg-amber-500/10 text-amber-400'],
                    ['name' => 'Rain Sensor Module', 'pin' => 'GPIO 32 (ADC)', 'desc' => 'Same polarity as soil — High = dry/no rain, Low = raining. Server inverts to get rain %. Detects active rainfall, not residual moisture.', 'color' => 'bg-blue-500/10 text-blue-400'],
                    ['name' => 'DHT11', 'pin' => 'GPIO 4 (Digital)', 'desc' => 'Temperature (°C) and air humidity (%). If the read returns NaN the entire send cycle is skipped — no bad data is ever stored.', 'color' => 'bg-orange-500/10 text-orange-400'],
                    ['name' => 'HC-SR04 Ultrasonic', 'pin' => 'Trig: GPIO 5 · Echo: GPIO 18', 'desc' => 'Measures distance from sensor to water surface. Mounted at top of tank. Distance > tank threshold = EMPTY. 30ms pulse timeout prevents lockup on air pockets.', 'color' => 'bg-teal-500/10 text-teal-400'],
                    ['name' => 'Relay + Water Pump', 'pin' => 'GPIO 25 (Relay IN)', 'desc' => 'Relay controls pump. Starts LOW (off) on boot. Pump only runs if Laravel says ON and local tank check passes — two independent safety layers.', 'color' => 'bg-emerald-500/10 text-emerald-400'],
                ];
            @endphp
            @foreach($hardware as $hw)
                <div class="flex gap-3">
                    <div class="rounded-full shrink-0 mt-1.5 w-0.5 self-stretch {{ $hw['color'] }}"></div>
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

    {{-- ESP32 → Laravel API --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-5">
        <div>
            <h2 class="text-sm font-semibold text-white">ESP32 → Laravel: Sensor Data</h2>
            <p class="text-xs text-gray-500 mt-0.5">Every send cycle the device POSTs raw sensor values and reads back the pump command.</p>
        </div>

        {{-- Boot config fetch --}}
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 rounded font-mono text-xs font-medium">GET</span>
                <code class="text-xs text-gray-300 font-mono">/api/config</code>
                <span class="text-xs text-gray-600">— called once on boot</span>
            </div>
            <p class="text-xs text-gray-500">Fetches device configuration. Result is stored in LittleFS so it survives reboots without network. Change settings in the web app, then hit "Reload Config" on the ESP32 debug UI to apply without re-flashing.</p>
            <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono leading-relaxed overflow-x-auto"><span class="text-gray-500">// Response</span>
<span class="text-gray-500">{</span>
  <span class="text-emerald-400">"tank_height_cm"</span>: <span class="text-amber-300">20</span>   <span class="text-gray-600">// used for EMPTY detection on the device</span>
<span class="text-gray-500">}</span></pre>
        </div>

        <div class="border-t border-gray-800"></div>

        {{-- Sensor POST --}}
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 rounded font-mono text-xs font-medium">POST</span>
                <code class="text-xs text-gray-300 font-mono">/api/sensor-data</code>
                <span class="text-xs text-gray-600">— repeated every cycle</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                <div class="flex flex-col gap-1.5">
                    <p class="text-xs font-medium text-gray-400">Request body</p>
                    <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono leading-relaxed h-full overflow-x-auto"><span class="text-gray-500">{</span>
  <span class="text-emerald-400">"moisture"</span>:    <span class="text-amber-300">3412</span>,    <span class="text-gray-600">// ADC 0–4095</span>
  <span class="text-emerald-400">"rain"</span>:         <span class="text-amber-300">4080</span>,    <span class="text-gray-600">// ADC 0–4095</span>
  <span class="text-emerald-400">"temp"</span>:         <span class="text-amber-300">24.5</span>,    <span class="text-gray-600">// °C</span>
  <span class="text-emerald-400">"humidity"</span>:     <span class="text-amber-300">62.0</span>,    <span class="text-gray-600">// %</span>
  <span class="text-emerald-400">"water_dist"</span>:   <span class="text-amber-300">5.2</span>,     <span class="text-gray-600">// cm from sensor to water</span>
  <span class="text-emerald-400">"tank_status"</span>:  <span class="text-green-300">"OK"</span>     <span class="text-gray-600">// "OK" | "EMPTY" (ESP32 decides)</span>
<span class="text-gray-500">}</span></pre>
                </div>
                <div class="flex flex-col gap-1.5">
                    <p class="text-xs font-medium text-gray-400">Response</p>
                    <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono leading-relaxed h-full overflow-x-auto"><span class="text-gray-500">{</span>
  <span class="text-emerald-400">"pump"</span>:          <span class="text-green-300">"ON"</span>,  <span class="text-gray-600">// "ON" | "OFF"</span>
  <span class="text-emerald-400">"next_interval"</span>: <span class="text-amber-300">20</span>    <span class="text-gray-600">// seconds until next POST</span>
                                  <span class="text-gray-600">// 20 = alert state</span>
                                  <span class="text-gray-600">// 300 = normal state</span>
<span class="text-gray-500">}</span></pre>
                </div>
            </div>

            <div class="bg-gray-800/50 rounded-xl p-3 flex flex-col gap-1.5 mt-1">
                <p class="text-xs font-medium text-gray-300">Adaptive interval logic</p>
                <p class="text-xs text-gray-500">
                    Server returns <code class="bg-gray-800 px-1 rounded text-gray-300">next_interval: 20</code> when any alert condition is true:
                    pump is ON, tank is EMPTY, or soil moisture is below the dry threshold.
                    Otherwise returns <code class="bg-gray-800 px-1 rounded text-gray-300">300</code> so the device doesn't hammer the server when everything is fine.
                    The ESP32 stores this value and uses it for the next sleep delay.
                </p>
            </div>

            <div class="bg-gray-800/50 rounded-xl p-3 flex flex-col gap-1.5">
                <p class="text-xs font-medium text-gray-300">Pump session tracking</p>
                <p class="text-xs text-gray-500">
                    Laravel compares the current pump command against <code class="bg-gray-800 px-1 rounded text-gray-300">pump_command_previous</code> in cache.
                    <strong class="text-gray-300">OFF→ON</strong> transition creates a new <code class="bg-gray-800 px-1 rounded text-gray-300">PumpSession</code> record with <code class="bg-gray-800 px-1 rounded text-gray-300">pump_on_at</code>.
                    <strong class="text-gray-300">ON→OFF</strong> closes the open session with <code class="bg-gray-800 px-1 rounded text-gray-300">pump_off_at</code> and calculates <code class="bg-gray-800 px-1 rounded text-gray-300">duration_seconds</code>.
                </p>
            </div>
        </div>
    </div>

    {{-- Sensor calculations --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <div>
            <h2 class="text-sm font-semibold text-white">Sensor Calculations</h2>
            <p class="text-xs text-gray-500 mt-0.5">ESP32 sends raw ADC values. All % conversions happen server-side in <code class="bg-gray-800 px-1 rounded text-gray-300">SensorReading.php</code> as Eloquent accessors.</p>
        </div>

        <div class="flex flex-col gap-0">
            @php
                $formulas = [
                    ['label' => 'moisture_percent', 'formula' => '(1 − moisture / 4095) × 100', 'note' => 'Inverted — sensor outputs HIGH (~4095) when bone dry, LOW when wet'],
                    ['label' => 'rain_percent', 'formula' => '(1 − rain / 4095) × 100', 'note' => 'Same inversion — 0% = no rain, 100% = heavy rain'],
                    ['label' => 'tank_fill_percent', 'formula' => '(1 − water_dist / tank_height_cm) × 100', 'note' => 'Clamped 0–100. Distance ≤ 0 or > tank_height_cm = 0%'],
                    ['label' => 'tank_status (server)', 'formula' => 'tank_fill_percent == 0 → "EMPTY"', 'note' => 'Derived from the stored water_dist and configured tank_height_cm'],
                    ['label' => 'tank_status (device)', 'formula' => 'water_dist > tankHeightCm || water_dist ≤ 0', 'note' => 'Checked on ESP32 before the POST. Loaded from /api/config on boot.'],
                ];
            @endphp
            @foreach($formulas as $f)
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-4 py-3 border-b border-gray-800/60 last:border-0">
                    <code class="text-xs text-amber-400 font-mono sm:w-44 shrink-0">{{ $f['label'] }}</code>
                    <code class="text-xs text-emerald-400 font-mono bg-gray-950 px-2 py-1 rounded">{{ $f['formula'] }}</code>
                    <span class="text-xs text-gray-500">{{ $f['note'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- AI decision flow --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-5">
        <div>
            <h2 class="text-sm font-semibold text-white">AI Decision Flow</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                The <code class="bg-gray-800 px-1 rounded text-gray-300">MakePumpDecision</code> job is dispatched at most once per configured interval (default 5 min).
                Between AI calls the last known pump command is reused — the device always gets a fast response.
            </p>
        </div>

        {{-- Pump decision steps --}}
        <div class="flex flex-col gap-4">
            @php
                $decisionSteps = [
                    ['num' => '1', 'color' => 'bg-purple-500/20 text-purple-400', 'title' => 'Sensor data arrives', 'body' => 'ESP32 POSTs raw ADC values. Laravel validates, stores the reading, caches device_last_seen, and immediately returns the last known pump command — no waiting for AI.'],
                    ['num' => '2', 'color' => 'bg-purple-500/20 text-purple-400', 'title' => 'AI job is throttled', 'body' => 'The ai_decision_throttle cache key gates dispatches. If the key exists (not expired), the job is skipped and the existing pump command continues. If expired, the job is dispatched and the key is reset.'],
                    ['num' => '3', 'color' => 'bg-purple-500/20 text-purple-400', 'title' => 'AI receives full context', 'body' => 'The job builds a structured payload with sensor data, live weather (if lat/long configured), and context. Your AI endpoint must return {"pump": "ON"|"OFF", "reason": "..."}.'],
                    ['num' => '4', 'color' => 'bg-red-500/20 text-red-400', 'title' => 'Safety override (server-side)', 'body' => 'If tank_status = EMPTY, the AI response is overridden to pump: OFF regardless of what was returned. This cannot be bypassed by the AI.'],
                    ['num' => '5', 'color' => 'bg-red-500/20 text-red-400', 'title' => 'Safety override (device-side)', 'body' => 'Even if the server says ON, the ESP32 will not energise the relay if its local ultrasonic check reports the tank as empty. Both checks must independently pass.'],
                    ['num' => '6', 'color' => 'bg-emerald-500/20 text-emerald-400', 'title' => 'Decision stored and broadcast', 'body' => 'pump_command and pump_command_at are written to cache. The reading row is updated with the AI reason. A SensorDataReceived event is broadcast via Reverb to update the dashboard in real time.'],
                    ['num' => '7', 'color' => 'bg-blue-500/20 text-blue-400', 'title' => 'Manual override', 'body' => 'Dashboard Force ON / Force OFF buttons write directly to pump_command cache with a 1-hour TTL. AI resumes on its next scheduled cycle automatically.'],
                ];
            @endphp
            @foreach($decisionSteps as $s)
                <div class="flex gap-4">
                    <span class="w-6 h-6 rounded-full {{ $s['color'] }} flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">{{ $s['num'] }}</span>
                    <div>
                        <p class="text-sm font-medium text-white">{{ $s['title'] }}</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $s['body'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-gray-800 pt-5 flex flex-col gap-4">
            <div>
                <h3 class="text-sm font-semibold text-white">AI API Contract</h3>
                <p class="text-xs text-gray-500 mt-0.5">Your endpoint must accept <code class="bg-gray-800 px-1 rounded text-gray-300">POST</code> with <code class="bg-gray-800 px-1 rounded text-gray-300">Content-Type: application/json</code> and return HTTP 200. Any non-200 is treated as unavailable — pump defaults to OFF.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1.5">
                    <p class="text-xs font-medium text-gray-400">Request payload (your AI receives this)</p>
                    <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono leading-relaxed overflow-x-auto"><span class="text-gray-500">{</span>
  <span class="text-emerald-400">"sensor"</span>: <span class="text-gray-500">{</span>
    <span class="text-blue-300">"moisture_percent"</span>:  <span class="text-amber-300">17</span>,
    <span class="text-blue-300">"soil_status"</span>:       <span class="text-green-300">"Dry — needs water"</span>,
    <span class="text-blue-300">"rain_percent"</span>:      <span class="text-amber-300">0</span>,
    <span class="text-blue-300">"rain_status"</span>:       <span class="text-green-300">"No rain"</span>,
    <span class="text-blue-300">"temp_celsius"</span>:      <span class="text-amber-300">24.5</span>,
    <span class="text-blue-300">"humidity_percent"</span>:  <span class="text-amber-300">62</span>,
    <span class="text-blue-300">"tank_status"</span>:       <span class="text-green-300">"OK"</span>,
    <span class="text-blue-300">"tank_fill_percent"</span>: <span class="text-amber-300">74</span>
  <span class="text-gray-500">},</span>
  <span class="text-emerald-400">"weather"</span>: <span class="text-gray-500">{</span>          <span class="text-gray-600">// null if no lat/long</span>
    <span class="text-blue-300">"temp_current"</span>:            <span class="text-amber-300">23.1</span>,
    <span class="text-blue-300">"humidity_current"</span>:        <span class="text-amber-300">58</span>,
    <span class="text-blue-300">"precipitation_now"</span>:       <span class="text-amber-300">0</span>,
    <span class="text-blue-300">"wind_speed"</span>:              <span class="text-amber-300">12.4</span>,
    <span class="text-blue-300">"description"</span>:             <span class="text-green-300">"Clear sky"</span>,
    <span class="text-blue-300">"rain_probability_next_6h"</span>: <span class="text-amber-300">5</span>,
    <span class="text-blue-300">"temp_next_6h"</span>: <span class="text-gray-500">[</span><span class="text-amber-300">24.1</span>,<span class="text-amber-300">25.0</span>,<span class="text-amber-300">26.3</span>,<span class="text-amber-300">25.8</span>,<span class="text-amber-300">24.5</span>,<span class="text-amber-300">23.0</span><span class="text-gray-500">]</span>
  <span class="text-gray-500">},</span>
  <span class="text-emerald-400">"context"</span>: <span class="text-gray-500">{</span>
    <span class="text-blue-300">"plant_name"</span>:          <span class="text-green-300">"Tomatoes"</span>,
    <span class="text-blue-300">"last_pump_command"</span>:    <span class="text-green-300">"OFF"</span>,
    <span class="text-blue-300">"last_pump_command_at"</span>: <span class="text-green-300">"2026-03-26T08:00:00Z"</span>,
    <span class="text-blue-300">"moisture_threshold"</span>:   <span class="text-amber-300">30</span>
  <span class="text-gray-500">}</span>
<span class="text-gray-500">}</span></pre>
                </div>
                <div class="flex flex-col gap-1.5">
                    <p class="text-xs font-medium text-gray-400">Expected response (your AI returns this)</p>
                    <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono leading-relaxed overflow-x-auto"><span class="text-gray-500">{</span>
  <span class="text-emerald-400">"pump"</span>:   <span class="text-green-300">"ON"</span>,
  <span class="text-emerald-400">"reason"</span>: <span class="text-green-300">"Soil at 17% — below the 30%
           threshold. No rain expected
           in the next 6 hours."</span>
<span class="text-gray-500">}</span>

<span class="text-gray-600">// "pump" → required, "ON" or "OFF"
// "reason" → required, shown on
//   dashboard activity log</span></pre>

                    <div class="bg-red-500/5 border border-red-500/20 rounded-xl p-3 flex flex-col gap-1.5 mt-auto">
                        <p class="text-xs font-medium text-red-400">Server-side safety rules</p>
                        <ul class="flex flex-col gap-1.5 text-xs text-gray-500">
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0 mt-1"></span>
                                <span>If <code class="bg-gray-800 px-1 rounded text-gray-300">tank_status = EMPTY</code>, pump is forced <strong class="text-white">OFF</strong> — AI response is ignored</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 shrink-0 mt-1"></span>
                                <span>Non-200 HTTP or timeout → pump stays at last known command (defaults <strong class="text-white">OFF</strong>)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0 mt-1"></span>
                                <span>AI called at most once per decision interval — last command replayed between calls</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cache keys reference --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-4">
        <h2 class="text-sm font-semibold text-white">Cache Keys Reference</h2>
        <p class="text-xs text-gray-500">All runtime state lives in Laravel's cache (Redis/file). These are cleared on Plant Reset.</p>
        <div class="flex flex-col gap-0">
            @php
                $keys = [
                    ['key' => 'pump_command', 'ttl' => '1 hour', 'desc' => 'Last pump decision — "ON" or "OFF". Returned to ESP32 on every POST. Overwritten by AI job and manual dashboard overrides.'],
                    ['key' => 'pump_command_at', 'ttl' => '1 hour', 'desc' => 'ISO timestamp of when pump_command was last written. Shown on dashboard as "last decision time".'],
                    ['key' => 'pump_command_previous', 'ttl' => '1 hour', 'desc' => 'Previous cycle\'s command. Compared to current command to detect ON↔OFF transitions for pump session tracking.'],
                    ['key' => 'ai_decision_throttle', 'ttl' => 'AI interval', 'desc' => 'Boolean gate that prevents dispatching MakePumpDecision more than once per interval. Expires automatically; no value, just presence.'],
                    ['key' => 'device_last_seen', 'ttl' => '5 min', 'desc' => 'Unix timestamp of the last successful POST from the ESP32. Dashboard uses this to calculate "last update" and show the connection-lost banner when it expires.'],
                ];
            @endphp
            @foreach($keys as $k)
                <div class="flex flex-col sm:flex-row gap-1 sm:gap-6 py-3 border-b border-gray-800/60 last:border-0">
                    <div class="sm:w-56 shrink-0 flex items-baseline gap-2 flex-wrap">
                        <code class="text-xs text-amber-400 font-mono">{{ $k['key'] }}</code>
                        <span class="text-xs text-gray-600">TTL: {{ $k['ttl'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">{{ $k['desc'] }}</p>
                </div>
            @endforeach
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
                    ['env' => 'FARM_NAME', 'default' => 'My Plant', 'desc' => 'Display name shown in the dashboard header.'],
                    ['env' => 'FARM_TANK_HEIGHT_CM', 'default' => '20', 'desc' => 'Distance (cm) from ultrasonic sensor to water surface when tank is empty. Sent to ESP32 via GET /api/config on boot — no re-flash needed when you change the tank.'],
                    ['env' => 'FARM_MOISTURE_THRESHOLD', 'default' => '30', 'desc' => 'Soil moisture % below which the alert interval (20s) is triggered and AI is nudged to water. AI makes the final call — this does not force the pump.'],
                    ['env' => 'FARM_AI_DECISION_INTERVAL', 'default' => '5', 'desc' => 'Minutes between AI calls. Sensor data is stored every cycle regardless. Increase to reduce AI API costs; decrease for faster response to soil changes.'],
                    ['env' => 'FARM_AI_ENDPOINT', 'default' => 'null', 'desc' => 'URL of your AI model endpoint. Leave blank to disable AI — pump will default to OFF until manually overridden.'],
                    ['env' => 'FARM_AI_API_KEY', 'default' => 'null', 'desc' => 'Bearer token sent as Authorization header with every AI request. Optional — leave blank if your endpoint is unprotected.'],
                    ['env' => 'FARM_LATITUDE / FARM_LONGITUDE', 'default' => 'null', 'desc' => 'GPS coordinates for Open-Meteo weather forecast. Sent in the AI payload as "weather" context. Leave blank to omit weather data entirely.'],
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
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col gap-5">
        <h2 class="text-sm font-semibold text-white">ESP32 First-Time Setup</h2>

        <ol class="flex flex-col gap-4">
            @php
                $setupSteps = [
                    ['title' => 'Install Arduino libraries', 'body' => 'In Arduino IDE: WiFiManager (tzapu), ArduinoJson (Benoit Blanchon), DHT sensor library (Adafruit). Select board "ESP32 Dev Module".'],
                    ['title' => 'Flash the firmware', 'body' => 'Open smart-plant-iot.ino and upload. The DEFAULT_API_URL is hardcoded as http://smart-farm.test/api/sensor-data — update it if your server is on a different IP before flashing, or change it via the debug UI after.'],
                    ['title' => 'Connect to the setup hotspot', 'body' => 'On first boot with no saved WiFi, the ESP32 broadcasts a hotspot named "Smart-Plant". Connect to it from your phone or laptop — a captive portal opens automatically.'],
                    ['title' => 'Configure WiFi via portal', 'body' => 'Select your WiFi network, enter the password, and save. Credentials are stored in flash. On future boots the device connects automatically — no portal unless WiFi is unreachable.'],
                    ['title' => 'Verify via debug UI', 'body' => 'Find the ESP32\'s IP in Serial Monitor (115200 baud). Open it in a browser. You\'ll see live sensor readings, last sync status, current send interval, and tank threshold. Update the API URL here if needed.'],
                    ['title' => 'Confirm on dashboard', 'body' => 'Within one send cycle you should see the first reading appear on the dashboard and the "last update" timer reset to 0 seconds. If not, check the debug UI for HTTP errors.'],
                ];
            @endphp
            @foreach($setupSteps as $i => $step)
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
