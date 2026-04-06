<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AiTestRun extends Model
{
    protected $fillable = [
        'scenario_key',
        'scenario_title',
        'payload',
        'response_body',
        'status_code',
        'latency_ms',
        'ok',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ok' => 'boolean',
        ];
    }

    /** Latest run per scenario key, keyed by scenario_key. */
    public static function latestPerScenario(): Collection
    {
        return static::query()
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('ai_test_runs')
                    ->groupBy('scenario_key');
            })
            ->get()
            ->keyBy('scenario_key');
    }
}
