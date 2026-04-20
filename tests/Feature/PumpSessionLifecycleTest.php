<?php

use App\Models\FarmSetting;
use App\Models\PumpSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

$payload = fn (array $overrides = []) => array_merge([
    'moisture' => 2000,
    'rain' => 100,
    'temp' => 24.5,
    'humidity' => 55.0,
    'water_dist' => 5.0,
    'tank_status' => 'OK',
], $overrides);

beforeEach(function () {
    Event::fake();
    Queue::fake();

    FarmSetting::create([
        'name' => 'Test Farm',
        'moisture_threshold' => 30,
        'moisture_max' => 80,
        'send_interval_seconds' => 300,
    ]);
});

it('opens a new pump session when pump transitions from OFF to ON', function () use ($payload) {
    Cache::put('pump_command', 'ON');
    Cache::forget('pump_command_previous');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    $this->assertDatabaseHas('pump_sessions', ['pump_off_at' => null]);
    expect(PumpSession::count())->toBe(1);
});

it('does not open a duplicate session when pump stays ON', function () use ($payload) {
    Cache::put('pump_command', 'ON');
    Cache::put('pump_command_previous', 'ON');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();
    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    // Only the already-set session should exist (none opened by these calls)
    expect(PumpSession::count())->toBe(0);
});

it('closes an open pump session when pump transitions from ON to OFF', function () use ($payload) {
    $session = PumpSession::create(['pump_on_at' => now()->subMinutes(3)]);

    Cache::put('pump_command', 'OFF');
    Cache::put('pump_command_previous', 'ON');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    $session->refresh();
    expect($session->pump_off_at)->not->toBeNull()
        ->and($session->duration_seconds)->toBeGreaterThan(0);
});

it('records a non-zero duration when closing a session', function () use ($payload) {
    $session = PumpSession::create(['pump_on_at' => now()->subMinutes(5)]);

    Cache::put('pump_command', 'OFF');
    Cache::put('pump_command_previous', 'ON');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    $session->refresh();
    // Should be at least ~300 seconds (5 minutes)
    expect($session->duration_seconds)->toBeGreaterThanOrEqual(290);
});

it('does not close a session when pump stays OFF', function () use ($payload) {
    $session = PumpSession::create(['pump_on_at' => now()->subMinutes(2)]);

    Cache::put('pump_command', 'OFF');
    Cache::put('pump_command_previous', 'OFF');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    $session->refresh();
    expect($session->pump_off_at)->toBeNull();
});

it('completes a full ON → running → OFF pump session cycle', function () use ($payload) {
    // Step 1: Pump turns ON
    Cache::put('pump_command', 'ON');
    Cache::forget('pump_command_previous');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    expect(PumpSession::whereNull('pump_off_at')->count())->toBe(1);

    // Step 2: Pump keeps running (no new session opened)
    Cache::put('pump_command_previous', 'ON');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    expect(PumpSession::count())->toBe(1)
        ->and(PumpSession::whereNull('pump_off_at')->count())->toBe(1);

    // Step 3: Override turns pump OFF → session closes
    Cache::put('pump_command', 'OFF');

    $this->postJson('/api/sensor-data', $payload())->assertSuccessful();

    expect(PumpSession::whereNull('pump_off_at')->count())->toBe(0);
    $this->assertDatabaseMissing('pump_sessions', ['pump_off_at' => null]);
});

it('forces pump OFF and does not open a session when tank is empty', function () use ($payload) {
    // Cache says ON but tank is empty
    Cache::put('pump_command', 'ON');
    Cache::forget('pump_command_previous');

    $this->postJson('/api/sensor-data', $payload(['tank_status' => 'EMPTY']))
        ->assertSuccessful()
        ->assertJsonPath('pump', 'OFF');

    // No session should be opened because pump_command resolves to OFF
    expect(PumpSession::count())->toBe(0);
});
