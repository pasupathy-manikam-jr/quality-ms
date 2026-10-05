<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\StoresUploads;
use Carbon\CarbonImmutable;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A supplier's material certificate: an EN 10204 mill test certificate, a certificate
 * of analysis (CoA) or a certificate of conformance (CoC), covering one or more lots.
 *
 * @property int $id
 * @property int $supplier_id
 * @property string $number
 * @property string $type
 * @property CarbonImmutable $issued_on
 * @property string|null $po_number
 * @property string|null $third_party_inspector
 * @property string $status
 * @property string|null $rejection_reason
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_type
 * @property int|null $file_size
 * @property string|null $file_sha256
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use Auditable, HasCreator, HasFactory, StoresUploads;

    public const UPLOAD_DIRECTORY = 'certificates';

    /** @var array<string, string> */
    public const TYPES = [
        'en10204-2.1' => 'EN 10204 2.1',
        'en10204-2.2' => 'EN 10204 2.2',
        'en10204-3.1' => 'EN 10204 3.1',
        'en10204-3.2' => 'EN 10204 3.2',
        'coa' => 'Certificate of analysis',
        'coc' => 'Certificate of conformance',
    ];

    /** Types that report test results; 2.1 and CoC only declare conformity. */
    public const RESULT_TYPES = ['en10204-2.2', 'en10204-3.1', 'en10204-3.2', 'coa'];

    public const STATUSES = ['received', 'verified', 'rejected'];

    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        'received' => ['verified', 'rejected'],
    ];

    protected $guarded = ['id', 'created_by', 'status', 'decided_by', 'decided_at', 'rejection_reason'];

    /** Matches the column default, so a certificate created in code knows its status before a reload. */
    protected $attributes = ['status' => 'received'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => 'date:Y-m-d',
            'decided_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class)->orderBy('lot_number');
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? $this->type);
    }

    public function reportsResults(): bool
    {
        return in_array($this->type, self::RESULT_TYPES, true);
    }

    public function isEditable(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Everything that stops this certificate from being verified. Empty means it can be.
     * Expects supplier, lots.material.limits and lots.results to be loaded.
     *
     * @return list<string>
     */
    public function issues(): array
    {
        $issues = [];

        if (! $this->supplier->is_approved) {
            $issues[] = __(':supplier is not an approved supplier.', ['supplier' => $this->supplier->name]);
        }

        if ($this->type === 'en10204-3.2' && blank($this->third_party_inspector)) {
            $issues[] = __('An EN 10204 3.2 certificate needs the third-party inspector\'s name.');
        }

        if ($this->lots->isEmpty()) {
            $issues[] = __('Add at least one :lot.', ['lot' => Lot::label()]);
        }

        if (! $this->reportsResults()) {
            return $issues;
        }

        foreach ($this->lots as $lot) {
            foreach ($lot->checks() as $check) {
                $issues[] = match ($check['outcome']) {
                    'fail' => __(':lot: :property :value is outside :range.', ['lot' => $lot->lot_number, 'property' => $check['property'], 'value' => $check['value'], 'range' => $check['range']]),
                    'missing' => __(':lot: no result for :property.', ['lot' => $lot->lot_number, 'property' => $check['property']]),
                    'no-limit' => __(':lot: no limit is defined for :property at this size.', ['lot' => $lot->lot_number, 'property' => $check['property']]),
                    default => null,
                };
            }
        }

        return array_values(array_filter($issues, 'is_string'));
    }

    /**
     * Move to another status, stamping who decided and when. The audit trail records the change.
     */
    public function transitionTo(string $status, ?string $reason = null): void
    {
        if (! in_array($status, self::TRANSITIONS[$this->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => __('A :from certificate cannot be marked :to.', ['from' => __($this->status), 'to' => __($status)])]);
        }

        if ($status === 'verified' && ($issues = $this->issues()) !== []) {
            throw ValidationException::withMessages(['status' => $issues]);
        }

        if ($status === 'rejected' && blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('Give a reason for rejecting the certificate.')]);
        }

        DB::transaction(function () use ($status, $reason) {
            $this->forceFill([
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? $reason : null,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ])->save();

            if ($status === 'rejected') {
                Ncr::raise($this, [
                    'source' => 'certificate',
                    'title' => __('Certificate :number rejected', ['number' => $this->number]),
                    'description' => $reason,
                    'severity' => 'major',
                    'supplier_id' => $this->supplier_id,
                    'lot_id' => $this->lots()->count() === 1 ? $this->lots()->value('id') : null,
                ]);
            }
        });
    }
}
