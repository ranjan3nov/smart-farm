<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FarmSetting extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'tank_height_cm',
        'moisture_threshold',
        'moisture_max',
        'ai_decision_interval_minutes',
        'send_interval_seconds',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'tank_height_cm' => 'integer',
            'moisture_threshold' => 'integer',
            'moisture_max' => 'integer',
            'ai_decision_interval_minutes' => 'integer',
            'send_interval_seconds' => 'integer',
        ];
    }

    /** Retrieve the single settings row, creating it with defaults if absent. */
    public static function current(): static
    {
        $cached = Cache::get('farm_settings');

        if ($cached instanceof static) {
            return $cached;
        }

        Cache::forget('farm_settings');

        $settings = static::firstOrCreate([]);
        Cache::put('farm_settings', $settings, now()->addHour());

        return $settings;
    }

    /** Persist updated values and clear the cache so next call is fresh. */
    public static function updateSettings(array $attributes): void
    {
        static::updateOrCreate([], $attributes);
        Cache::forget('farm_settings');
    }
}
