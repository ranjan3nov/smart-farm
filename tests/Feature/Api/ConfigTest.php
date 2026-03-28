<?php

it('returns tank height from config', function () {
    config(['farm.tank_height_cm' => 25]);

    $this->getJson('/api/config')
        ->assertSuccessful()
        ->assertJsonPath('tank_height_cm', 25);
});

it('returns tank height as integer', function () {
    config(['farm.tank_height_cm' => '30']);

    $this->getJson('/api/config')
        ->assertSuccessful()
        ->assertJsonPath('tank_height_cm', 30);
});
