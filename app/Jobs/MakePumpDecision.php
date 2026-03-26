<?php

namespace App\Jobs;

use App\Models\SensorReading;
use App\Services\Ai\AiDecisionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MakePumpDecision implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(public readonly SensorReading $reading) {}

    public function handle(AiDecisionService $service): void
    {
        $service->decide($this->reading);
    }
}
