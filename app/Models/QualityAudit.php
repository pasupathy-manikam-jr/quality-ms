<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Database\Factories\QualityAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An internal audit (ISO 9001 §9.2): planned → in progress → completed.
 * Findings are recorded while it is in progress; nonconformities raise NCRs.
 *
 * @property int $id
 * @property string $number
 * @property string $title
 * @property string|null $scope
 * @property int|null $lead_auditor_id
 * @property CarbonImmutable $planned_on
 * @property string $status
 * @property string|null $summary
 * @property CarbonImmutable|null $completed_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class QualityAudit extends Model
{
    /** @use HasFactory<QualityAuditFactory> */
    use Auditable, HasCreator, HasFactory;

    public const STATUSES = ['planned', 'in-progress', 'completed'];

    protected $guarded = ['id', 'number', 'created_by', 'status', 'completed_at'];

    protected $attributes = ['status' => 'planned'];

    protected static function booted(): void
    {
        static::creating(function (self $audit) {
            $audit->number ??= Sequence::next('AUD');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_on' => 'date:Y-m-d',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_id')->withTrashed();
    }

    /**
     * @return BelongsToMany<IsoClause, $this>
     */
    public function clauses(): BelongsToMany
    {
        return $this->belongsToMany(IsoClause::class)->orderBy('number');
    }

    /**
     * @return HasMany<AuditFinding, $this>
     */
    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class)->orderBy('id');
    }

    public function start(): void
    {
        if ($this->status !== 'planned') {
            throw ValidationException::withMessages(['status' => __('Only a planned audit can be started.')]);
        }

        $this->forceFill(['status' => 'in-progress'])->save();
    }

    /**
     * Record a finding; a nonconformity raises a draft NCR in the same transaction.
     *
     * @param  array{type: string, iso_clause_id: int|null, description: string}  $data
     */
    public function addFinding(array $data): AuditFinding
    {
        if ($this->status !== 'in-progress') {
            throw ValidationException::withMessages(['status' => __('Findings are recorded while the audit is in progress.')]);
        }

        return DB::transaction(function () use ($data) {
            $finding = $this->findings()->create($data);

            if ($finding->isNonconformity()) {
                Ncr::raise($finding, [
                    'source' => 'internal-audit',
                    'title' => __('Audit :number: nonconformity :clause', ['number' => $this->number, 'clause' => $finding->clause ? '§'.$finding->clause->number : '']),
                    'description' => $finding->description,
                    'severity' => $finding->type === 'major-nonconformity' ? 'major' : 'minor',
                ]);
            }

            return $finding;
        });
    }

    public function complete(): void
    {
        if ($this->status !== 'in-progress') {
            throw ValidationException::withMessages(['status' => __('Only an audit in progress can be completed.')]);
        }

        if (blank($this->summary)) {
            throw ValidationException::withMessages(['status' => __('Write the audit summary first.')]);
        }

        $this->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
    }
}
