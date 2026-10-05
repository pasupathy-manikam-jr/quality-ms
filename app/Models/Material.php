<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use Carbon\CarbonImmutable;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A material and the specification it is bought to (S355JR steel, PA66-GF30 resin, ...).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $specification
 * @property string|null $size_label e.g. "Thickness (mm)"; limits can depend on it
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use Auditable, HasCreator, HasFactory;

    protected $guarded = ['id', 'created_by'];

    /**
     * @return HasMany<MaterialLimit, $this>
     */
    public function limits(): HasMany
    {
        return $this->hasMany(MaterialLimit::class)->orderBy('property')->orderBy('size_from');
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
