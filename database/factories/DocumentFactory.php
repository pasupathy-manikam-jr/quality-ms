<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => strtoupper(fake()->unique()->bothify('QP-###')),
            'title' => fake()->sentence(3),
            'type' => 'procedure',
            'review_interval_months' => 12,
        ];
    }
}
