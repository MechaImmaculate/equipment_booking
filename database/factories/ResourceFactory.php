<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'location' => fake()->randomElement(['Nairobi HQ', 'Mombasa Office', 'Kisumu Office']),
            'total_quantity' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
