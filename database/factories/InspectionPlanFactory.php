<?php

namespace Database\Factories;

use App\Models\InspectionPlan;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionPlan>
 */
class InspectionPlanFactory extends Factory
{
    /**
     * A draft final-inspection plan for a new part, with no characteristics.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_id' => Part::factory(),
            'stage' => 'final',
            'title' => 'Final inspection',
        ];
    }

    /**
     * Approved, with one numeric characteristic (10.00 ± 0.05 mm, sample 2) and one visual check.
     */
    public function approvedWithItems(): static
    {
        return $this->afterCreating(function (InspectionPlan $plan) {
            $plan->items()->createMany([
                ['position' => 1, 'characteristic' => 'Hole diameter', 'kind' => 'numeric', 'unit' => 'mm', 'nominal' => '10', 'min' => '9.95', 'max' => '10.05', 'sample_size' => 2, 'is_critical' => true],
                ['position' => 2, 'characteristic' => 'Surface finish', 'kind' => 'attribute', 'method' => 'Visual', 'sample_size' => 1],
            ]);
            $plan->forceFill(['status' => 'approved', 'approved_at' => now()])->save();
        });
    }
}
