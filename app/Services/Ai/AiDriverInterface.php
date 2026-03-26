<?php

namespace App\Services\Ai;

/**
 * @phpstan-type PumpDecision array{pump: 'ON'|'OFF', reason: string}
 */
interface AiDriverInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{pump: string, reason: string}
     */
    public function decide(array $payload): array;
}
