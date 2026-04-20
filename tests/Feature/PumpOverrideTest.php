<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('sets pump command to ON in cache', function () {
    Cache::forget('pump_command');

    $this->post(route('pump.override'), ['command' => 'ON'])
        ->assertRedirect();

    expect(Cache::get('pump_command'))->toBe('ON');
});

it('sets pump command to OFF in cache', function () {
    Cache::put('pump_command', 'ON');

    $this->post(route('pump.override'), ['command' => 'OFF'])
        ->assertRedirect();

    expect(Cache::get('pump_command'))->toBe('OFF');
});

it('defaults to OFF for an invalid command', function () {
    $this->post(route('pump.override'), ['command' => 'INVALID'])
        ->assertRedirect();

    expect(Cache::get('pump_command'))->toBe('OFF');
});

it('flashes a success message after override', function () {
    $this->post(route('pump.override'), ['command' => 'ON'])
        ->assertRedirect()
        ->assertSessionHas('success');
});

it('requires authentication to override the pump', function () {
    auth()->logout();

    $this->post(route('pump.override'), ['command' => 'ON'])
        ->assertRedirect(route('login'));

    // Cache must not have been changed by unauthenticated request
    expect(Cache::get('pump_command'))->toBeNull();
});
