<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PumpSession extends Model
{
    protected $fillable = ['pump_on_at', 'pump_off_at', 'duration_seconds'];

    protected function casts(): array
    {
        return [
            'pump_on_at' => 'datetime',
            'pump_off_at' => 'datetime',
        ];
    }

    /** Human-readable duration, e.g. "2m 34s". */
    public function getDurationLabelAttribute(): string
    {
        if ($this->duration_seconds === null) {
            return 'Running...';
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
    }
}
