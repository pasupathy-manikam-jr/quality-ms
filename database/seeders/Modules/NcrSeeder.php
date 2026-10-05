<?php

namespace Database\Seeders\Modules;

use App\Models\Calibration;
use App\Models\Capa;
use App\Models\Ncr;
use App\Models\Part;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NcrSeeder extends Seeder
{
    /**
     * A customer complaint with a CAPA part-way through 8D. The failed hardness-tester
     * calibration already raised its own NCR through Gauge::recordCalibration().
     */
    public function run(): void
    {
        if (Ncr::query()->where('source', 'customer-complaint')->exists()) {
            return;
        }

        DB::transaction(function () {
            $manager = User::query()->where('email', 'quality-manager@example.com')->firstOrFail();
            auth()->setUser($manager);

            $this->raiseMissingCalibrationNcrs();

            $ncr = Ncr::query()->create([
                'source' => 'customer-complaint',
                'title' => 'Bracket holes undersize, parts will not assemble',
                'description' => "Customer reports 12 of 50 brackets from WO-2026-0871 where the M10 bolt does not pass.\nMeasured on return: Ø9.91–9.93 mm (spec 9.95–10.05).",
                'severity' => 'major',
                'part_id' => Part::query()->where('part_number', 'BRK-100')->value('id'),
                'customer' => 'Perkasa Automotive Sdn Bhd',
                'quantity_affected' => '50',
            ]);
            $ncr->transitionTo('open');
            $ncr->update(['disposition' => 'rework', 'disposition_notes' => 'Ream holes to Ø10.00 and re-inspect 100%.']);
            $ncr->transitionTo('disposition-approved');

            $capa = Capa::query()->create([
                'title' => 'Undersize holes on BRK-100',
                'owner_id' => $manager->id,
                'due_on' => now()->addDays(14)->toDateString(),
                'd1_team' => 'Quality manager, CNC cell 2 lead, tooling engineer',
                'd2_problem' => $ncr->description,
                'd3_containment' => 'Stock and WIP of BRK-100 quarantined; 100% hole check before shipping.',
                'd4_root_cause' => "Why undersize? Drill worn.\nWhy worn? Tool life counter reset after a machine crash.\nWhy not caught? Hole only sampled 3 per batch.",
                'd5_actions' => 'Lock tool life counter; add go/no-go plug check per part.',
            ]);
            $capa->ncrs()->attach($ncr);
            $capa->actions()->createMany([
                ['description' => 'Password-protect tool life counter on CNC cell 2', 'owner_id' => $manager->id, 'due_on' => now()->addDays(3)->toDateString(), 'done_at' => now()],
                ['description' => 'Add go/no-go plug check to bracket plan (new revision)', 'owner_id' => $manager->id, 'due_on' => now()->addDays(7)->toDateString()],
            ]);
            $capa->advance();
            $capa->advance();
        });
    }

    /**
     * Databases seeded before NCRs existed have failed calibrations without one.
     */
    private function raiseMissingCalibrationNcrs(): void
    {
        $raised = Ncr::query()->where('sourceable_type', (new Calibration)->getMorphClass())->pluck('sourceable_id');

        Calibration::query()->with('gauge')->where('result', 'fail')->whereNotIn('id', $raised)->get()
            ->each(fn (Calibration $calibration) => Ncr::raise($calibration, [
                'source' => 'calibration',
                'title' => __('Gauge :code failed calibration', ['code' => $calibration->gauge->code]),
                'description' => $calibration->as_found,
                'severity' => $calibration->gauge->suspectInspections()->isEmpty() ? 'minor' : 'major',
            ]));
    }
}
