@extends('layouts.app')
@section('title', 'AI Tester')

@section('content')
<div class="p-6 flex flex-col gap-6" id="ai-tester"
     data-latest="{{ $latest ? $latest->toJson() : 'null' }}"
     data-latest-url="{{ route('dashboard.latest') }}"
>

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold text-white">AI Tester</h1>
        <p class="text-sm text-gray-500 mt-0.5">Send sensor data to your AI endpoint and inspect the response in real time.</p>
    </div>

    {{-- Endpoint bar --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
        <h2 class="text-sm font-semibold text-white mb-3">Endpoint</h2>
        <div class="flex gap-2">
            <span class="flex items-center px-3 bg-gray-800 border border-gray-700 border-r-0 rounded-l-xl text-xs font-mono text-gray-500">POST</span>
            <input id="ai-url" type="url" placeholder="https://your-ai-endpoint.com/predict"
                   class="flex-1 bg-gray-800 border border-gray-700 rounded-r-xl px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-emerald-500/50 transition-colors font-mono">
        </div>
        <div id="ai-url-error" class="hidden mt-2 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-xl px-3 py-2"></div>
    </div>

    {{-- Live / Custom test --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Left: Payload --}}
        <div class="flex flex-col gap-4">

            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-white">Live Payload</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Auto-updates from device</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1.5 text-xs text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live
                        </span>
                        <button id="ai-copy-payload" type="button"
                                class="text-xs text-gray-500 hover:text-gray-300 transition-colors flex items-center gap-1 ml-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copy
                        </button>
                    </div>
                </div>
                <pre id="ai-payload" class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono overflow-x-auto whitespace-pre leading-relaxed max-h-80"></pre>
                <button id="ai-send-btn" type="button"
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Test with Live Data
                </button>
                <div id="ai-error" class="hidden text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-xl px-3 py-2"></div>
            </div>

            {{-- Sensor summary --}}
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <h2 class="text-sm font-semibold text-white mb-3">Current Reading</h2>
                <div id="reading-summary" class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-gray-950 rounded-lg px-3 py-2 flex justify-between">
                        <span class="text-gray-500">Moisture</span>
                        <span id="r-moisture" class="text-white font-mono">—</span>
                    </div>
                    <div class="bg-gray-950 rounded-lg px-3 py-2 flex justify-between">
                        <span class="text-gray-500">Rain</span>
                        <span id="r-rain" class="text-white font-mono">—</span>
                    </div>
                    <div class="bg-gray-950 rounded-lg px-3 py-2 flex justify-between">
                        <span class="text-gray-500">Temperature</span>
                        <span id="r-temp" class="text-white font-mono">—</span>
                    </div>
                    <div class="bg-gray-950 rounded-lg px-3 py-2 flex justify-between">
                        <span class="text-gray-500">Humidity</span>
                        <span id="r-humidity" class="text-white font-mono">—</span>
                    </div>
                    <div class="bg-gray-950 rounded-lg px-3 py-2 flex justify-between">
                        <span class="text-gray-500">Tank</span>
                        <span id="r-tank" class="text-white font-mono">—</span>
                    </div>
                    <div class="bg-gray-950 rounded-lg px-3 py-2 flex justify-between">
                        <span class="text-gray-500">Pump</span>
                        <span id="r-pump" class="text-white font-mono">—</span>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600" id="r-timestamp"></div>
            </div>

        </div>

        {{-- Right: Live response --}}
        <div class="flex flex-col gap-4">

            <div id="ai-response-status-bar" class="hidden bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-white">Response</h2>
                    <div class="flex items-center gap-3">
                        <span id="ai-response-status" class="text-xs font-mono"></span>
                        <span id="ai-response-latency" class="text-xs text-gray-500 font-mono"></span>
                    </div>
                </div>
                <pre id="ai-response-body" class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono overflow-x-auto whitespace-pre leading-relaxed max-h-96"></pre>
            </div>

            <div id="ai-response-empty" class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 min-h-48 text-center">
                <div class="w-10 h-10 rounded-xl bg-gray-800 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">No response yet</div>
                    <div class="text-xs text-gray-600 mt-0.5">Enter your endpoint URL and click Test with Live Data</div>
                </div>
            </div>

        </div>
    </div>

    {{-- Sample Payloads --}}
    <div>
        <div class="mb-4">
            <h2 class="text-base font-semibold text-white">Sample Payloads</h2>
            <p class="text-xs text-gray-500 mt-0.5">Edge-case scenarios — each has a Test button to call your endpoint directly.</p>
        </div>

        <div class="flex flex-col gap-4" id="sample-payload-list">

            @php
            $samples = [
                [
                    'title' => 'Sunny + Bone-Dry + Heavy Rain in 1h',
                    'key'   => 'sunny_bone_dry_rain_incoming',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 8,
                            'soil_status'       => 'Very dry',
                            'rain_percent'      => 2,
                            'rain_status'       => 'No rain',
                            'temp_celsius'      => 34.0,
                            'humidity_percent'  => 38,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 82,
                        ],
                        'weather' => [
                            'temp_current'              => 33.5,
                            'humidity_current'          => 35,
                            'precipitation_now'         => 0,
                            'wind_speed'                => 8.2,
                            'description'               => 'Sunny',
                            'rain_probability_next_6h'  => 85,
                            'temp_next_6h'              => [35, 36, 35, 30, 26, 24],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'Rainy + Waterlogged Soil + No Forecast',
                    'key'   => 'rainy_waterlogged_no_forecast',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 88,
                            'soil_status'       => 'Well watered',
                            'rain_percent'      => 91,
                            'rain_status'       => 'Heavy rain',
                            'temp_celsius'      => 22.0,
                            'humidity_percent'  => 87,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 100,
                        ],
                        'weather' => [
                            'temp_current'              => 21.0,
                            'humidity_current'          => 90,
                            'precipitation_now'         => 12.5,
                            'wind_speed'                => 5.1,
                            'description'               => 'Heavy rain',
                            'rain_probability_next_6h'  => 5,
                            'temp_next_6h'              => [22, 22, 23, 24, 25, 24],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'Extreme Heat + Critical Drought',
                    'key'   => 'extreme_heat_critical_drought',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 4,
                            'soil_status'       => 'Very dry',
                            'rain_percent'      => 0,
                            'rain_status'       => 'No rain',
                            'temp_celsius'      => 41.0,
                            'humidity_percent'  => 18,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 67,
                        ],
                        'weather' => [
                            'temp_current'              => 40.5,
                            'humidity_current'          => 15,
                            'precipitation_now'         => 0,
                            'wind_speed'                => 15.3,
                            'description'               => 'Clear sky',
                            'rain_probability_next_6h'  => 0,
                            'temp_next_6h'              => [42, 43, 42, 40, 37, 34],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'Pump Running + Tank Almost Empty',
                    'key'   => 'pump_on_tank_low',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 22,
                            'soil_status'       => 'Dry — needs water',
                            'rain_percent'      => 0,
                            'rain_status'       => 'No rain',
                            'temp_celsius'      => 29.0,
                            'humidity_percent'  => 55,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 12,
                        ],
                        'weather' => [
                            'temp_current'              => 28.0,
                            'humidity_current'          => 52,
                            'precipitation_now'         => 0,
                            'wind_speed'                => 6.0,
                            'description'               => 'Partly cloudy',
                            'rain_probability_next_6h'  => 10,
                            'temp_next_6h'              => [29, 30, 30, 29, 28, 27],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'ON',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'Dry Soil + Tank Empty',
                    'key'   => 'dry_soil_tank_empty',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 15,
                            'soil_status'       => 'Dry — needs water',
                            'rain_percent'      => 0,
                            'rain_status'       => 'No rain',
                            'temp_celsius'      => 31.0,
                            'humidity_percent'  => 42,
                            'tank_status'       => 'EMPTY',
                            'tank_fill_percent' => 0,
                        ],
                        'weather' => [
                            'temp_current'              => 30.0,
                            'humidity_current'          => 40,
                            'precipitation_now'         => 0,
                            'wind_speed'                => 9.5,
                            'description'               => 'Clear sky',
                            'rain_probability_next_6h'  => 20,
                            'temp_next_6h'              => [31, 32, 32, 30, 28, 26],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'Cool Night + Adequate Moisture',
                    'key'   => 'cool_night_adequate_moisture',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 52,
                            'soil_status'       => 'Slightly dry',
                            'rain_percent'      => 3,
                            'rain_status'       => 'No rain',
                            'temp_celsius'      => 14.0,
                            'humidity_percent'  => 72,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 74,
                        ],
                        'weather' => [
                            'temp_current'              => 13.5,
                            'humidity_current'          => 75,
                            'precipitation_now'         => 0,
                            'wind_speed'                => 3.2,
                            'description'               => 'Clear sky',
                            'rain_probability_next_6h'  => 15,
                            'temp_next_6h'              => [13, 12, 12, 13, 15, 18],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'Light Drizzle + High Humidity',
                    'key'   => 'light_drizzle_high_humidity',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 68,
                            'soil_status'       => 'Slightly dry',
                            'rain_percent'      => 35,
                            'rain_status'       => 'Light rain',
                            'temp_celsius'      => 17.0,
                            'humidity_percent'  => 94,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 91,
                        ],
                        'weather' => [
                            'temp_current'              => 16.5,
                            'humidity_current'          => 96,
                            'precipitation_now'         => 2.1,
                            'wind_speed'                => 4.5,
                            'description'               => 'Light drizzle',
                            'rain_probability_next_6h'  => 60,
                            'temp_next_6h'              => [16, 16, 17, 18, 18, 17],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
                [
                    'title' => 'All Sensors Mid-Range / Nominal',
                    'key'   => 'all_nominal',
                    'payload' => [
                        'sensor' => [
                            'moisture_percent'  => 50,
                            'soil_status'       => 'Slightly dry',
                            'rain_percent'      => 0,
                            'rain_status'       => 'No rain',
                            'temp_celsius'      => 25.0,
                            'humidity_percent'  => 60,
                            'tank_status'       => 'OK',
                            'tank_fill_percent' => 50,
                        ],
                        'weather' => [
                            'temp_current'              => 24.5,
                            'humidity_current'          => 58,
                            'precipitation_now'         => 0,
                            'wind_speed'                => 7.0,
                            'description'               => 'Partly cloudy',
                            'rain_probability_next_6h'  => 10,
                            'temp_next_6h'              => [25, 26, 26, 25, 24, 23],
                        ],
                        'context' => [
                            'plant_name'          => 'Tomatoes',
                            'last_pump_command'   => 'OFF',
                            'last_pump_command_at'=> null,
                            'moisture_threshold'  => 30,
                            'moisture_max'        => 80,
                        ],
                    ],
                ],
            ];
            @endphp

            @foreach ($samples as $i => $sample)
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col gap-3 sample-card" data-index="{{ $i }}" data-key="{{ $sample['key'] }}">

                {{-- Card header --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">{{ $sample['title'] }}</h3>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="sample-tested-at text-xs text-gray-600"></span>
                        <button type="button"
                                class="sample-test-btn flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-lg transition-colors"
                                data-payload='@json($sample['payload'])'>
                            <svg class="w-3.5 h-3.5 btn-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span class="btn-label">Test</span>
                        </button>
                    </div>
                </div>

                {{-- Payload + Response side by side --}}
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">

                    {{-- Payload --}}
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-gray-500 font-medium">Request</span>
                        <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono overflow-x-auto whitespace-pre leading-relaxed h-full">{{ json_encode($sample['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>

                    {{-- Response --}}
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 font-medium">Response</span>
                            <span class="sample-status text-xs font-mono px-2 py-0.5 rounded-md"></span>
                            <span class="sample-latency text-xs text-gray-500 font-mono"></span>
                        </div>
                        <div class="sample-response-empty bg-gray-950 border border-gray-800 rounded-xl p-4 flex items-center justify-center text-xs text-gray-600 min-h-24">
                            Not tested yet
                        </div>
                        <pre class="sample-body hidden bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono overflow-x-auto whitespace-pre leading-relaxed h-full"></pre>
                    </div>

                </div>

            </div>
            @endforeach

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script type="module">
(function () {
    const el = document.getElementById('ai-tester');
    let currentReading = JSON.parse(el.dataset.latest || 'null');

    // ── Payload builder (matches AI API contract) ────────────────────────────
    function buildLivePayload(r) {
        if (!r) { return null; }
        return {
            sensor: {
                moisture_percent:  r.moisture_percent  ?? null,
                soil_status:       r.soil_status       ?? null,
                rain_percent:      r.rain_percent       ?? null,
                rain_status:       r.rain_status       ?? null,
                temp_celsius:      r.temp              ?? null,
                humidity_percent:  r.humidity          ?? null,
                tank_status:       r.tank_status       ?? null,
                tank_fill_percent: r.tank_fill_percent ?? null,
            },
            weather: null,
            context: {
                plant_name:           null,
                last_pump_command:    r.pump_command ?? 'OFF',
                last_pump_command_at: r.created_at   ?? null,
                moisture_threshold:   null,
                moisture_max:         null,
            },
        };
    }

    // ── Render live payload preview ──────────────────────────────────────────
    function refreshLivePreview() {
        const r = currentReading;
        const payload = buildLivePayload(r);

        document.getElementById('ai-payload').textContent = payload
            ? JSON.stringify(payload, null, 2)
            : '// No sensor data yet — connect your device';

        if (r) {
            document.getElementById('r-moisture').textContent = r.moisture_percent != null ? r.moisture_percent + '%' : '—';
            document.getElementById('r-rain').textContent     = r.rain_percent      != null ? r.rain_percent      + '%' : '—';
            document.getElementById('r-temp').textContent     = r.temp              != null ? r.temp              + '°C': '—';
            document.getElementById('r-humidity').textContent = r.humidity          != null ? r.humidity          + '%' : '—';
            document.getElementById('r-tank').textContent     = r.tank_status === 'EMPTY' ? 'Empty' : (r.tank_fill_percent != null ? r.tank_fill_percent + '%' : '—');
            document.getElementById('r-pump').textContent     = r.pump_command ?? '—';
            const ts = r.created_at ? new Date(r.created_at) : null;
            document.getElementById('r-timestamp').textContent = ts ? 'Recorded ' + ts.toLocaleTimeString() : '';
        }
    }

    refreshLivePreview();

    window.Echo?.channel('farm').listen('SensorDataReceived', (data) => {
        currentReading = data;
        refreshLivePreview();
    });

    // ── Shared HTTP helper ───────────────────────────────────────────────────
    async function sendRequest(url, payload) {
        const start = Date.now();
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const elapsed = Date.now() - start;
        const text = await res.text();
        let display = text;
        try { display = JSON.stringify(JSON.parse(text), null, 2); } catch {}
        return { ok: res.ok, status: res.status, statusText: res.statusText, elapsed, display };
    }

    function getUrl() {
        return document.getElementById('ai-url').value.trim();
    }

    function showUrlError(msg) {
        const e = document.getElementById('ai-url-error');
        e.textContent = msg;
        e.classList.remove('hidden');
    }

    function clearUrlError() {
        document.getElementById('ai-url-error').classList.add('hidden');
    }

    // ── Copy live payload ────────────────────────────────────────────────────
    document.getElementById('ai-copy-payload').addEventListener('click', () => {
        const payload = buildLivePayload(currentReading);
        if (!payload) { return; }
        navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
        const btn = document.getElementById('ai-copy-payload');
        btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied`;
        setTimeout(() => {
            btn.innerHTML = `<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy`;
        }, 1500);
    });

    // ── Test with live data ──────────────────────────────────────────────────
    document.getElementById('ai-send-btn').addEventListener('click', async () => {
        const url = getUrl();
        const btn = document.getElementById('ai-send-btn');
        const errorEl = document.getElementById('ai-error');
        const statusBar = document.getElementById('ai-response-status-bar');
        const emptyState = document.getElementById('ai-response-empty');
        const payload = buildLivePayload(currentReading);

        clearUrlError();
        errorEl.classList.add('hidden');

        if (!url) { showUrlError('Enter an endpoint URL first.'); return; }
        if (!payload) {
            errorEl.textContent = 'No sensor data available to send.';
            errorEl.classList.remove('hidden');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Sending...`;

        try {
            const r = await sendRequest(url, payload);
            const statusEl = document.getElementById('ai-response-status');
            statusEl.textContent = `${r.status} ${r.statusText}`;
            statusEl.className = 'text-xs font-mono px-2 py-0.5 rounded-md ' + (r.ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400');
            document.getElementById('ai-response-latency').textContent = `${r.elapsed}ms`;
            document.getElementById('ai-response-body').textContent = r.display || '(empty response)';
            statusBar.classList.remove('hidden');
            emptyState.classList.add('hidden');
        } catch (err) {
            errorEl.textContent = `Request failed: ${err.message}`;
            errorEl.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Test with Live Data`;
        }
    });

    // ── Sample payload cards ─────────────────────────────────────────────────
    const STORAGE_PREFIX = 'ai_sample_result_';

    function renderSampleResult(card, result) {
        const status   = card.querySelector('.sample-status');
        const latency  = card.querySelector('.sample-latency');
        const testedAt = card.querySelector('.sample-tested-at');
        const body     = card.querySelector('.sample-body');
        const empty    = card.querySelector('.sample-response-empty');

        status.textContent = `${result.status} ${result.statusText}`;
        status.className = 'sample-status text-xs font-mono px-2 py-0.5 rounded-md ' + (result.ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400');
        latency.textContent = `${result.elapsed}ms`;
        testedAt.textContent = result.tested_at ? 'Last tested ' + new Date(result.tested_at).toLocaleTimeString() : '';
        body.textContent = result.display || '(empty response)';
        empty.classList.add('hidden');
        body.classList.remove('hidden');
    }

    // Restore stored results on load
    document.querySelectorAll('.sample-card').forEach((card) => {
        const key = card.dataset.key;
        const stored = localStorage.getItem(STORAGE_PREFIX + key);
        if (stored) {
            try { renderSampleResult(card, JSON.parse(stored)); } catch {}
        }
    });

    // Test buttons
    document.getElementById('sample-payload-list').addEventListener('click', async (e) => {
        const btn = e.target.closest('.sample-test-btn');
        if (!btn) { return; }

        const url = getUrl();
        clearUrlError();
        if (!url) { showUrlError('Enter an endpoint URL above first.'); return; }

        const card = btn.closest('.sample-card');
        const key = card.dataset.key;
        const payload = JSON.parse(btn.dataset.payload);

        btn.disabled = true;
        btn.querySelector('.btn-icon').outerHTML = `<svg class="w-3.5 h-3.5 btn-icon animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>`;
        btn.querySelector('.btn-label').textContent = 'Sending…';

        try {
            const result = await sendRequest(url, payload);
            result.tested_at = new Date().toISOString();
            localStorage.setItem(STORAGE_PREFIX + key, JSON.stringify(result));
            renderSampleResult(card, result);
        } catch (err) {
            renderSampleResult(card, { ok: false, status: 'Error', statusText: '', elapsed: 0, display: err.message, tested_at: new Date().toISOString() });
        } finally {
            btn.disabled = false;
            btn.querySelector('.btn-icon').outerHTML = `<svg class="w-3.5 h-3.5 btn-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`;
            btn.querySelector('.btn-label').textContent = 'Test';
        }
    });
})();
</script>
@endpush
