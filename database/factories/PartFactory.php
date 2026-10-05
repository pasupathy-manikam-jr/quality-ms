<?php

namespace Database\Factories;

use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_number' => strtoupper(fake()->unique()->bothify('P-#####')),
            'revision' => 'A',
            'name' => fake()->words(2, true),
        ];
    }
}
