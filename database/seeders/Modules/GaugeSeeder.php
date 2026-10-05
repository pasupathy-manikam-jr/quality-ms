<?php

namespace Database\Seeders\Modules;

use App\Models\Gauge;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class GaugeSeeder extends Seeder
{
    /**
     * Gauges in every state (calibrated, due, overdue, out of service, never calibrated).
     * Dates are relative to today so the demo never ages.
     */
    public function run(): void
    {
        /** @var list<array{code: string, owner: string|null, calibrations: list<array{days_ago: int, performed_by: string, result: string, as_found?: string, as_left?: string}>}> $gauges */
        $gauges = File::json(database_path('demo/gauges.json'), JSON_THROW_ON_ERROR);

        foreach ($gauges as $data) {
            if (Gauge::query()->where('code', $data['code'])->exists()) {
                continue;
            }

            $gauge = Gauge::query()->create([
                ...Arr::except($data, ['owner', 'calibrations']),
                'owner_id' => $data['owner'] ? User::query()->where('email', "{$data['owner']}@example.com")->value('id') : null,
            ]);

            foreach ($data['calibrations'] as $calibration) {
                $gauge->recordCalibration([
                    'performed_on' => now()->subDays($calibration['days_ago'])->toDateString(),
                    'performed_by' => $calibration['performed_by'],
                    'result' => $calibration['result'],
                    'as_found' => $calibration['as_found'] ?? null,
                    'as_left' => $calibration['as_left'] ?? null,
                ]);
            }
        }
    }
}
