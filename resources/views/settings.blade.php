@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="p-6 max-w-2xl flex flex-col gap-6">

    <div>
        <h1 class="text-xl font-semibold text-white">Settings</h1>
        <p class="text-sm text-gray-500 mt-0.5">Configure your plant, AI, and sensor preferences.</p>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl px-4 py-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('PUT')

        {{-- Plant profile --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col gap-4">
            <div>
                <h2 class="text-sm font-semibold text-white">Plant Profile</h2>
                <p class="text-xs text-gray-500 mt-0.5">Track history per plant. Reset when you change the plant.</p>
            </div>

            <div class="flex flex-col gap-1.5" x-data="plantPicker({{ json_encode($plants) }}, '{{ old('plant_name', auth()->user()->plant_name) }}')">
                <label class="text-sm font-medium text-gray-300">Plant <span class="text-gray-600 font-normal">(optional)</span></label>

                @if(count($plants) > 0)
                    <div class="relative">
                        <input type="text" x-model="search" @focus="open = true" @click.outside="open = false"
                               placeholder="Search plants..."
                               class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors">

                        <div x-show="open && filtered.length > 0" x-cloak
                             class="absolute z-10 mt-1 w-full bg-gray-800 border border-gray-700 rounded-xl overflow-hidden shadow-xl">
                            <div class="max-h-52 overflow-y-auto">
                                <button type="button" @click="select(null)"
                                        class="w-full text-left px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-700 transition-colors">
                                    — None —
                                </button>
                                <template x-for="plant in filtered" :key="plant.id">
                                    <button type="button" @click="select(plant)"
                                            :class="selected && selected.name === plant.name ? 'bg-emerald-500/10 text-emerald-400' : 'text-white hover:bg-gray-700'"
                                            class="w-full text-left px-4 py-2.5 text-sm transition-colors flex items-center justify-between gap-2">
                                        <span x-text="plant.name"></span>
                                        <span class="text-xs text-gray-500 capitalize" x-text="plant.category.replace('_', ' ')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="plant_name" :value="selected ? selected.name : ''">

                    <p x-show="selected" class="text-xs text-gray-500">
                        Moisture <span class="text-gray-300" x-text="selected ? selected.moisture_min + '–' + selected.moisture_max + '%' : ''"></span>
                        · Ideal <span class="text-gray-300" x-text="selected ? selected.ideal_moisture + '%' : ''"></span>
                    </p>
                @else
                    <input name="plant_name" type="text" value="{{ old('plant_name', auth()->user()->plant_name) }}"
                           placeholder="e.g. Tomatoes, Basil, Peppers..."
                           class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors">
                    <p class="text-xs text-gray-500">Plant list unavailable — enter manually.</p>
                @endif

                @error('plant_name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="border-t border-gray-800 pt-4 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-white">Reset Plant</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if(auth()->user()->plant_started_at)
                            Currently tracking since <strong class="text-gray-300">{{ auth()->user()->plant_started_at->format('M j, Y') }}</strong>. Resetting will start fresh from today — old readings remain but are hidden from the dashboard.
                        @else
                            No reset recorded — all historical data is shown. Reset to start tracking from today.
                        @endif
                    </p>
                </div>
                <button type="button"
                        onclick="if(confirm('Reset plant tracking from today?')) document.getElementById('reset-plant-form').submit()"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reset Plant
                </button>
            </div>
        </div>

        {{-- Farm details --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col gap-4">
            <h2 class="text-sm font-semibold text-white">System Details</h2>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-300">System Name</label>
                <input name="farm_name" type="text" value="{{ old('farm_name', $settings->name) }}"
                       class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors @error('farm_name') border-red-500 @enderror">
                @error('farm_name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-300">Latitude</label>
                    <input name="latitude" type="number" step="0.0001" value="{{ old('latitude', $settings->latitude) }}"
                           placeholder="e.g. 28.6139"
                           class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors">
                    <p class="text-xs text-gray-500">Required for weather forecast</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-300">Longitude</label>
                    <input name="longitude" type="number" step="0.0001" value="{{ old('longitude', $settings->longitude) }}"
                           placeholder="e.g. 77.2090"
                           class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors">
                </div>
            </div>
        </div>

        {{-- Sensor thresholds --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col gap-4">
            <h2 class="text-sm font-semibold text-white">Sensor Thresholds</h2>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-300">Tank Height (cm)</label>
                    <input name="tank_height_cm" type="number" value="{{ old('tank_height_cm', $settings->tank_height_cm) }}"
                           class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors @error('tank_height_cm') border-red-500 @enderror">
                    <p class="text-xs text-gray-500">Distance from sensor to water surface when tank is empty. Sent to the device each sync — no firmware change needed.</p>
                    @error('tank_height_cm')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-300">Dry Soil Threshold (%)</label>
                    <input name="moisture_threshold" type="number" min="0" max="100" value="{{ old('moisture_threshold', $settings->moisture_threshold) }}"
                           class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors @error('moisture_threshold') border-red-500 @enderror">
                    <p class="text-xs text-gray-500">AI considers watering below this %</p>
                    @error('moisture_threshold')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-300">Well-watered Threshold (%)</label>
                <input name="moisture_max" type="number" min="0" max="100" value="{{ old('moisture_max', $settings->moisture_max) }}"
                       class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors @error('moisture_max') border-red-500 @enderror">
                <p class="text-xs text-gray-500">AI stops watering above this %. Must be higher than the dry threshold.</p>
                @error('moisture_max')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- AI settings --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 flex flex-col gap-4">
            <div>
                <h2 class="text-sm font-semibold text-white">AI Configuration</h2>
                <p class="text-xs text-gray-500 mt-0.5">Endpoint and API key are set via <code class="bg-gray-800 px-1 py-0.5 rounded text-gray-300">.env</code> — <code class="bg-gray-800 px-1 py-0.5 rounded text-gray-300">FARM_AI_ENDPOINT</code> and <code class="bg-gray-800 px-1 py-0.5 rounded text-gray-300">FARM_AI_API_KEY</code>.</p>
            </div>

            @if(config('farm.ai_endpoint'))
                <div class="flex items-center gap-2 text-xs text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Endpoint configured: <code class="font-mono text-gray-300">{{ config('farm.ai_endpoint') }}</code>
                </div>
            @else
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span>
                    No endpoint set — AI decisions are disabled.
                </div>
            @endif

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-300">Decision Interval <span class="text-gray-600 font-normal">(minutes)</span></label>
                <input name="ai_decision_interval" type="number" min="1" max="60" value="{{ old('ai_decision_interval', $settings->ai_decision_interval_minutes) }}"
                       class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors">
                <p class="text-xs text-gray-500">How often the AI re-evaluates the pump. Default: 5 minutes.</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-300">Send Interval <span class="text-gray-600 font-normal">(seconds)</span></label>
                <input name="send_interval" type="number" min="10" max="3600" value="{{ old('send_interval', $settings->send_interval_seconds) }}"
                       class="bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors">
                <p class="text-xs text-gray-500">How often the device sends readings in normal mode. Alert mode always uses 20s. Default: 300s.</p>
            </div>

            {{-- API Contract --}}
            <div class="border-t border-gray-800 pt-4 flex flex-col gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-white">API Contract</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Your AI endpoint must accept a <code class="bg-gray-800 px-1 py-0.5 rounded text-gray-300">POST</code> request with this JSON body and return the response format shown below.</p>
                </div>

                {{-- Tabs --}}
                <div x-data="{ tab: 'request' }" class="flex flex-col gap-3">
                    <div class="flex gap-1 bg-gray-800 p-1 rounded-xl w-fit">
                        <button type="button" @click="tab = 'request'"
                                :class="tab === 'request' ? 'bg-gray-700 text-white' : 'text-gray-500 hover:text-gray-300'"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                            Request payload
                        </button>
                        <button type="button" @click="tab = 'response'"
                                :class="tab === 'response' ? 'bg-gray-700 text-white' : 'text-gray-500 hover:text-gray-300'"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                            Expected response
                        </button>
                    </div>

                    {{-- Request --}}
                    <div x-show="tab === 'request'" class="flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="px-2 py-0.5 bg-blue-500/10 text-blue-400 rounded font-mono font-medium">POST</span>
                            <span>Content-Type: application/json</span>
                            @if(config('farm.ai_api_key'))
                                <span>· Authorization: Bearer ••••</span>
                            @endif
                        </div>
                        <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono overflow-x-auto leading-relaxed"><span class="text-gray-500">{</span>
  <span class="text-emerald-400">"sensor"</span>: <span class="text-gray-500">{</span>
    <span class="text-blue-300">"moisture_percent"</span>: <span class="text-amber-300">18</span>,         <span class="text-gray-600">// 0–100 (100 = fully wet, 0 = bone dry)</span>
    <span class="text-blue-300">"soil_status"</span>:     <span class="text-green-300">"Dry — needs water"</span>,  <span class="text-gray-600">// human label</span>
    <span class="text-blue-300">"rain_percent"</span>:    <span class="text-amber-300">0</span>,            <span class="text-gray-600">// 0–100 (100 = heavy rain)</span>
    <span class="text-blue-300">"rain_status"</span>:     <span class="text-green-300">"No rain"</span>,
    <span class="text-blue-300">"temp_celsius"</span>:    <span class="text-amber-300">24.5</span>,
    <span class="text-blue-300">"humidity_percent"</span>: <span class="text-amber-300">62</span>,
    <span class="text-blue-300">"tank_status"</span>:     <span class="text-green-300">"OK"</span>,          <span class="text-gray-600">// "OK" | "EMPTY"</span>
    <span class="text-blue-300">"tank_fill_percent"</span>: <span class="text-amber-300">74</span>          <span class="text-gray-600">// 0–100</span>
  <span class="text-gray-500">},</span>
  <span class="text-emerald-400">"weather"</span>: <span class="text-gray-500">{</span>                        <span class="text-gray-600">// null if no lat/long configured</span>
    <span class="text-blue-300">"temp_current"</span>:           <span class="text-amber-300">23.1</span>,
    <span class="text-blue-300">"humidity_current"</span>:       <span class="text-amber-300">58</span>,
    <span class="text-blue-300">"precipitation_now"</span>:      <span class="text-amber-300">0</span>,    <span class="text-gray-600">// mm</span>
    <span class="text-blue-300">"wind_speed"</span>:             <span class="text-amber-300">12.4</span>,  <span class="text-gray-600">// km/h</span>
    <span class="text-blue-300">"description"</span>:            <span class="text-green-300">"Clear sky"</span>,
    <span class="text-blue-300">"rain_probability_next_6h"</span>: <span class="text-amber-300">5</span>,  <span class="text-gray-600">// %</span>
    <span class="text-blue-300">"temp_next_6h"</span>: <span class="text-gray-500">[</span><span class="text-amber-300">24.1</span>, <span class="text-amber-300">25.0</span>, <span class="text-amber-300">26.3</span>, <span class="text-amber-300">25.8</span>, <span class="text-amber-300">24.5</span>, <span class="text-amber-300">23.0</span><span class="text-gray-500">]</span>
  <span class="text-gray-500">},</span>
  <span class="text-emerald-400">"context"</span>: <span class="text-gray-500">{</span>
    <span class="text-blue-300">"plant_name"</span>:          <span class="text-green-300">"Tomatoes"</span>,        <span class="text-gray-600">// null if not set in Plant Profile</span>
    <span class="text-blue-300">"last_pump_command"</span>:    <span class="text-green-300">"OFF"</span>,             <span class="text-gray-600">// last decision sent to device</span>
    <span class="text-blue-300">"last_pump_command_at"</span>: <span class="text-green-300">"2026-03-26T08:00:00Z"</span>,
    <span class="text-blue-300">"moisture_threshold"</span>:   <span class="text-amber-300">30</span>                 <span class="text-gray-600">// your configured dry threshold</span>
  <span class="text-gray-500">}</span>
<span class="text-gray-500">}</span></pre>
                    </div>

                    {{-- Response --}}
                    <div x-show="tab === 'response'" class="flex flex-col gap-3">
                        <p class="text-xs text-gray-500">Return <code class="bg-gray-800 px-1 py-0.5 rounded text-gray-300">HTTP 200</code> with this JSON. Any non-200 response is treated as an error and the pump defaults to <strong class="text-white">OFF</strong>.</p>
                        <pre class="bg-gray-950 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 font-mono leading-relaxed"><span class="text-gray-500">{</span>
  <span class="text-emerald-400">"pump"</span>:   <span class="text-green-300">"ON"</span>,                                  <span class="text-gray-600">// required — "ON" | "OFF"</span>
  <span class="text-emerald-400">"reason"</span>: <span class="text-green-300">"Soil is very dry and no rain expected"</span>  <span class="text-gray-600">// required — shown on dashboard</span>
<span class="text-gray-500">}</span></pre>
                        <div class="flex flex-col gap-2">
                            <p class="text-xs font-medium text-gray-400">Rules enforced by the server:</p>
                            <ul class="flex flex-col gap-1.5 text-xs text-gray-500">
                                <li class="flex items-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0 mt-1"></span>
                                    If <code class="bg-gray-800 px-1 rounded text-gray-300">tank_status</code> is <code class="bg-gray-800 px-1 rounded text-red-400">"EMPTY"</code>, the pump is forced <strong class="text-white">OFF</strong> regardless of what your AI returns.
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 shrink-0 mt-1"></span>
                                    If the AI endpoint is unreachable or times out, the pump defaults to <strong class="text-white">OFF</strong> and the dashboard shows "AI unavailable".
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0 mt-1"></span>
                                    The AI is called at most once per decision interval. Between calls, the last known command is repeated to the device.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit"
                class="self-start bg-emerald-500 hover:bg-emerald-400 text-white font-semibold text-sm py-2.5 px-6 rounded-xl transition-colors">
            Save Settings
        </button>
    </form>

</div>

<form id="reset-plant-form" method="POST" action="{{ route('settings.plant.reset') }}" class="hidden">
    @csrf
</form>

<script>
function plantPicker(plants, current) {
    return {
        plants,
        search: current ?? '',
        selected: plants.find(p => p.name === current) ?? null,
        open: false,
        get filtered() {
            const q = this.search.toLowerCase();
            return q.length < 1 ? this.plants : this.plants.filter(p => p.name.toLowerCase().includes(q));
        },
        select(plant) {
            this.selected = plant;
            this.search = plant ? plant.name : '';
            this.open = false;

            if (plant) {
                const threshold = document.querySelector('[name="moisture_threshold"]');
                const max = document.querySelector('[name="moisture_max"]');
                if (threshold) threshold.value = Math.round(plant.moisture_min);
                if (max) max.value = Math.round(plant.moisture_max);
            }
        },
    };
}
</script>
@endsection
