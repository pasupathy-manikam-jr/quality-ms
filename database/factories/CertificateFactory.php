<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'number' => strtoupper(fake()->unique()->bothify('MTC-#####')),
            'type' => 'en10204-3.1',
            'issued_on' => fake()->date(),
            'po_number' => fake()->bothify('PO-####'),
        ];
    }

    public function status(string $status): static
    {
        return $this->afterCreating(fn (Certificate $certificate) => $certificate->forceFill(['status' => $status])->save());
    }
}
