<?php

namespace Database\Seeders\Modules;

use App\Models\Gauge;
use App\Models\Inspection;
use App\Models\InspectionPlan;
use App\Models\Lot;
use App\Models\Material;
use App\Models\Part;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InspectionSeeder extends Seeder
{
    /**
     * Parts, approved plans and a few inspections. The bracket inspection 30 days ago used
     * the hardness tester that has since failed calibration, so it shows as suspect.
     */
    public function run(): void
    {
        if (Part::query()->where('part_number', 'BRK-100')->exists()) {
            return;
        }

        // All or nothing, so a failed run never leaves half the demo behind.
        DB::transaction(fn () => $this->seed());
    }

    private function seed(): void
    {
        /** @var array{parts: list<array<string, string>>, plans: list<array<string, mixed>>, inspections: list<array<string, mixed>>} $data */
        $data = File::json(database_path('demo/inspections.json'), JSON_THROW_ON_ERROR);
        $manager = User::query()->where('email', 'quality-manager@example.com')->value('id');
        $inspector = User::query()->where('email', 'inspector@example.com')->value('id');

        foreach ($data['parts'] as $part) {
            Part::query()->firstOrCreate(
                ['part_number' => $part['part_number'], 'revision' => $part['revision']],
                ['name' => $part['name'], 'material_id' => Material::query()->where('code', $part['material'])->value('id')],
            );
        }

        $plans = [];

        foreach ($data['plans'] as $planData) {
            /** @var list<array<string, mixed>> $items */
            $items = $planData['items'];
            $plan = InspectionPlan::query()->create([
                'part_id' => isset($planData['part']) ? Part::query()->where('part_number', $planData['part'])->value('id') : null,
                'material_id' => isset($planData['material']) ? Material::query()->where('code', $planData['material'])->value('id') : null,
                'stage' => $planData['stage'],
                'title' => $planData['title'],
            ]);
            $plan->items()->createMany(array_map(fn (array $item, int $i) => [...$item, 'position' => $i + 1], $items, array_keys($items)));
            $plan->forceFill(['status' => 'approved', 'approved_by' => $manager, 'approved_at' => now()->subDays(60)])->save();
            $plans[$plan->title] = $plan->load('items');
        }

        foreach ($data['inspections'] as $row) {
            $plan = $plans[$row['plan']];
            $inspection = new Inspection([
                'inspection_plan_id' => $plan->id,
                'lot_id' => isset($row['lot']) ? Lot::query()->where('lot_number', $row['lot'])->value('id') : null,
                'reference' => $row['reference'] ?? null,
                'quantity' => $row['quantity'],
                'inspected_on' => now()->subDays((int) $row['days_ago'])->toDateString(),
            ]);
            $inspection->created_by = $inspector;
            $inspection->save();

            /** @var array<string, list<array{0: string|null, 1: string|bool}>> $readings */
            $readings = $row['readings'];

            foreach ($readings as $characteristic => $samples) {
                $item = $plan->items->firstWhere('characteristic', $characteristic);

                foreach ($samples as $i => [$gaugeCode, $reading]) {
                    $inspection->readings()->create([
                        'inspection_plan_item_id' => $item->id,
                        'sample' => $i + 1,
                        'gauge_id' => $gaugeCode ? Gauge::query()->where('code', $gaugeCode)->value('id') : null,
                        'value' => is_string($reading) ? $reading : null,
                        'is_ok' => is_bool($reading) ? $reading : null,
                        'passed' => is_bool($reading) ? $reading : $item->accepts($reading),
                    ]);
                }
            }

            if (Arr::get($row, 'readings') !== []) {
                $inspection->complete();
                $inspection->forceFill(['completed_at' => now()->subDays((int) $row['days_ago'])])->save();
            }
        }
    }
}
