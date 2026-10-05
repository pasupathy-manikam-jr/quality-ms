<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use Carbon\CarbonImmutable;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property bool $is_approved
 * @property CarbonImmutable|null $approved_on
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use Auditable, HasCreator, HasFactory;

    protected $guarded = ['id', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_on' => 'date:Y-m-d',
        ];
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * @return HasMany<Ncr, $this>
     */
    public function ncrs(): HasMany
    {
        return $this->hasMany(Ncr::class);
    }

    /**
     * Share of decided certificates that were verified, from withCount() columns
     * verified_count and rejected_count; null until a certificate has been decided.
     */
    public function acceptanceRate(): ?float
    {
        $verified = (int) $this->getAttribute('verified_count');
        $decided = $verified + (int) $this->getAttribute('rejected_count');

        return $decided === 0 ? null : round($verified / $decided * 100, 1);
    }
}
