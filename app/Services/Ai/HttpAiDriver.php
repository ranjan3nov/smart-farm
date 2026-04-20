<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpAiDriver implements AiDriverInterface
{
    public function __construct(
        private readonly ?string $endpoint,
        private readonly ?string $apiKey = null,
    ) {}

    /** @param  array<string, mixed>  $payload */
    public function decide(array $payload): array
    {
        if (! $this->endpoint) {
            return ['pump' => 'OFF', 'reason' => 'AI endpoint not configured.'];
        }

        try {
            $request = Http::timeout(60)
                ->connectTimeout(15);

            if ($this->apiKey) {
                $request = $request->withHeader('X-API-Key', $this->apiKey);
            }

            $response = $request->post($this->endpoint, $payload)->throw();

            $data = $response->json();

            return [
                'pump' => strtoupper($data['pump'] ?? 'OFF') === 'ON' ? 'ON' : 'OFF',
                'reason' => $data['reason'] ?? 'No reason provided.',
            ];
        } catch (\Throwable $e) {
            Log::error('AI driver error', ['error' => $e->getMessage()]);
        }

        return ['pump' => 'OFF', 'reason' => 'AI unavailable — defaulting to pump OFF.'];
    }
}
