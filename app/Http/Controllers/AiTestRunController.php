<?php

namespace App\Http\Controllers;

use App\Models\AiTestRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
