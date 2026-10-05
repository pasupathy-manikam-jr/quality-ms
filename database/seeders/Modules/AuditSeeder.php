<?php

namespace Database\Seeders\Modules;

use App\Models\IsoClause;
use App\Models\QualityAudit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditSeeder extends Seeder
{
    /**
     * One completed audit of receiving inspection (with a minor nonconformity that raised
     * an NCR) and one planned audit of calibration control.
     */
    public function run(): void
    {
        if (QualityAudit::query()->exists()) {
            return;
        }

        DB::transaction(function () {
            $auditor = User::query()->where('email', 'auditor@example.com')->firstOrFail();
            auth()->setUser($auditor);
            $clause = fn (string $number) => IsoClause::query()->where('number', $number)->value('id');

            $done = QualityAudit::query()->create([
                'title' => 'Receiving inspection and supplier control',
                'scope' => 'Goods-in area, certificate checks, receiving inspection records for the last quarter.',
                'lead_auditor_id' => $auditor->id,
                'planned_on' => now()->subDays(21)->toDateString(),
            ]);
            $done->clauses()->sync([$clause('8.4'), $clause('8.6')]);
            $done->start();
            $done->addFinding(['type' => 'minor-nonconformity', 'iso_clause_id' => $clause('8.4'), 'description' => 'Jaya Fasteners Trading supplies M8 bolts but is not on the approved supplier list.']);
            $done->addFinding(['type' => 'observation', 'iso_clause_id' => $clause('8.6'), 'description' => 'Receiving inspection records are complete; sampling sizes could be linked to lot size.']);
            $done->update(['summary' => 'Certificate checks work well. One supplier outside the approval process.']);
            $done->complete();
            $done->forceFill(['completed_at' => now()->subDays(20)])->save();

            $planned = QualityAudit::query()->create([
                'title' => 'Calibration control',
                'scope' => 'Gauge register, calibration records, handling of failed calibrations.',
                'lead_auditor_id' => $auditor->id,
                'planned_on' => now()->addDays(10)->toDateString(),
            ]);
            $planned->clauses()->sync([$clause('7.1.5')]);
        });
    }
}
