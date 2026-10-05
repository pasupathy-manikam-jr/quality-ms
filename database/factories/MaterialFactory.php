<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('MAT-####')),
            'name' => fake()->words(2, true),
            'specification' => fake()->bothify('SPEC ###'),
            'size_label' => null,
        ];
    }
}
