<?php

namespace App\Console\Commands;

use App\Models\Gauge;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('qms:calibration-reminders')]
#[Description('Email each gauge owner the gauges that are due or overdue for calibration')]
class SendCalibrationReminders extends Command
{
    public function handle(): int
    {
        $gauges = Gauge::query()
            ->whereNotNull('owner_id')
            ->where(fn ($q) => $q->inState('due')->orWhere(fn ($q) => $q->inState('overdue')))
            ->orderBy('next_due_on')
            ->get();

        $owners = User::query()->whereIn('id', $gauges->pluck('owner_id')->unique())->get()->keyBy('id');

        foreach ($gauges->groupBy('owner_id') as $ownerId => $owned) {
            if ($owner = $owners->get($ownerId)) {
                Notify::calibrationDue($owner, $owned->values());
            }
        }

        $this->info(__('Reminded :owners owner(s) about :gauges gauge(s).', ['owners' => $owners->count(), 'gauges' => $gauges->count()]));

        return self::SUCCESS;
    }
}
