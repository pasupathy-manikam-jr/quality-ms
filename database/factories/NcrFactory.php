<?php

namespace Database\Factories;

use App\Models\Ncr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ncr>
 */
class NcrFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => 'customer-complaint',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'severity' => 'minor',
            'customer' => fake()->company(),
        ];
    }

    public function status(string $status): static
    {
        return $this->afterCreating(fn (Ncr $ncr) => $ncr->forceFill(['status' => $status])->save());
    }
}
