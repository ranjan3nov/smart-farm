<?php

use App\Events\SensorDataReceived;
use App\Jobs\MakePumpDecision;
use App\Models\PumpSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

$validPayload = [
    'moisture' => 2000,
    'rain' => 100,
    'temp' => 24.5,
    'humidity' => 55.0,
    'water_dist' => 5.0,
    'tank_status' => 'OK',
];

it('stores a sensor reading and returns pump command', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    $this->postJson('/api/sensor-data', $validPayload)
        ->assertSuccessful()
        ->assertJsonStructure(['pump', 'next_interval'])
        ->assertJsonPath('pump', 'OFF');

    $this->assertDatabaseHas('sensor_readings', ['moisture' => 2000]);
});

it('accepts a reading with only moisture set', function () {
    Event::fake();
    Queue::fake();

    $this->postJson('/api/sensor-data', ['moisture' => 1500])
        ->assertSuccessful()
        ->assertJsonPath('pump', 'OFF');

    $this->assertDatabaseCount('sensor_readings', 1);
});

it('accepts a fully empty payload', function () {
    Event::fake();
    Queue::fake();

    $this->postJson('/api/sensor-data', [])
        ->assertSuccessful()
        ->assertJsonPath('pump', 'OFF');
});

it('forces pump OFF when tank is empty regardless of cached command', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::put('pump_command', 'ON');

    $this->postJson('/api/sensor-data', array_merge($validPayload, ['tank_status' => 'EMPTY']))
        ->assertSuccessful()
        ->assertJsonPath('pump', 'OFF');
});

it('returns last known pump command when tank is not empty', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::put('pump_command', 'ON');

    $this->postJson('/api/sensor-data', $validPayload)
        ->assertSuccessful()
        ->assertJsonPath('pump', 'ON');
});

it('returns alert interval when pump is ON', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::put('pump_command', 'ON');

    $this->postJson('/api/sensor-data', $validPayload)
        ->assertSuccessful()
        ->assertJsonPath('next_interval', 20);
});

it('returns normal interval when soil is moist and tank is OK', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::put('pump_command', 'OFF');

    // Low raw ADC = high moisture percent = healthy
    $this->postJson('/api/sensor-data', array_merge($validPayload, ['moisture' => 500]))
        ->assertSuccessful()
        ->assertJsonPath('next_interval', 300);
});

it('returns alert interval when tank is empty', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    $this->postJson('/api/sensor-data', array_merge($validPayload, ['tank_status' => 'EMPTY']))
        ->assertSuccessful()
        ->assertJsonPath('next_interval', 20);
});

it('dispatches pump decision job on first reading', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::forget('ai_decision_throttle');

    $this->postJson('/api/sensor-data', $validPayload)->assertSuccessful();

    Queue::assertPushed(MakePumpDecision::class);
});

it('throttles pump decision job on subsequent readings', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::put('ai_decision_throttle', true, now()->addMinutes(5));

    $this->postJson('/api/sensor-data', $validPayload)->assertSuccessful();

    Queue::assertNotPushed(MakePumpDecision::class);
});

it('broadcasts sensor data received event', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    $this->postJson('/api/sensor-data', $validPayload)->assertSuccessful();

    Event::assertDispatched(SensorDataReceived::class);
});

it('updates device_last_seen cache on each reading', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::forget('device_last_seen');

    $this->postJson('/api/sensor-data', $validPayload)->assertSuccessful();

    expect(Cache::has('device_last_seen'))->toBeTrue();
});

it('opens a pump session when pump transitions to ON', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    Cache::put('pump_command', 'ON');
    Cache::forget('pump_command_previous');

    $this->postJson('/api/sensor-data', $validPayload)->assertSuccessful();

    $this->assertDatabaseHas('pump_sessions', ['pump_off_at' => null]);
});

it('closes a pump session when pump transitions OFF', function () use ($validPayload) {
    Event::fake();
    Queue::fake();

    PumpSession::create(['pump_on_at' => now()->subMinutes(2)]);

    Cache::put('pump_command', 'OFF');
    Cache::put('pump_command_previous', 'ON');

    $this->postJson('/api/sensor-data', $validPayload)->assertSuccessful();

    $this->assertDatabaseMissing('pump_sessions', ['pump_off_at' => null]);
});
