<?php

namespace App\Jobs;

use App\Models\SensorReading;
use App\Services\Ai\AiDecisionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MakePumpDecision implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(public readonly SensorReading $reading) {}

    public function handle(AiDecisionService $service): void
    {
        $service->decide($this->reading);
    }

    public function failed(\Throwable $e): void
    {
        $this->reading->update([
            'pump_command' => 'OFF',
            'ai_reason'    => 'AI unreachable after retries — defaulting to pump OFF.',
        ]);
    }
}
