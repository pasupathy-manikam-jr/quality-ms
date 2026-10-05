<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasSignatures;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Database\Factories\NcrFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

/**
 * A non-conformance report. Failed inspections, rejected certificates and failed calibrations
 * raise one as a draft automatically (see raise()); others are entered by hand.
 *
 * @property int $id
 * @property string $number
 * @property string $source
 * @property string|null $sourceable_type
 * @property int|null $sourceable_id
 * @property string $title
 * @property string|null $description
 * @property string $severity
 * @property int|null $part_id
 * @property int|null $lot_id
 * @property int|null $supplier_id
 * @property string|null $customer
 * @property string|null $quantity_affected
 * @property string|null $disposition
 * @property string|null $disposition_notes
 * @property int|null $disposition_approved_by
 * @property CarbonImmutable|null $disposition_approved_at
 * @property string|null $closure_notes
 * @property string $status
 * @property CarbonImmutable|null $closed_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Ncr extends Model
{
    /** @use HasFactory<NcrFactory> */
    use Auditable, HasCreator, HasFactory, HasSignatures;

    public const SOURCES = ['inspection', 'certificate', 'calibration', 'customer-complaint', 'supplier', 'internal-audit', 'other'];

    /** Sources a person can pick; the others are raised by the system. */
    public const MANUAL_SOURCES = ['customer-complaint', 'supplier', 'internal-audit', 'other'];

    public const SEVERITIES = ['minor', 'major', 'critical'];

    public const DISPOSITIONS = ['use-as-is', 'rework', 'repair', 'scrap', 'return-to-supplier'];

    public const STATUSES = ['draft', 'open', 'disposition-approved', 'closed', 'cancelled'];

    /** Where each record may stand, and what each move requires, lives in transitionTo(). */
    public const TRANSITIONS = [
        'draft' => ['open', 'cancelled'],
        'open' => ['disposition-approved'],
        'disposition-approved' => ['closed'],
    ];

    protected $guarded = ['id', 'number', 'created_by', 'status', 'disposition_approved_by', 'disposition_approved_at', 'closed_at'];

    protected $attributes = ['status' => 'draft', 'severity' => 'minor'];

    protected static function booted(): void
    {
        static::creating(function (self $ncr) {
            $ncr->number ??= Sequence::next('NCR');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_affected' => 'decimal:3',
            'disposition_approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Raise a draft NCR for a failure found elsewhere in the system.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function raise(Model $source, array $attributes): self
    {
        $ncr = new self($attributes);
        $ncr->sourceable()->associate($source);
        $ncr->save();

        return $ncr;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
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
    public function dispositionApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposition_approved_by')->withTrashed();
    }

    /**
     * @return BelongsToMany<Capa, $this>
     */
    public function capas(): BelongsToMany
    {
        return $this->belongsToMany(Capa::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'open'], true);
    }

    /**
     * The page of the record that raised this NCR, if it can be shown.
     */
    public function sourceUrl(): ?string
    {
        return match (true) {
            $this->sourceable instanceof Inspection => route('inspections.show', $this->sourceable),
            $this->sourceable instanceof Certificate => route('certificates.show', $this->sourceable),
            $this->sourceable instanceof Calibration => route('gauges.show', $this->sourceable->gauge_id),
            $this->sourceable instanceof AuditFinding => route('audits.show', $this->sourceable->quality_audit_id),
            default => null,
        };
    }

    public function transitionTo(string $status, ?string $notes = null): void
    {
        if (! in_array($status, self::TRANSITIONS[$this->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => __('A :from NCR cannot be marked :to.', ['from' => __($this->status), 'to' => __($status)])]);
        }

        $error = match ($status) {
            'open' => blank($this->description) ? __('Describe the non-conformance before opening it.') : null,
            'disposition-approved' => $this->disposition === null ? __('Choose a disposition first.') : null,
            'closed' => $this->capas()->where('status', '!=', 'closed')->exists() ? __('Close the linked CAPA first.') : null,
            'cancelled' => blank($notes) ? __('Give a reason for cancelling.') : null,
        };

        if ($error) {
            throw ValidationException::withMessages(['status' => $error]);
        }

        $this->forceFill(match ($status) {
            'disposition-approved' => ['disposition_approved_by' => auth()->id(), 'disposition_approved_at' => now()],
            'closed', 'cancelled' => ['closed_at' => now(), 'closure_notes' => $notes],
            default => [],
        })->forceFill(['status' => $status])->save();
    }
}
