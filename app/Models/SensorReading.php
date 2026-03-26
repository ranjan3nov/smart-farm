<?php

namespace App\Models;

use Database\Factories\SensorReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    /** @use HasFactory<SensorReadingFactory> */
    use HasFactory;

    protected $appends = [
        'moisture_percent',
        'rain_percent',
        'tank_fill_percent',
        'soil_status',
        'soil_color',
        'rain_status',
    ];

    protected $fillable = [
        'moisture',
        'rain',
        'temp',
        'humidity',
        'water_dist',
        'tank_status',
        'pump_command',
        'ai_reason',
    ];

    protected function casts(): array
    {
        return [
            'moisture' => 'integer',
            'rain' => 'integer',
            'temp' => 'float',
            'humidity' => 'float',
            'water_dist' => 'float',
        ];
    }

    /** Soil moisture as a 0–100 percentage (4095 = dry, 0 = wet). */
    public function getMoisturePercentAttribute(): int
    {
        return (int) round((1 - $this->moisture / 4095) * 100);
    }

    /** Rain intensity as a 0–100 percentage (0 = no rain, 100 = heavy rain).
     *  Sensor outputs HIGH (~4095) when dry, LOW (~0) when wet — inverted like soil sensor. */
    public function getRainPercentAttribute(): int
    {
        return (int) round((1 - $this->rain / 4095) * 100);
    }

    /** Tank fill percentage based on a configurable max distance. */
    public function getTankFillPercentAttribute(): int
    {
        $maxDistance = (float) (config('farm.tank_height_cm', 200));

        if ($this->water_dist <= 0 || $this->water_dist > $maxDistance) {
            return 0;
        }

        return (int) round((1 - $this->water_dist / $maxDistance) * 100);
    }

    /** Human-readable soil status. */
    public function getSoilStatusAttribute(): string
    {
        $pct = $this->moisture_percent;

        return match (true) {
            $pct >= 70 => 'Well watered',
            $pct >= 40 => 'Slightly dry',
            $pct >= 20 => 'Dry — needs water',
            default => 'Very dry',
        };
    }

    /** Human-readable rain status. */
    public function getRainStatusAttribute(): string
    {
        $pct = $this->rain_percent;

        return match (true) {
            $pct >= 70 => 'Heavy rain',
            $pct >= 30 => 'Light rain',
            default => 'No rain',
        };
    }

    /** CSS color class for soil status. */
    public function getSoilColorAttribute(): string
    {
        return match (true) {
            $this->moisture_percent >= 70 => 'emerald',
            $this->moisture_percent >= 40 => 'yellow',
            $this->moisture_percent >= 20 => 'orange',
            default => 'red',
        };
    }

    public function scopeRecent($query, int $limit = 30)
    {
        return $query->latest()->limit($limit);
    }
}
