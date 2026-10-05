<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasLimits;
use App\Support\Decimal;
use Database\Factories\MaterialLimitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The accepted range for one property of a material, optionally only for sizes
 * between size_from and size_to (inclusive). At least one of min and max is set.
 *
 * @property int $id
 * @property int $material_id
 * @property string $property
 * @property string|null $unit
 * @property string|null $min
 * @property string|null $max
 * @property string|null $size_from
 * @property string|null $size_to
 */
class MaterialLimit extends Model
{
    /** @use HasFactory<MaterialLimitFactory> */
    use Auditable, HasFactory, HasLimits;

    protected $guarded = ['id'];

    /**
     * Decimal casts keep values as exact strings on every database (SQLite would return floats).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min' => 'decimal:6',
            'max' => 'decimal:6',
            'size_from' => 'decimal:3',
            'size_to' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function appliesToSize(?string $size): bool
    {
        if ($this->size_from === null && $this->size_to === null) {
            return true;
        }

        return $size !== null
            && ($this->size_from === null || Decimal::compare($size, $this->size_from) >= 0)
            && ($this->size_to === null || Decimal::compare($size, $this->size_to) <= 0);
    }

    public function sizeLabel(): string
    {
        return match (true) {
            $this->size_from !== null && $this->size_to !== null => Decimal::format($this->size_from).' – '.Decimal::format($this->size_to),
            $this->size_from !== null => '≥ '.Decimal::format($this->size_from),
            $this->size_to !== null => '≤ '.Decimal::format($this->size_to),
            default => __('Any'),
        };
    }

    /**
     * Whether this limit's size range overlaps another's for the same property
     * (then a result could match two limits). Missing bounds are open-ended.
     */
    public function overlaps(?string $from, ?string $to): bool
    {
        return ($this->size_from === null || $to === null || Decimal::compare($this->size_from, $to) <= 0)
            && ($from === null || $this->size_to === null || Decimal::compare($from, $this->size_to) <= 0);
    }
}
