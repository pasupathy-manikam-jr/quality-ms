<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use Carbon\CarbonImmutable;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A part you make, identified by part number and drawing revision.
 *
 * @property int $id
 * @property string $part_number
 * @property string $revision
 * @property string $name
 * @property int|null $material_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use Auditable, HasCreator, HasFactory;

    protected $guarded = ['id', 'created_by'];

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return HasMany<InspectionPlan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(InspectionPlan::class);
    }

    public function label(): string
    {
        return "{$this->part_number} rev {$this->revision}";
    }
}
