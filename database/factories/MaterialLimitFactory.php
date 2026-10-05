<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\MaterialLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialLimit>
 */
class MaterialLimitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'property' => fake()->unique()->lexify('P???'),
            'unit' => '%',
            'min' => null,
            'max' => '1.000000',
        ];
    }
}
