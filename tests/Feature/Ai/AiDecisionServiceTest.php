<?php

/**
 * Feature / integration tests for AiDecisionService.
 *
 * These tests exercise the full decision pipeline with a real in-memory database
 * and a mocked HTTP driver, so every permutation of sensor state, safety
 * overrides, weather, caching and payload shape is covered deterministically.
 *
 * Real-API smoke tests live at the bottom of this file (group "real-api").
 * They are skipped automatically when FARM_AI_ENDPOINT is not set in .env.
 *
 * Run them explicitly before a presentation:
 *
 *   php artisan test --group=real-api
 */

use App\Models\FarmSetting;
use App\Models\SensorReading;
use App\Models\User;
use App\Services\Ai\AiDecisionService;
use App\Services\Ai\AiDriverInterface;
use App\Services\Ai\HttpAiDriver;
use App\Services\Weather\OpenMeteoClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\mock;

// ─────────────────────────────────────────────────────────────────────────────
// Shared setup
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Cache::flush();

    FarmSetting::create([
        'name' => 'Test Farm',
        'moisture_threshold' => 30,
        'moisture_max' => 80,
        'send_interval_seconds' => 300,
        'tank_height_cm' => 100,
    ]);

    // Ensure AI endpoint is configured so the service doesn't short-circuit
    config(['farm.ai_endpoint' => 'https://ai.example.com/decide', 'farm.ai_api_key' => 'test-key']);
});

// Helper: wire up a service with a canned driver response
function aiService(string $pump = 'OFF', string $reason = 'ok', ?array $weather = null): AiDecisionService
{
    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')
        ->andReturn(['pump' => $pump, 'reason' => $reason])
        ->getMock();

    $weatherClient = mock(OpenMeteoClient::class)
        ->shouldReceive('getForecast')
        ->andReturn($weather)
        ->getMock();

    return new AiDecisionService($driver, $weatherClient);
}

// ─────────────────────────────────────────────────────────────────────────────
// Endpoint guard
// ─────────────────────────────────────────────────────────────────────────────

it('skips the AI call and logs when FARM_AI_ENDPOINT is not configured', function () {
    config(['farm.ai_endpoint' => null]);

    $reading = SensorReading::factory()->create();

    $driver = mock(AiDriverInterface::class)->shouldNotReceive('decide')->getMock();
    $weather = mock(OpenMeteoClient::class)->shouldNotReceive('getForecast')->getMock();

    Log::shouldReceive('info')->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'no endpoint configured'));

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect(Cache::get('pump_command'))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Pump decision storage
// ─────────────────────────────────────────────────────────────────────────────

it('caches pump ON and persists it on the reading', function () {
    $reading = SensorReading::factory()->drySoil()->create(['tank_status' => 'OK']);

    aiService('ON', 'Soil is very dry.')->decide($reading);

    expect(Cache::get('pump_command'))->toBe('ON');
    expect($reading->fresh()->pump_command)->toBe('ON');
});

it('caches pump OFF and persists it on the reading', function () {
    $reading = SensorReading::factory()->create(['tank_status' => 'OK', 'moisture' => 500]);

    aiService('OFF', 'Soil moisture is sufficient.')->decide($reading);

    expect(Cache::get('pump_command'))->toBe('OFF');
    expect($reading->fresh()->pump_command)->toBe('OFF');
});

// ─────────────────────────────────────────────────────────────────────────────
// Safety override — tank empty must always force pump OFF
// ─────────────────────────────────────────────────────────────────────────────

it('forces pump OFF when the tank is empty even if the AI says ON', function () {
    $reading = SensorReading::factory()->tankEmpty()->drySoil()->create();

    aiService('ON', 'Soil is dry.')->decide($reading);

    expect(Cache::get('pump_command'))->toBe('OFF');
    expect($reading->fresh()->pump_command)->toBe('OFF');
});

it('allows pump ON when tank is OK and AI says ON', function () {
    $reading = SensorReading::factory()->drySoil()->create(['tank_status' => 'OK']);

    aiService('ON', 'Dry.')->decide($reading);

    expect(Cache::get('pump_command'))->toBe('ON');
});

it('allows pump OFF when tank is OK and AI says OFF', function () {
    $reading = SensorReading::factory()->create(['tank_status' => 'OK', 'moisture' => 100]);

    aiService('OFF', 'Wet enough.')->decide($reading);

    expect(Cache::get('pump_command'))->toBe('OFF');
});

