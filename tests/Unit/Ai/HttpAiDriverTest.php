<?php

/**
 * Unit tests for HttpAiDriver.
 *
 * These tests exercise the HTTP client layer in complete isolation — no database,
 * no Laravel app service container. Http::fake() stands in for the real AI API so
 * every edge case can be triggered deterministically.
 */

use App\Services\Ai\HttpAiDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// No real HTTP calls escape during any test in this file
beforeEach(fn () => Http::preventStrayRequests());

// ─────────────────────────────────────────────────────────────────────────────
// Successful AI responses
// ─────────────────────────────────────────────────────────────────────────────

it('returns pump ON when the AI responds with ON', function () {
    Http::fake(['*' => Http::response(['pump' => 'ON', 'reason' => 'Soil is very dry.'], 200)]);

    $result = (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    expect($result['pump'])->toBe('ON')
        ->and($result['reason'])->toBe('Soil is very dry.');
});

it('returns pump OFF when the AI responds with OFF', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'Soil moisture is sufficient.'], 200)]);

    $result = (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    expect($result['pump'])->toBe('OFF')
        ->and($result['reason'])->toBe('Soil moisture is sufficient.');
});

// ─────────────────────────────────────────────────────────────────────────────
// Response normalisation / defensive parsing
// ─────────────────────────────────────────────────────────────────────────────

it('normalises a lowercase pump value ("on") to ON', function () {
    Http::fake(['*' => Http::response(['pump' => 'on', 'reason' => 'ok'], 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('ON');
});

it('normalises a mixed-case pump value ("On") to ON', function () {
    Http::fake(['*' => Http::response(['pump' => 'On', 'reason' => 'ok'], 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('ON');
});

it('defaults to OFF when the AI returns an unrecognised pump value', function () {
    Http::fake(['*' => Http::response(['pump' => 'MAYBE', 'reason' => 'unsure'], 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

it('defaults to OFF when the pump field is absent from the response', function () {
    Http::fake(['*' => Http::response(['reason' => 'No pump key present'], 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

it('uses a built-in fallback reason when the reason field is absent', function () {
    Http::fake(['*' => Http::response(['pump' => 'ON'], 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['reason'])
        ->toBe('No reason provided.');
});

// ─────────────────────────────────────────────────────────────────────────────
// Request shape — method, URL, headers, body
// ─────────────────────────────────────────────────────────────────────────────

it('sends a POST request to the configured endpoint URL', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'ok'], 200)]);

    (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    Http::assertSent(
        fn (Request $r) => $r->toPsrRequest()->getMethod() === 'POST'
            && $r->url() === 'https://ai.example.com/decide'
    );
});

it('sends exactly one request per decide() call', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'ok'], 200)]);

    (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    Http::assertSentCount(1);
});

it('sends the full sensor payload as JSON in the request body', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'ok'], 200)]);

    $payload = [
        'sensor' => ['moisture_percent' => 15, 'tank_status' => 'OK'],
        'context' => ['last_pump_command' => 'OFF'],
        'weather' => null,
    ];

    (new HttpAiDriver('https://ai.example.com/decide'))->decide($payload);

    Http::assertSent(function (Request $r) {
        return $r['sensor']['moisture_percent'] === 15
            && $r['sensor']['tank_status'] === 'OK'
            && $r['context']['last_pump_command'] === 'OFF';
    });
});

it('attaches X-API-Key header when an API key is configured', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'ok'], 200)]);

    (new HttpAiDriver('https://ai.example.com/decide', 'my-secret-key'))->decide([]);

    Http::assertSent(fn (Request $r) => $r->hasHeader('X-API-Key', 'my-secret-key'));
});

it('does not send an X-API-Key header when no key is configured', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'ok'], 200)]);

    (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('X-API-Key'));
});

it('does not send an Authorization header (uses X-API-Key instead)', function () {
    Http::fake(['*' => Http::response(['pump' => 'OFF', 'reason' => 'ok'], 200)]);

    (new HttpAiDriver('https://ai.example.com/decide', 'my-secret-key'))->decide([]);

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('Authorization'));
});

