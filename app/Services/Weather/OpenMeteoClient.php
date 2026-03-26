<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoClient
{
    private const API_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Fetch current + 6-hour forecast for the given coordinates.
     * Results are cached for 10 minutes to avoid hammering the free API.
     *
     * @return array<string, mixed>|null
     */
    public function getForecast(float $latitude, float $longitude): ?array
    {
        $cacheKey = "weather_{$latitude}_{$longitude}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(8)
                    ->connectTimeout(5)
                    ->get(self::API_URL, [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'current' => 'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
                        'hourly' => 'precipitation_probability,temperature_2m',
                        'forecast_hours' => 6,
                        'timezone' => 'auto',
                    ])
                    ->throw()
                    ->json();

                $current = $response['current'] ?? [];
                $hourly = $response['hourly'] ?? [];

                return [
                    'temp_current' => $current['temperature_2m'] ?? null,
                    'humidity_current' => $current['relative_humidity_2m'] ?? null,
                    'precipitation_now' => $current['precipitation'] ?? 0,
                    'wind_speed' => $current['wind_speed_10m'] ?? null,
                    'weather_code' => $current['weather_code'] ?? null,
                    'description' => $this->describeWeatherCode($current['weather_code'] ?? 0),
                    'rain_probability_next_6h' => ! empty($hourly['precipitation_probability'])
                        ? max($hourly['precipitation_probability'])
                        : null,
                    'temp_next_6h' => $hourly['temperature_2m'] ?? [],
                ];
            } catch (\Throwable $e) {
                Log::warning('Open-Meteo fetch failed', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    private function describeWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            $code <= 3 => 'Partly cloudy',
            $code <= 49 => 'Foggy',
            $code <= 67 => 'Rain',
            $code <= 77 => 'Snow',
            $code <= 82 => 'Rain showers',
            $code <= 99 => 'Thunderstorm',
            default => 'Unknown',
        };
    }
}