// ─────────────────────────────────────────────────────────────────────────────
// Payload — sensor fields
// ─────────────────────────────────────────────────────────────────────────────

it('passes all required sensor keys to the AI driver', function () {
    $reading = SensorReading::factory()->create([
        'tank_status' => 'OK',
        'moisture' => 2000,
        'rain' => 100,
        'temp' => 24.5,
        'humidity' => 55.0,
        'water_dist' => 5.0,
    ]);

    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['sensor'])->toHaveKeys([
        'moisture_percent', 'soil_status', 'rain_percent', 'rain_status',
        'temp_celsius', 'humidity_percent', 'tank_status', 'tank_fill_percent',
    ]);
});

it('maps sensor raw values to the correct payload fields', function () {
    $reading = SensorReading::factory()->create([
        'tank_status' => 'OK',
        'moisture' => 4095, // 0% wet (completely dry)
        'rain' => 4095, // 0% rain
        'temp' => 32.0,
        'humidity' => 80.0,
        'water_dist' => 10.0,
    ]);

    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'ON', 'reason' => 'Dry'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['sensor']['temp_celsius'])->toBe(32.0)
        ->and($captured['sensor']['humidity_percent'])->toBe(80.0)
        ->and($captured['sensor']['tank_status'])->toBe('OK')
        ->and($captured['sensor']['rain_percent'])->toBe(0)
        ->and($captured['sensor']['moisture_percent'])->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Payload — context fields
// ─────────────────────────────────────────────────────────────────────────────

it('passes all required context keys to the AI driver', function () {
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['context'])->toHaveKeys([
        'plant_name', 'moisture_threshold', 'moisture_max',
        'last_pump_command', 'last_pump_command_at',
    ]);
});

it('includes the plant name from the user record', function () {
    // The migration seeds a default admin user — update their plant_name
    User::first()->update(['plant_name' => 'Basil']);
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['context']['plant_name'])->toBe('Basil');
});

it('carries the last pump command ON from cache into the context', function () {
    Cache::put('pump_command', 'ON');
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['context']['last_pump_command'])->toBe('ON');
});

it('defaults last_pump_command to OFF when cache has no prior decision', function () {
    Cache::forget('pump_command');
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['context']['last_pump_command'])->toBe('OFF');
});

it('passes the previous pump_command_at timestamp to the AI', function () {
    $previousAt = now()->subMinutes(10)->toISOString();
    Cache::put('pump_command_at', $previousAt);

    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['context']['last_pump_command_at'])->toBe($previousAt);
});

it('passes moisture_threshold and moisture_max from farm settings', function () {
    FarmSetting::first()->update(['moisture_threshold' => 25, 'moisture_max' => 75]);
    Cache::forget('farm_settings');

    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['context']['moisture_threshold'])->toBe(25)
        ->and($captured['context']['moisture_max'])->toBe(75);
});

// ─────────────────────────────────────────────────────────────────────────────
// Payload — weather
// ─────────────────────────────────────────────────────────────────────────────

it('fetches weather and includes it when farm coordinates are configured', function () {
    FarmSetting::first()->update(['latitude' => 1.3521, 'longitude' => 103.8198]);
    Cache::forget('farm_settings');

    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $fakeWeather = ['temp_current' => 28.5, 'description' => 'Clear sky', 'rain_probability_next_6h' => 10];
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weatherClient = mock(OpenMeteoClient::class)
        ->shouldReceive('getForecast')->once()->with(1.3521, 103.8198)->andReturn($fakeWeather)
        ->getMock();

    (new AiDecisionService($driver, $weatherClient))->decide($reading);

    expect($captured['weather'])->toBe($fakeWeather);
});

it('does not call the weather API when no coordinates are configured', function () {
    FarmSetting::first()->update(['latitude' => null, 'longitude' => null]);
    Cache::forget('farm_settings');

    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);

    $driver = mock(AiDriverInterface::class)->shouldReceive('decide')->andReturn(['pump' => 'OFF', 'reason' => 'ok'])->getMock();
    $weather = mock(OpenMeteoClient::class)->shouldNotReceive('getForecast')->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);
});

