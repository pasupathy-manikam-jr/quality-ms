<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'certificate_id' => Certificate::factory(),
            'material_id' => Material::factory(),
            'lot_number' => strtoupper(fake()->unique()->bothify('H##-#####')),
            'size' => null,
            'quantity' => '10.000',
            'quantity_unit' => 'pcs',
        ];
    }
}
