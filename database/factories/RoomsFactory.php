<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rooms>
 */
class RoomsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'capacity' => $this->faker->numberBetween(5, 10),
            'location' => '2F Library',
            'status' => 'operational',
            'requires_approval' => false,
        ];
    }
}
