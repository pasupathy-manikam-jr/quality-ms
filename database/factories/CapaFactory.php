<?php

namespace Database\Factories;

use App\Models\Capa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Capa>
 */
class CapaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'owner_id' => User::factory(),
            'due_on' => now()->addDays(30)->toDateString(),
        ];
    }

    public function status(string $status): static
    {
        return $this->afterCreating(fn (Capa $capa) => $capa->forceFill(['status' => $status])->save());
    }
}
