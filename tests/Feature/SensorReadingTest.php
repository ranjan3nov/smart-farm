<?php

use App\Models\SensorReading;

it('calculates moisture percent correctly', function () {
    $reading = SensorReading::factory()->make(['moisture' => 4095]);
    expect($reading->moisture_percent)->toBe(0);

    $reading = SensorReading::factory()->make(['moisture' => 0]);
    expect($reading->moisture_percent)->toBe(100);

    $reading = SensorReading::factory()->make(['moisture' => 2048]);
    expect($reading->moisture_percent)->toBe(50);
});

it('calculates rain percent correctly', function () {
    $reading = SensorReading::factory()->make(['rain' => 4095]);
    expect($reading->rain_percent)->toBe(0);

    $reading = SensorReading::factory()->make(['rain' => 0]);
    expect($reading->rain_percent)->toBe(100);
});

it('calculates tank fill percent correctly', function () {
    config(['farm.tank_height_cm' => 20]);

    $reading = SensorReading::factory()->make(['water_dist' => 5.0]);
    expect($reading->tank_fill_percent)->toBe(75);

    $reading = SensorReading::factory()->make(['water_dist' => 20.0]);
    expect($reading->tank_fill_percent)->toBe(0);

    // Beyond max distance = empty
    $reading = SensorReading::factory()->make(['water_dist' => 100.0]);
    expect($reading->tank_fill_percent)->toBe(0);
});

it('returns zero tank fill for invalid distance', function () {
    $reading = SensorReading::factory()->make(['water_dist' => 0]);
    expect($reading->tank_fill_percent)->toBe(0);
});

it('returns correct soil status labels', function (int $moisture, string $expected) {
    $reading = SensorReading::factory()->make(['moisture' => $moisture]);
    expect($reading->soil_status)->toBe($expected);
})->with([
    'well watered' => [500, 'Well watered'],   // ~88%
    'slightly dry' => [1800, 'Slightly dry'],  // ~56%
    'dry' => [2800, 'Dry — needs water'],      // ~32%
    'very dry' => [3600, 'Very dry'],          // ~12%
]);

it('returns correct rain status labels', function (int $rain, string $expected) {
    $reading = SensorReading::factory()->make(['rain' => $rain]);
    expect($reading->rain_status)->toBe($expected);
})->with([
    'heavy rain' => [500, 'Heavy rain'],
    'light rain' => [2500, 'Light rain'],
    'no rain' => [4000, 'No rain'],
]);

it('returns correct soil color', function (int $moisture, string $color) {
    $reading = SensorReading::factory()->make(['moisture' => $moisture]);
    expect($reading->soil_color)->toBe($color);
})->with([
    'emerald' => [500, 'emerald'],   // ~88%
    'yellow' => [1800, 'yellow'],    // ~56%
    'orange' => [2800, 'orange'],    // ~32%
    'red' => [4000, 'red'],
]);

it('includes computed attributes in json', function () {
    $reading = SensorReading::factory()->make();
    $json = $reading->toArray();

    expect($json)->toHaveKeys([
        'moisture_percent',
        'rain_percent',
        'tank_fill_percent',
        'soil_status',
        'soil_color',
        'rain_status',
    ]);
});