// ─────────────────────────────────────────────────────────────────────────────
// No endpoint configured — short-circuit without making any HTTP call
// ─────────────────────────────────────────────────────────────────────────────

it('returns fallback OFF immediately when endpoint is null', function () {
    Http::fake();

    $result = (new HttpAiDriver(null))->decide(['sensor' => ['moisture_percent' => 5]]);

    Http::assertNothingSent();
    expect($result['pump'])->toBe('OFF')
        ->and($result['reason'])->toContain('not configured');
});

it('returns fallback OFF immediately when endpoint is an empty string', function () {
    Http::fake();

    $result = (new HttpAiDriver(''))->decide([]);

    Http::assertNothingSent();
    expect($result['pump'])->toBe('OFF');
});

// ─────────────────────────────────────────────────────────────────────────────
// HTTP error responses — all should degrade gracefully to pump OFF
// ─────────────────────────────────────────────────────────────────────────────

it('returns fallback OFF on a 500 server error and logs an error', function () {
    Http::fake(['*' => Http::response(['error' => 'Internal server error'], 500)]);
    Log::shouldReceive('error')->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'AI driver error'));

    $result = (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    expect($result['pump'])->toBe('OFF')
        ->and($result['reason'])->toContain('AI unavailable');
});

it('returns fallback OFF on a 503 service unavailable response', function () {
    Http::fake(['*' => Http::response('Service temporarily unavailable', 503)]);
    Log::shouldReceive('error')->once();

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

it('returns fallback OFF on a 401 unauthorised response', function () {
    Http::fake(['*' => Http::response(['error' => 'Unauthorized'], 401)]);
    Log::shouldReceive('error')->once();

    expect((new HttpAiDriver('https://ai.example.com/decide', 'bad-key'))->decide([])['pump'])->toBe('OFF');
});

it('returns fallback OFF on a 403 forbidden response', function () {
    Http::fake(['*' => Http::response(['error' => 'Forbidden'], 403)]);
    Log::shouldReceive('error')->once();

    expect((new HttpAiDriver('https://ai.example.com/decide', 'bad-key'))->decide([])['pump'])->toBe('OFF');
});

it('returns fallback OFF on a 422 unprocessable response', function () {
    Http::fake(['*' => Http::response(['detail' => 'Invalid payload schema'], 422)]);
    Log::shouldReceive('error')->once();

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

it('returns fallback OFF on a 404 not found response', function () {
    Http::fake(['*' => Http::response('Not found', 404)]);
    Log::shouldReceive('error')->once();

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

// ─────────────────────────────────────────────────────────────────────────────
// Network / transport errors
// ─────────────────────────────────────────────────────────────────────────────

it('returns fallback OFF on a connection exception and logs a warning', function () {
    Http::fake(['*' => Http::failedConnection()]);
    Log::shouldReceive('warning')->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'AI driver connection failed'));

    $result = (new HttpAiDriver('https://ai.example.com/decide'))->decide([]);

    expect($result['pump'])->toBe('OFF')
        ->and($result['reason'])->toContain('AI unavailable');
});

// ─────────────────────────────────────────────────────────────────────────────
// Malformed response bodies
// ─────────────────────────────────────────────────────────────────────────────

it('returns pump OFF (gracefully) when the response body is not valid JSON', function () {
    // json() returns null on parse failure; null-coalescing handles it without throwing
    Http::fake(['*' => Http::response('not-json-at-all', 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

it('returns pump OFF (gracefully) when the response body is an empty string', function () {
    Http::fake(['*' => Http::response('', 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});

it('returns pump OFF when the response body is a JSON array instead of object', function () {
    // A top-level array has no "pump" key — defaults to OFF cleanly
    Http::fake(['*' => Http::response([['pump' => 'ON']], 200)]);

    expect((new HttpAiDriver('https://ai.example.com/decide'))->decide([])['pump'])->toBe('OFF');
});
