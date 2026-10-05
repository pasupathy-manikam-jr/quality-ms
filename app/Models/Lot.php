<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Database\Factories\LotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One supplier lot covered by a certificate: a heat number, batch number or date code.
 *
 * @property int $id
 * @property int $certificate_id
 * @property int $material_id
 * @property string $lot_number
 * @property string|null $size
 * @property string|null $quantity
 * @property string|null $quantity_unit
 * @property CarbonImmutable|null $expires_on
 */
class Lot extends Model
{
    /** @use HasFactory<LotFactory> */
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_on' => 'date:Y-m-d',
            'size' => 'decimal:3',
            'quantity' => 'decimal:3',
        ];
    }

    /**
     * What this deployment calls a lot: "Lot", "Heat number", "Batch", ... (config/qms.php).
     */
    public static function label(): string
    {
        return __((string) config('qms.lot_label'));
    }

    /**
     * @return BelongsTo<Certificate, $this>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return HasMany<LotResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(LotResult::class)->orderBy('property');
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }

    /**
     * The limits that apply to this lot's size.
     *
     * @return list<MaterialLimit>
     */
    public function applicableLimits(): array
    {
        return array_values($this->material->limits->filter(fn (MaterialLimit $limit) => $limit->appliesToSize($this->size))->all());
    }

    /**
     * Every reported result and every required property, each with its outcome:
     * pass, fail, missing (limit but no result) or no-limit (result but no limit at this size).
     *
     * @return list<array{property: string, value: string|null, unit: string|null, range: string|null, outcome: string}>
     */
    public function checks(): array
    {
        $limits = collect($this->applicableLimits())->keyBy('property');
        $results = $this->results->keyBy('property');

        return array_values($limits->keys()->merge($results->keys())->unique()->sort()
            ->map(function (string $property) use ($limits, $results) {
                $limit = $limits->get($property);
                $result = $results->get($property);

                return [
                    'property' => $property,
                    'value' => $result ? Decimal::format($result->value) : null,
                    'unit' => $limit?->unit,
                    'range' => $limit?->rangeLabel(),
                    'outcome' => match (true) {
                        $result === null => 'missing',
                        $limit === null => 'no-limit',
                        $limit->accepts($result->value) => 'pass',
                        default => 'fail',
                    },
                ];
            })->all());
    }
}