it('sends weather as null when no coordinates are configured', function () {
    FarmSetting::first()->update(['latitude' => null, 'longitude' => null]);
    Cache::forget('farm_settings');

    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->never()->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['weather'])->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Timestamps
// ─────────────────────────────────────────────────────────────────────────────

it('writes a pump_command_at timestamp to cache after every decision', function () {
    Cache::forget('pump_command_at');
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);

    aiService('ON', 'Dry.')->decide($reading);

    expect(Cache::get('pump_command_at'))->not->toBeNull();
});

it('stores pump_command_at as a valid ISO 8601 string', function () {
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);

    aiService()->decide($reading);

    $at = Cache::get('pump_command_at');
    expect(fn () => new DateTimeImmutable($at))->not->toThrow(Exception::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Logging
// ─────────────────────────────────────────────────────────────────────────────

it('logs the pump decision after every successful call', function () {
    $reading = SensorReading::factory()->create(['tank_status' => 'OK']);

    Log::shouldReceive('info')->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'AI pump decision'));

    aiService('ON', 'Very dry.')->decide($reading);
});

// ─────────────────────────────────────────────────────────────────────────────
// Soil state dataset — correct soil_status label sent to the AI
// ─────────────────────────────────────────────────────────────────────────────

dataset('soil states', [
    'well watered' => [200,  'Well watered'],
    'slightly dry' => [2000, 'Slightly dry'],
    'dry needs water' => [3000, 'Dry — needs water'],
    'very dry' => [3900, 'Very dry'],
]);

