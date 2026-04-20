<?php

use App\Models\FarmSetting;

it('returns tank height from farm settings', function () {
    FarmSetting::create(['tank_height_cm' => 25]);

    $this->getJson('/api/config')
        ->assertSuccessful()
        ->assertJsonPath('tank_height_cm', 25);
});

it('returns tank height as integer', function () {
    FarmSetting::create(['tank_height_cm' => 30]);

    $this->getJson('/api/config')
        ->assertSuccessful()
        ->assertJsonPath('tank_height_cm', 30);
});
