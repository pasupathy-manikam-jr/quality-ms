<?php

namespace Database\Factories;

use App\Models\Gauge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gauge>
 */
class GaugeFactory extends Factory
{
    /**
     * A calibrated, active gauge due well outside the reminder window.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('G-####')),
            'description' => fake()->randomElement(['Digital caliper', 'Outside micrometer', 'Height gauge', 'Thread plug gauge']),
            'type' => 'Caliper',
            'measuring_range' => '0–150 mm',
            'resolution' => '0.01 mm',
            'location' => 'QC lab',
            'interval_days' => 365,
        ];
    }

    public function dueIn(?int $days): static
    {
        return $this->afterCreating(fn (Gauge $gauge) => $gauge->forceFill(['next_due_on' => $days === null ? null : now()->addDays($days)->toDateString()])->save());
    }

    public function configure(): static
    {
        return $this->afterMaking(fn (Gauge $gauge) => $gauge->forceFill(['next_due_on' => now()->addDays(200)->toDateString()]));
    }

    public function status(string $status): static
    {
        return $this->afterCreating(fn (Gauge $gauge) => $gauge->forceFill(['status' => $status])->save());
    }
}
