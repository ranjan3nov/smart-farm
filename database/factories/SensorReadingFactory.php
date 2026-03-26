<?php

namespace Database\Factories;

use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SensorReading>
 */
class SensorReadingFactory extends Factory
{
    public function definition(): array
    {
        $tankEmpty = $this->faker->boolean(30);

        return [
            'moisture' => $this->faker->numberBetween(0, 4095),
            'rain' => $this->faker->numberBetween(0, 4095),
            'temp' => $this->faker->randomFloat(1, -5, 40),
            'humidity' => $this->faker->randomFloat(1, 5, 95),
            'water_dist' => $tankEmpty ? $this->faker->randomFloat(2, 100, 300) : $this->faker->randomFloat(2, 0, 20),
            'tank_status' => $tankEmpty ? 'EMPTY' : 'OK',
            'pump_command' => $this->faker->randomElement(['ON', 'OFF']),
            'ai_reason' => $this->faker->optional()->sentence(),
        ];
    }

    public function tankEmpty(): static
    {
        return $this->state([
            'water_dist' => $this->faker->randomFloat(2, 150, 300),
            'tank_status' => 'EMPTY',
            'pump_command' => 'OFF',
        ]);
    }

    public function drySoil(): static
    {
        return $this->state([
            'moisture' => $this->faker->numberBetween(3000, 4095),
        ]);
    }
}
