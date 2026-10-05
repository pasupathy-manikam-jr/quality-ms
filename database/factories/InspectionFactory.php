<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\InspectionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inspection_plan_id' => InspectionPlan::factory()->approvedWithItems(),
            'inspected_on' => now()->toDateString(),
            'quantity' => '10',
        ];
    }
}