it('sends the correct soil_status label for each soil sensor reading', function (int $moisture, string $expectedStatus) {
    $reading = SensorReading::factory()->create(['moisture' => $moisture, 'tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['sensor']['soil_status'])->toBe($expectedStatus);
})->with('soil states');

// ─────────────────────────────────────────────────────────────────────────────
// Rain state dataset — correct rain_status label sent to the AI
// ─────────────────────────────────────────────────────────────────────────────

dataset('rain states', [
    'no rain' => [4095, 'No rain'],
    'light rain' => [2000, 'Light rain'],
    'heavy rain' => [100,  'Heavy rain'],
]);

it('sends the correct rain_status label for each rain sensor reading', function (int $rain, string $expectedStatus) {
    $reading = SensorReading::factory()->create(['rain' => $rain, 'tank_status' => 'OK']);
    $captured = null;

    $driver = mock(AiDriverInterface::class)
        ->shouldReceive('decide')->once()
        ->withArgs(function ($p) use (&$captured) {
            $captured = $p;

            return true;
        })
        ->andReturn(['pump' => 'OFF', 'reason' => 'ok'])
        ->getMock();

    $weather = mock(OpenMeteoClient::class)->shouldReceive('getForecast')->andReturn(null)->getMock();

    (new AiDecisionService($driver, $weather))->decide($reading);

    expect($captured['sensor']['rain_status'])->toBe($expectedStatus);
})->with('rain states');

// ─────────────────────────────────────────────────────────────────────────────
// Real AI API smoke tests
//
// These tests hit the live AI endpoint and are skipped automatically unless
// FARM_AI_ENDPOINT is set. Run them with:
//
//   php artisan test --group=real-api
// ─────────────────────────────────────────────────────────────────────────────

it('gets a valid ON/OFF decision from the real API for dry-soil data', function () {
    $endpoint = env('FARM_AI_ENDPOINT');
    $apiKey = env('FARM_AI_API_KEY');

    if (! $endpoint) {
        $this->markTestSkipped('FARM_AI_ENDPOINT not set.');
    }

    Http::allowStrayRequests(); // allow the real call

    $result = (new HttpAiDriver($endpoint, $apiKey))->decide([
        'sensor' => [
            'moisture_percent' => 8,
            'soil_status' => 'Very dry',
            'rain_percent' => 0,
            'rain_status' => 'No rain',
            'temp_celsius' => 34.0,
            'humidity_percent' => 45.0,
            'tank_status' => 'OK',
            'tank_fill_percent' => 80,
        ],
        'weather' => null,
        'context' => [
            'plant_name' => 'Tomato',
            'last_pump_command' => 'OFF',
            'last_pump_command_at' => null,
            'moisture_threshold' => 30,
            'moisture_max' => 80,
        ],
    ]);

    expect($result)->toHaveKeys(['pump', 'reason'])
        ->and($result['pump'])->toBeIn(['ON', 'OFF'])
        ->and($result['reason'])->toBeString()->not->toBeEmpty();
})->group('real-api');

it('gets a valid ON/OFF decision from the real API for wet-soil + active rain data', function () {
    $endpoint = env('FARM_AI_ENDPOINT');
    $apiKey = env('FARM_AI_API_KEY');

    if (! $endpoint) {
        $this->markTestSkipped('FARM_AI_ENDPOINT not set.');
    }

    Http::allowStrayRequests();

    $result = (new HttpAiDriver($endpoint, $apiKey))->decide([
        'sensor' => [
            'moisture_percent' => 82,
            'soil_status' => 'Well watered',
            'rain_percent' => 65,
            'rain_status' => 'Light rain',
            'temp_celsius' => 22.0,
            'humidity_percent' => 78.0,
            'tank_status' => 'OK',
            'tank_fill_percent' => 60,
        ],
        'weather' => [
            'temp_current' => 22.0,
            'description' => 'Light rain',
            'rain_probability_next_6h' => 75,
        ],
        'context' => [
            'plant_name' => 'Tomato',
            'last_pump_command' => 'OFF',
            'last_pump_command_at' => now()->subHours(2)->toISOString(),
            'moisture_threshold' => 30,
            'moisture_max' => 80,
        ],
    ]);

    expect($result)->toHaveKeys(['pump', 'reason'])
        ->and($result['pump'])->toBeIn(['ON', 'OFF'])
        ->and($result['reason'])->toBeString()->not->toBeEmpty();
})->group('real-api');

it('gets a valid decision from the real API when tank is empty', function () {
    $endpoint = env('FARM_AI_ENDPOINT');
    $apiKey = env('FARM_AI_API_KEY');

    if (! $endpoint) {
        $this->markTestSkipped('FARM_AI_ENDPOINT not set.');
    }

    Http::allowStrayRequests();

    $result = (new HttpAiDriver($endpoint, $apiKey))->decide([
        'sensor' => [
            'moisture_percent' => 5,
            'soil_status' => 'Very dry',
            'rain_percent' => 0,
            'rain_status' => 'No rain',
            'temp_celsius' => 36.0,
            'humidity_percent' => 30.0,
            'tank_status' => 'EMPTY',
            'tank_fill_percent' => 0,
        ],
        'weather' => null,
        'context' => [
            'plant_name' => 'Tomato',
            'last_pump_command' => 'OFF',
            'last_pump_command_at' => null,
            'moisture_threshold' => 30,
            'moisture_max' => 80,
        ],
    ]);

    expect($result)->toHaveKeys(['pump', 'reason'])
        ->and($result['pump'])->toBeIn(['ON', 'OFF'])
        ->and($result['reason'])->toBeString()->not->toBeEmpty();
})->group('real-api');

it('gets a valid decision from the real API with weather context and recent prior pump', function () {
    $endpoint = env('FARM_AI_ENDPOINT');
    $apiKey = env('FARM_AI_API_KEY');

    if (! $endpoint) {
        $this->markTestSkipped('FARM_AI_ENDPOINT not set.');
    }

    Http::allowStrayRequests();

    $result = (new HttpAiDriver($endpoint, $apiKey))->decide([
        'sensor' => [
            'moisture_percent' => 20,
            'soil_status' => 'Dry — needs water',
            'rain_percent' => 0,
            'rain_status' => 'No rain',
            'temp_celsius' => 29.0,
            'humidity_percent' => 60.0,
            'tank_status' => 'OK',
            'tank_fill_percent' => 50,
        ],
        'weather' => [
            'temp_current' => 29.5,
            'description' => 'Partly cloudy',
            'rain_probability_next_6h' => 20,
        ],
        'context' => [
            'plant_name' => 'Chilli',
            'last_pump_command' => 'ON',
            'last_pump_command_at' => now()->subMinutes(30)->toISOString(),
            'moisture_threshold' => 30,
            'moisture_max' => 80,
        ],
    ]);

    expect($result)->toHaveKeys(['pump', 'reason'])
        ->and($result['pump'])->toBeIn(['ON', 'OFF'])
        ->and($result['reason'])->toBeString()->not->toBeEmpty();
})->group('real-api');
