<?php

namespace App\Http\Controllers;

use App\Models\AiTestRun;
use App\Models\FarmSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiTestRunController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scenario_key' => ['required', 'string', 'max:100'],
            'scenario_title' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
            'response_body' => ['nullable', 'string'],
            'status_code' => ['nullable', 'integer'],
            'latency_ms' => ['nullable', 'integer'],
            'ok' => ['required', 'boolean'],
        ]);

        $run = AiTestRun::create($data);

        return response()->json($run);
    }

    public function proxy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url'     => ['required', 'url', 'max:500'],
            'payload' => ['required', 'array'],
        ]);

        $apiKey = config('farm.ai_api_key');

        $start = now();

        try {
            $http = Http::timeout(90)->connectTimeout(10)->acceptJson();

            if ($apiKey) {
                $http = $http->withHeader('X-API-Key', $apiKey);
            }

            $response = $http->post($data['url'], $data['payload']);

            return response()->json([
                'status'     => $response->status(),
                'statusText' => $response->reason(),
                'body'       => $response->body(),
                'elapsed'    => now()->diffInMilliseconds($start),
                'ok'         => $response->successful(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'     => 0,
                'statusText' => 'Error',
                'body'       => $e->getMessage(),
                'elapsed'    => now()->diffInMilliseconds($start),
                'ok'         => false,
            ]);
        }
    }

    public function updateEndpoint(Request $request): JsonResponse
    {
        $data = $request->validate(['url' => ['required', 'url', 'max:500']]);

        FarmSetting::updateSettings(['ai_endpoint' => $data['url']]);

        return response()->json(['ok' => true]);
    }
}
