@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="p-6 flex flex-col gap-6" id="dashboard"
     data-readings="{{ $recentReadings->toJson() }}"
     data-latest="{{ $latest ? $latest->toJson() : 'null' }}"
     data-interval="{{ $nextInterval }}"
     data-latest-url="{{ route('dashboard.latest') }}"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-semibold text-white">{{ $farmName }}</h1>
                @if($plantName)
                    <span class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-medium border border-emerald-500/20">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 5.97-7 9.5-7 12a7 7 0 0014 0c0-2.5-2.03-6.03-7-12z"/></svg>
                        {{ $plantName }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-0.5">
                Live plant monitoring
                @if($plantStartedAt)
                    <span class="text-gray-600">· tracking since {{ $plantStartedAt->format('M j, Y') }}</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Countdown timer --}}
            <div id="update-timer" class="flex items-center gap-1.5 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span id="update-timer-text">
                    @if($latest) Next in {{ $nextInterval }}s @else Waiting for data... @endif
                </span>
                <span class="w-10 h-1 bg-gray-800 rounded-full overflow-hidden">
                    <span id="update-timer-progress" class="block h-full bg-emerald-500 rounded-full transition-none" style="width:100%"></span>
                </span>
            </div>

            {{-- Force refresh button --}}
            <button id="btn-refresh" onclick="pollLatest()"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white transition-colors"
                title="Fetch latest reading now">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M5.635 15A8 8 0 104.582 9H4"/></svg>
                Refresh
            </button>

            {{-- Raw data button --}}
            <a href="{{ route('dashboard.raw') }}" target="_blank"
               class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Raw Data
            </a>


            {{-- Device status --}}
            <div id="device-status" class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium
                {{ $lastSeen && $lastSeen->diffInSeconds() < 60 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-gray-800 text-gray-500' }}">
                <span id="device-status-dot" class="w-1.5 h-1.5 rounded-full {{ $lastSeen && $lastSeen->diffInSeconds() < 60 ? 'bg-emerald-400 animate-pulse' : 'bg-gray-600' }}"></span>
                <span id="device-status-text">
                    @if($lastSeen)
                        {{ $lastSeen->diffInSeconds() < 60 ? 'Device online' : 'Last seen ' . $lastSeen->diffForHumans() }}
                    @else
                        No data yet
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Pump override feedback --}}
    @if(session('success'))
        <div class="flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-xl px-4 py-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Alert banners --}}
    <div id="alerts" class="flex flex-col gap-2">
        @if($latest && $latest->tank_status === 'EMPTY')
            <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl px-4 py-3 text-sm">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <strong>Water tank is empty.</strong> Pump is disabled until the tank is refilled.
            </div>
        @endif
        @if($latest && $latest->temp !== null && $latest->temp < 4)
            <div class="flex items-center gap-3 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-xl px-4 py-3 text-sm">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <strong>Frost warning!</strong> Temperature is {{ $latest->temp }}°C — protect your plants.
            </div>
        @endif
        <div id="offline-alert" class="hidden items-center gap-3 bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 rounded-xl px-4 py-3 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>Device offline — <span id="offline-duration">no data received in the last 60 seconds.</span></span>
        </div>
    </div>

    {{-- Metric cards --}}
    <div>
    <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sensor Readings</span>
        <span id="last-updated-label" class="text-xs text-gray-600">
            @if($latest) Updated {{ $latest->created_at->diffForHumans() }} @else No data @endif
        </span>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        {{-- Soil Moisture --}}
        <div class="col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Soil</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                </div>
            </div>
            <div>
                <div id="soil-percent" class="text-2xl font-bold text-white">{{ $latest ? $latest->moisture_percent . '%' : '—' }}</div>
                <div id="soil-status" class="text-xs text-gray-400 mt-0.5">{{ $latest ? $latest->soil_status : 'No data' }}</div>
            </div>
            <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                <div id="soil-bar" class="h-full rounded-full transition-all duration-700
                    {{ $latest ? match($latest->soil_color) { 'emerald' => 'bg-emerald-500', 'yellow' => 'bg-yellow-500', 'orange' => 'bg-orange-500', default => 'bg-red-500' } : 'bg-gray-700' }}"
                     style="width: {{ $latest ? $latest->moisture_percent : 0 }}%"></div>
            </div>
        </div>

        {{-- Rain --}}
        <div class="col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Rain</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3"/></svg>
                </div>
            </div>
            <div>
                <div id="rain-status" class="text-2xl font-bold text-white">{{ $latest ? $latest->rain_status : '—' }}</div>
                <div class="text-xs text-gray-400 mt-0.5">Rainfall sensor</div>
            </div>
            <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                <div id="rain-bar" class="h-full bg-blue-500 rounded-full transition-all duration-700"
                     style="width: {{ $latest ? $latest->rain_percent : 0 }}%"></div>
            </div>
        </div>

        {{-- Temperature --}}
        <div class="col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Temperature</span>
                <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
            </div>
            <div>
                <div id="temp-value" class="text-2xl font-bold text-white">{{ $latest && $latest->temp !== null ? $latest->temp . '°C' : '—' }}</div>
                <div id="temp-status" class="text-xs text-gray-400 mt-0.5">
                    @if($latest && $latest->temp !== null)
                        {{ $latest->temp < 0 ? 'Freezing' : ($latest->temp < 10 ? 'Cold' : ($latest->temp < 25 ? 'Comfortable' : 'Hot')) }}
                    @else
                        No data
                    @endif
                </div>
            </div>
        </div>

        {{-- Humidity --}}
        <div class="col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Air Humidity</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 5.97-7 9.5-7 12a7 7 0 0014 0c0-2.5-2.03-6.03-7-12z"/></svg>
                </div>
            </div>
            <div>
                <div id="humidity-value" class="text-2xl font-bold text-white">{{ $latest && $latest->humidity !== null ? $latest->humidity . '%' : '—' }}</div>
                <div id="humidity-status" class="text-xs text-gray-400 mt-0.5">
                    @if($latest && $latest->humidity !== null)
                        {{ $latest->humidity < 20 ? 'Very dry air' : ($latest->humidity < 50 ? 'Comfortable' : 'Humid') }}
                    @else
                        No data
                    @endif
                </div>
            </div>
        </div>

        {{-- Water Tank --}}
        <div class="col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Water Tank</span>
                <div class="w-8 h-8 rounded-lg bg-teal-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                </div>
            </div>
            <div>
                <div id="tank-percent" class="text-2xl font-bold text-white">
                    {{ $latest ? ($latest->tank_status === 'EMPTY' ? 'Empty' : $latest->tank_fill_percent . '%') : '—' }}
                </div>
                <div id="tank-status-text" class="text-xs mt-0.5 {{ $latest && $latest->tank_status === 'EMPTY' ? 'text-red-400' : 'text-gray-400' }}">
                    {{ $latest ? ($latest->tank_status === 'EMPTY' ? 'Refill needed' : 'Tank level') : 'No data' }}
                </div>
            </div>
            <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                <div id="tank-bar" class="h-full rounded-full transition-all duration-700
                    {{ $latest && $latest->tank_status === 'EMPTY' ? 'bg-red-500' : 'bg-teal-500' }}"
                     style="width: {{ $latest && $latest->tank_status !== 'EMPTY' ? $latest->tank_fill_percent : 0 }}%"></div>
            </div>
        </div>

        {{-- Pump --}}
        <div id="pump-card" class="col-span-1 bg-gray-900 border rounded-2xl p-4 flex flex-col gap-3
            {{ $latest && $latest->pump_command === 'ON' ? 'border-emerald-500/30' : 'border-gray-800' }}">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Water Pump</span>
                <div class="w-8 h-8 rounded-lg {{ $latest && $latest->pump_command === 'ON' ? 'bg-emerald-500/10' : 'bg-gray-800' }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $latest && $latest->pump_command === 'ON' ? 'text-emerald-400' : 'text-gray-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div>
                <div id="pump-state" class="text-2xl font-bold {{ $latest && $latest->pump_command === 'ON' ? 'text-emerald-400' : 'text-gray-500' }}">
                    {{ $latest ? $latest->pump_command : 'OFF' }}
                </div>
                <div id="pump-reason" class="text-xs text-gray-400 mt-0.5 line-clamp-2">
                    {{ $latest && $latest->ai_reason ? $latest->ai_reason : 'Waiting for AI decision...' }}
                </div>
            </div>
        </div>

    </div>
    </div>

    {{-- Trend stats row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Last irrigation --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 flex items-center gap-4">
            <div class="w-9 h-9 rounded-xl bg-teal-500/10 flex items-center justify-center shrink-0">
                <svg class="w-4.5 h-4.5 text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-500 font-medium uppercase tracking-wide">Last Irrigation</div>
                <div class="text-sm font-semibold text-white mt-0.5">
                    @if($pumpSessions->isNotEmpty())
                        {{ $pumpSessions->first()->pump_on_at->diffForHumans() }}
                    @else
                        No sessions yet
                    @endif
                </div>
                @if($pumpSessions->isNotEmpty())
                    <div class="text-xs text-gray-500 mt-0.5">ran for {{ $pumpSessions->first()->duration_label }}</div>
                @endif
            </div>
        </div>

        {{-- Avg drying time --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 flex items-center gap-4">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0">
                <svg class="w-4.5 h-4.5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-500 font-medium uppercase tracking-wide">Drying Time</div>
                <div class="text-sm font-semibold text-white mt-0.5">
                    @if($dryingHours !== null)
                        ~{{ $dryingHours }}h to reach dry threshold
                    @else
                        Not enough data yet
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-0.5">time after irrigation until soil dried</div>
            </div>
        </div>

        {{-- Next irrigation estimate --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 flex items-center gap-4">
            <div class="w-9 h-9 rounded-xl bg-purple-500/10 flex items-center justify-center shrink-0">
                <svg class="w-4.5 h-4.5 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="text-xs text-gray-500 font-medium uppercase tracking-wide">Next Irrigation Est.</div>
                <div class="text-sm font-semibold text-white mt-0.5">
                    @if($nextIrrigationEstimate)
                        @if($nextIrrigationEstimate->isPast())
                            Overdue ({{ $nextIrrigationEstimate->diffForHumans() }})
                        @else
                            {{ $nextIrrigationEstimate->diffForHumans() }}
                        @endif
                    @else
                        Not enough data yet
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-0.5">based on historical drying pattern</div>
            </div>
        </div>

    </div>

    {{-- Weather Forecast --}}
    @if($weather)
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-white">Weather Forecast</h2>
            <span class="text-xs text-gray-600">via Open-Meteo · updated every 10 min</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            {{-- Condition --}}
            <div class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Condition</span>
                <span class="text-sm font-semibold text-white">{{ $weather['description'] }}</span>
            </div>

            {{-- Outside temp --}}
            <div class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Outside Temp</span>
                <span class="text-sm font-semibold text-white">
                    {{ $weather['temp_current'] !== null ? $weather['temp_current'] . '°C' : '—' }}
                </span>
            </div>

            {{-- Rain probability --}}
            <div class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Rain next 6h</span>
                <span class="text-sm font-semibold {{ ($weather['rain_probability_next_6h'] ?? 0) >= 60 ? 'text-blue-400' : 'text-white' }}">
                    {{ $weather['rain_probability_next_6h'] !== null ? $weather['rain_probability_next_6h'] . '%' : '—' }}
                </span>
            </div>

            {{-- Wind --}}
            <div class="flex flex-col gap-1">
                <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Wind</span>
                <span class="text-sm font-semibold text-white">
                    {{ $weather['wind_speed'] !== null ? $weather['wind_speed'] . ' km/h' : '—' }}
                </span>
            </div>

        </div>

        {{-- 6-hour temp sparkline --}}
        @if(!empty($weather['temp_next_6h']))
        <div class="mt-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <span class="text-xs text-gray-500">6-hour temperature outlook</span>
            </div>
            <canvas id="chart-weather-temp" height="50"></canvas>
        </div>
        @endif
    </div>
    @endif

    {{-- Charts + Activity --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Sensor charts --}}
        <div class="xl:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <h3 class="text-xs font-semibold text-white">Soil Moisture</h3>
                    <span class="ml-auto text-xs text-gray-600">%</span>
                </div>
                <canvas id="chart-soil" height="90"></canvas>
            </div>

            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <h3 class="text-xs font-semibold text-white">Rain Intensity</h3>
                    <span class="ml-auto text-xs text-gray-600">%</span>
                </div>
                <canvas id="chart-rain" height="90"></canvas>
            </div>

            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <h3 class="text-xs font-semibold text-white">Temperature</h3>
                    <span class="ml-auto text-xs text-gray-600">°C</span>
                </div>
                <canvas id="chart-temp" height="90"></canvas>
            </div>

            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    <h3 class="text-xs font-semibold text-white">Humidity</h3>
                    <span class="ml-auto text-xs text-gray-600">%</span>
                </div>
                <canvas id="chart-humidity" height="90"></canvas>
            </div>

        </div>

        {{-- Pump decisions --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-white">Pump Decisions</h2>
                <form method="POST" action="{{ route('ai.trigger') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium bg-gray-800 hover:bg-emerald-500/10 text-gray-400 hover:text-emerald-400 border border-gray-700 hover:border-emerald-500/30 transition-colors"
                        title="Run AI decision now, bypassing the interval">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Run AI Now
                    </button>
                </form>
            </div>
            @if(session('ai_trigger_success'))
                <div class="flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs rounded-lg px-3 py-2 mb-3">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    AI decision queued — refresh in a moment to see the result.
                </div>
            @endif
            @if(session('ai_trigger_error'))
                <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-400 text-xs rounded-lg px-3 py-2 mb-3">
                    {{ session('ai_trigger_error') }}
                </div>
            @endif
            <div id="activity-log" class="flex flex-col gap-2">
                @forelse($activityLog as $entry)
                    <div class="flex items-start gap-3 py-2 border-b border-gray-800/60 last:border-0">
                        <div class="w-5 h-5 rounded-full shrink-0 flex items-center justify-center mt-0.5
                            {{ $entry->pump_command === 'ON' ? 'bg-emerald-500/20' : 'bg-gray-800' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $entry->pump_command === 'ON' ? 'bg-emerald-400' : 'bg-gray-500' }}"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium {{ $entry->pump_command === 'ON' ? 'text-emerald-400' : 'text-gray-400' }}">
                                Pump {{ $entry->pump_command }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $entry->ai_reason }}</div>
                            <div class="text-xs text-gray-600 mt-0.5">{{ $entry->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500">No AI decisions yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Pump history + Manual override --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Pump session history --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-white mb-4">Pump Session History</h2>
            @if($pumpSessions->isEmpty())
                <p class="text-xs text-gray-500">No completed pump sessions yet.</p>
            @else
                <div class="flex flex-col gap-0">
                    @foreach($pumpSessions as $session)
                        <div class="flex items-center gap-4 py-3 border-b border-gray-800/60 last:border-0">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-white">{{ $session->pump_on_at->format('M j, g:i a') }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Ran for <span class="text-gray-300">{{ $session->duration_label }}</span>
                                    · off at {{ $session->pump_off_at->format('g:i a') }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-600">{{ $session->pump_on_at->diffForHumans() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Manual override --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-white mb-1">Manual Pump Override</h2>
            <p class="text-xs text-gray-500 mb-4">Temporarily override the AI decision. The AI will resume control on the next decision cycle.</p>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('pump.override') }}">
                    @csrf
                    <input type="hidden" name="command" value="ON">
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Turn Pump ON
                    </button>
                </form>
                <form method="POST" action="{{ route('pump.override') }}">
                    @csrf
                    <input type="hidden" name="command" value="OFF">
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Turn Pump OFF
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script type="module">
(function () {
    const el = document.getElementById('dashboard');
    let readings = JSON.parse(el.dataset.readings || '[]');
    let sendIntervalSecs = parseInt(el.dataset.interval || '300', 10);
    const latestUrl = el.dataset.latestUrl;

    // --- Chart setup ---
    function makeChart(id, color, data, { min, max, unit } = {}) {
        const ctx = document.getElementById(id).getContext('2d');
        const yOpts = {
            display: true,
            grid: { color: 'rgba(255,255,255,0.04)' },
            ticks: { color: '#6b7280', font: { size: 10 }, callback: v => v + unit },
            border: { display: false },
        };
        if (min !== undefined) { yOpts.min = min; }
        if (max !== undefined) { yOpts.max = max; }

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(() => ''),
                datasets: [{
                    data,
                    borderColor: color,
                    backgroundColor: color.replace(')', ', 0.08)').replace('rgb', 'rgba'),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                animation: { duration: 400 },
                plugins: { legend: { display: false }, tooltip: {
                    callbacks: { label: ctx => ctx.parsed.y + unit }
                }},
                scales: { x: { display: false }, y: yOpts },
            },
        });
    }

    const charts = {
        soil:     makeChart('chart-soil',     '#f59e0b', readings.map(r => r.moisture_percent ?? Math.round((1 - r.moisture / 4095) * 100)), { min: 0, max: 100, unit: '%' }),
        rain:     makeChart('chart-rain',     '#3b82f6', readings.map(r => r.rain_percent ?? Math.round((1 - r.rain / 4095) * 100)),         { min: 0, max: 100, unit: '%' }),
        temp:     makeChart('chart-temp',     '#f97316', readings.map(r => r.temp),                                                           { unit: '°C' }),
        humidity: makeChart('chart-humidity', '#06b6d4', readings.map(r => r.humidity),                                                       { min: 0, max: 100, unit: '%' }),
    };

    // --- Helpers ---
    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) { el.textContent = val; }
    }

    function setWidth(id, pct) {
        const el = document.getElementById(id);
        if (el) { el.style.width = Math.max(0, Math.min(100, pct)) + '%'; }
    }

    function tempStatus(t) {
        if (t < 0) return 'Freezing';
        if (t < 10) return 'Cold';
        if (t < 25) return 'Comfortable';
        return 'Hot';
    }

    function humidityStatus(h) {
        if (h < 20) return 'Very dry air';
        if (h < 50) return 'Comfortable';
        return 'Humid';
    }

    // --- Timer / countdown state ---
    const latestReading = JSON.parse(el.dataset.latest || 'null');
    let lastDataAt = latestReading?.created_at ? new Date(latestReading.created_at).getTime() : Date.now();
    let offlineSince = null;
    let pollTimer = null;

    function formatDuration(secs) {
        if (secs < 60) return `${secs}s`;
        if (secs < 3600) {
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return s > 0 ? `${m}m ${s}s` : `${m}m`;
        }
        const h = Math.floor(secs / 3600);
        const m = Math.floor((secs % 3600) / 60);
        if (secs < 86400) return m > 0 ? `${h}h ${m}m` : `${h}h`;
        const d = Math.floor(secs / 86400);
        const rh = Math.floor((secs % 86400) / 3600);
        return rh > 0 ? `${d}d ${rh}h` : `${d}d`;
    }

    function tickTimer() {
        const elapsed = Math.floor((Date.now() - lastDataAt) / 1000);
        const remaining = Math.max(0, sendIntervalSecs - elapsed);

        // Last updated label
        const lastUpdatedEl = document.getElementById('last-updated-label');
        if (lastUpdatedEl && lastDataAt) {
            lastUpdatedEl.textContent = elapsed < 5 ? 'Updated just now' : `Updated ${formatDuration(elapsed)} ago`;
        }

        // Countdown text
        const timerText = document.getElementById('update-timer-text');
        if (timerText) {
            if (remaining > 0) {
                timerText.textContent = `Next in ${formatDuration(remaining)}`;
            } else {
                timerText.textContent = elapsed < sendIntervalSecs * 2 ? 'Waiting...' : `Overdue by ${formatDuration(elapsed - sendIntervalSecs)}`;
            }
        }

        // Progress bar drains left-to-right as countdown runs
        const progress = document.getElementById('update-timer-progress');
        if (progress) {
            const pct = Math.max(0, (remaining / sendIntervalSecs) * 100);
            progress.style.width = pct + '%';
            progress.style.transition = 'width 1s linear';
            if (elapsed > sendIntervalSecs * 2) {
                progress.className = 'block h-full bg-red-500 rounded-full';
            } else if (elapsed > sendIntervalSecs * 1.2) {
                progress.className = 'block h-full bg-yellow-500 rounded-full';
            } else {
                progress.className = 'block h-full bg-emerald-500 rounded-full';
            }
        }

        // Offline detection — overdue by more than one full interval
        const overdue = elapsed > sendIntervalSecs * 2;
        const offlineAlert = document.getElementById('offline-alert');
        const statusEl = document.getElementById('device-status');
        const statusDot = document.getElementById('device-status-dot');
        const statusText = document.getElementById('device-status-text');

        if (overdue) {
            if (!offlineSince) { offlineSince = lastDataAt + sendIntervalSecs * 1000; }
            const offlineSecs = Math.floor((Date.now() - offlineSince) / 1000);

            if (offlineAlert) {
                offlineAlert.classList.remove('hidden');
                offlineAlert.classList.add('flex');
                const dur = document.getElementById('offline-duration');
                if (dur) {
                    dur.textContent = `no data for ${formatDuration(offlineSecs)}.`;
                }
            }
            if (statusEl) { statusEl.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400'; }
            if (statusDot) { statusDot.className = 'w-1.5 h-1.5 rounded-full bg-red-500'; }
            if (statusText) { statusText.textContent = 'Device offline'; }
        } else {
            offlineSince = null;
            if (offlineAlert) { offlineAlert.classList.add('hidden'); offlineAlert.classList.remove('flex'); }
        }
    }

    setInterval(tickTimer, 1000);
    tickTimer();

    // --- Poll fallback + manual refresh ---
    // Polls /dashboard/latest when Echo doesn't fire within the expected interval
    function schedulePoll() {
        clearTimeout(pollTimer);
        // Wait interval + 5s grace, then poll
        pollTimer = setTimeout(pollLatest, (sendIntervalSecs + 5) * 1000);
    }

    async function pollLatest() {
        try {
            const res = await fetch(latestUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) { return; }
            const json = await res.json();
            if (json.reading) {
                // Update interval from server
                sendIntervalSecs = json.next_interval || sendIntervalSecs;
                applyReading(json.reading);
            }
        } catch (e) {
            // silently ignore network errors
        } finally {
            schedulePoll();
        }
    }
    window.pollLatest = pollLatest;

    schedulePoll();

    // --- Reading applied on both Echo push and poll ---
    function applyReading(data) {
        lastDataAt = Date.now();
        offlineSince = null;
        sendIntervalSecs = data.next_interval || sendIntervalSecs;

        // Reset countdown bar
        const progress = document.getElementById('update-timer-progress');
        if (progress) {
            progress.style.transition = 'none';
            progress.style.width = '100%';
        }

        schedulePoll(); // reset poll timer on every fresh reading

        // Online status pill
        const statusEl = document.getElementById('device-status');
        const statusDot = document.getElementById('device-status-dot');
        const statusText = document.getElementById('device-status-text');
        if (statusEl) { statusEl.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400'; }
        if (statusDot) { statusDot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse'; }
        if (statusText) { statusText.textContent = 'Device online'; }

        // Soil
        setText('soil-percent', data.moisture_percent + '%');
        setText('soil-status', data.soil_status);
        setWidth('soil-bar', data.moisture_percent);
        const soilColors = { emerald: 'bg-emerald-500', yellow: 'bg-yellow-500', orange: 'bg-orange-500', red: 'bg-red-500' };
        const soilBar = document.getElementById('soil-bar');
        if (soilBar) {
            soilBar.className = soilBar.className.replace(/bg-\w+-500/g, soilColors[data.soil_color] || 'bg-red-500');
        }

        // Rain
        setText('rain-status', data.rain_status);
        setWidth('rain-bar', data.rain_percent);

        // Temp
        setText('temp-value', data.temp != null ? data.temp + '°C' : '—');
        setText('temp-status', data.temp != null ? tempStatus(data.temp) : 'No data');

        // Humidity
        setText('humidity-value', data.humidity != null ? data.humidity + '%' : '—');
        setText('humidity-status', data.humidity != null ? humidityStatus(data.humidity) : 'No data');

        // Tank
        const tankEmpty = data.tank_status === 'EMPTY';
        setText('tank-percent', tankEmpty ? 'Empty' : data.tank_fill_percent + '%');
        const tankStatusEl = document.getElementById('tank-status-text');
        if (tankStatusEl) {
            tankStatusEl.textContent = tankEmpty ? 'Refill needed' : 'Tank level';
            tankStatusEl.className = tankStatusEl.className.replace(/text-\w+-400/g, tankEmpty ? 'text-red-400' : 'text-gray-400');
        }
        setWidth('tank-bar', tankEmpty ? 0 : data.tank_fill_percent);

        // Pump
        const pumpOn = data.pump_command === 'ON';
        setText('pump-state', data.pump_command);
        setText('pump-reason', data.ai_reason || 'Waiting for AI decision...');
        const pumpStateEl = document.getElementById('pump-state');
        if (pumpStateEl) {
            pumpStateEl.className = 'text-2xl font-bold ' + (pumpOn ? 'text-emerald-400' : 'text-gray-500');
        }

        // Live alert banners
        const alertsEl = document.getElementById('alerts');
        if (alertsEl) {
            let alertsHtml = '';

            if (data.tank_status === 'EMPTY') {
                alertsHtml += `<div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl px-4 py-3 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <strong>Water tank is empty.</strong> Pump is disabled until the tank is refilled.
                </div>`;
            }

            if (data.temp !== null && data.temp < 4) {
                alertsHtml += `<div class="flex items-center gap-3 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-xl px-4 py-3 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <strong>Frost warning!</strong> Temperature is ${data.temp}°C — protect your plants.
                </div>`;
            }

            // Preserve the offline alert (it's managed separately)
            const offlineAlert = document.getElementById('offline-alert');
            alertsEl.innerHTML = alertsHtml;
            if (offlineAlert) { alertsEl.appendChild(offlineAlert); }
        }

        // Charts update — keep last 40
        readings.push(data);
        if (readings.length > 40) { readings.shift(); }

        const emptyLabels = readings.map(() => '');
        charts.soil.data.labels     = emptyLabels;
        charts.rain.data.labels     = emptyLabels;
        charts.temp.data.labels     = emptyLabels;
        charts.humidity.data.labels = emptyLabels;
        charts.soil.data.datasets[0].data     = readings.map(r => r.moisture_percent ?? Math.round((1 - r.moisture / 4095) * 100));
        charts.rain.data.datasets[0].data     = readings.map(r => r.rain_percent ?? Math.round((1 - r.rain / 4095) * 100));
        charts.temp.data.datasets[0].data     = readings.map(r => r.temp);
        charts.humidity.data.datasets[0].data = readings.map(r => r.humidity);
        Object.values(charts).forEach(c => c.update());
    }

    // --- Weather sparkline ---
    @if(!empty($weather['temp_next_6h']))
    (function () {
        const temps = @json($weather['temp_next_6h']);
        const ctx = document.getElementById('chart-weather-temp');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: temps.map((_, i) => `+${i}h`),
                datasets: [{
                    data: temps,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249,115,22,0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#f97316',
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ctx.parsed.y + '°C' } },
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { display: false }, border: { display: false } },
                    y: { display: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#6b7280', font: { size: 10 }, callback: v => v + '°C' }, border: { display: false } },
                },
            },
        });
    })();
    @endif

    // --- Reverb live updates ---
    window.Echo.channel('farm').listen('SensorDataReceived', (data) => {
        applyReading(data);
    });
})();
</script>

@endpush
