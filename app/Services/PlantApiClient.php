<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlantApiClient
{
    public function all(): array
    {
        if (Cache::has('plants_list')) {
            return Cache::get('plants_list');
        }

        $url = env('PLANTS_API_URL', 'https://irrigation-3g0b.onrender.com/api/v1/plants');
        $key = env('FARM_AI_API_KEY');

        try {
            $response = Http::timeout(8)
                ->connectTimeout(5)
                ->withHeader('X-API-KEY', $key)
                ->get($url)
                ->throw()
                ->json();

            $plants = collect($response)
                ->sortBy('name')
                ->values()
                ->toArray();

            Cache::put('plants_list', $plants, now()->addHours(6));

            return $plants;
        } catch (\Throwable $e) {
            Log::warning('Plants API fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
