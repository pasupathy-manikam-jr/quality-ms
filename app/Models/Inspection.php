<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Support\Decimal;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One inspection done against an approved plan revision, optionally of a supplier lot.
 *
 * @property int $id
 * @property string $number
 * @property int $inspection_plan_id
 * @property int|null $lot_id
 * @property string|null $reference work order, batch or serial range
 * @property string|null $quantity
 * @property CarbonImmutable $inspected_on
 * @property string $status
 * @property CarbonImmutable|null $completed_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use Auditable, HasCreator, HasFactory;

    public const STATUSES = ['in-progress', 'passed', 'failed'];

    protected $guarded = ['id', 'number', 'created_by', 'status', 'completed_at'];

    protected $attributes = ['status' => 'in-progress'];

    protected static function booted(): void
    {
        static::creating(function (self $inspection) {
            $inspection->number ??= Sequence::next('INS');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'inspected_on' => 'date:Y-m-d',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<InspectionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    /**
     * @return HasMany<InspectionReading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(InspectionReading::class);
    }

    public function isEditable(): bool
    {
        return $this->status === 'in-progress';
    }

    /**
     * Readings still to take, as "characteristic (sample n)". Expects plan.items and readings loaded.
     *
     * @return list<string>
     */
    public function missingReadings(): array
    {
        $taken = $this->readings->map(fn (InspectionReading $r) => "{$r->inspection_plan_item_id}-{$r->sample}")->flip();
        $missing = [];

        foreach ($this->plan->items as $item) {
            for ($sample = 1; $sample <= $item->sample_size; $sample++) {
                if (! $taken->has("{$item->id}-{$sample}")) {
                    $missing[] = $item->sample_size > 1 ? __(':characteristic (sample :n)', ['characteristic' => $item->characteristic, 'n' => $sample]) : $item->characteristic;
                }
            }
        }

        return $missing;
    }

    /**
     * Close the inspection: it passes only if every reading is taken and every reading passes.
     */
    public function complete(): void
    {
        if (! $this->isEditable()) {
            throw ValidationException::withMessages(['status' => __('This inspection is already complete.')]);
        }

        $this->load(['plan.items', 'readings']);

        if (($missing = $this->missingReadings()) !== []) {
            throw ValidationException::withMessages(['status' => __('Readings still missing: :list.', ['list' => implode(', ', $missing)])]);
        }

        DB::transaction(function () {
            $this->forceFill([
                'status' => $this->readings->every('passed') ? 'passed' : 'failed',
                'completed_at' => now(),
            ])->save();

            if ($this->status === 'failed') {
                $this->raiseNcr();
            }
        });
    }

    /**
     * A failed inspection opens a draft NCR listing what failed; a failed critical
     * characteristic makes it major.
     */
    private function raiseNcr(): Ncr
    {
        $items = $this->plan->items->keyBy('id');
        $failed = $this->readings->reject->passed->map(fn (InspectionReading $r) => $items[$r->inspection_plan_item_id]);

        return Ncr::raise($this, [
            'source' => 'inspection',
            'title' => __('Inspection :number failed: :list', ['number' => $this->number, 'list' => $failed->pluck('characteristic')->unique()->join(', ')]),
            'description' => $this->readings->reject->passed->map(fn (InspectionReading $r) => __(':characteristic (sample :n): :value, specification :spec', [
                'characteristic' => $items[$r->inspection_plan_item_id]->characteristic,
                'n' => $r->sample,
                'value' => $r->value !== null ? Decimal::format($r->value) : __('Not OK'),
                'spec' => $items[$r->inspection_plan_item_id]->specLabel(),
            ]))->join("\n"),
            'severity' => $failed->contains('is_critical', true) ? 'major' : 'minor',
            'part_id' => $this->plan->part_id,
            'lot_id' => $this->lot_id,
            'supplier_id' => $this->lot?->certificate->supplier_id,
            'quantity_affected' => $this->quantity,
        ]);
    }
}
