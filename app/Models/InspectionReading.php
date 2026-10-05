<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One measured value (numeric) or OK / not OK judgement (attribute) for one sample.
 *
 * @property int $id
 * @property int $inspection_id
 * @property int $inspection_plan_item_id
 * @property int $sample
 * @property int|null $gauge_id
 * @property string|null $value
 * @property bool|null $is_ok
 * @property bool $passed
 */
class InspectionReading extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample' => 'integer',
            'value' => 'decimal:6',
            'is_ok' => 'boolean',
            'passed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * @return BelongsTo<InspectionPlanItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InspectionPlanItem::class, 'inspection_plan_item_id');
    }

    /**
     * @return BelongsTo<Gauge, $this>
     */
    public function gauge(): BelongsTo
    {
        return $this->belongsTo(Gauge::class);
    }
}
