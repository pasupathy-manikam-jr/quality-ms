<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLimits;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One characteristic to check. Numeric ones are measured with a gauge against min/max;
 * attribute ones (visual, go/no-go) are judged OK or not OK.
 *
 * @property int $id
 * @property int $inspection_plan_id
 * @property int $position
 * @property string $characteristic
 * @property string $kind
 * @property string|null $method
 * @property string|null $unit
 * @property string|null $nominal
 * @property string|null $min
 * @property string|null $max
 * @property int $sample_size
 * @property bool $is_critical
 */
class InspectionPlanItem extends Model
{
    use Auditable, HasLimits;

    public const KINDS = ['numeric', 'attribute'];

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:6',
            'min' => 'decimal:6',
            'max' => 'decimal:6',
            'sample_size' => 'integer',
            'is_critical' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<InspectionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }

    public function isNumeric(): bool
    {
        return $this->kind === 'numeric';
    }

    public function specLabel(): string
    {
        if (! $this->isNumeric()) {
            return __('OK / not OK');
        }

        $nominal = $this->nominal !== null ? __('nominal :value', ['value' => Decimal::format($this->nominal)]).', ' : '';

        return trim($nominal.$this->rangeLabel().' '.$this->unit);
    }
}
