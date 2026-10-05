<?php

namespace Database\Factories;

use App\Models\QualityAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityAudit>
 */
class QualityAuditFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'scope' => fake()->sentence(),
            'planned_on' => now()->addWeek()->toDateString(),
        ];
    }

    public function status(string $status): static
    {
        return $this->afterCreating(fn (QualityAudit $audit) => $audit->forceFill(['status' => $status])->save());
    }
}
