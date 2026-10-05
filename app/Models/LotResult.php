<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One certified test result for a lot, e.g. C = 0.18 or moisture = 0.12.
 *
 * @property int $id
 * @property int $lot_id
 * @property string $property
 * @property string $value
 */
class LotResult extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    /**
     * Decimal casts keep values as exact strings on every database (SQLite would return floats).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:6',
        ];
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
