<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * One audit finding. Never edited or deleted; a wrong nonconformity is handled by cancelling its NCR.
 *
 * @property int $id
 * @property int $quality_audit_id
 * @property string $type
 * @property int|null $iso_clause_id
 * @property string $description
 * @property int|null $created_by
 */
class AuditFinding extends Model
{
    use Auditable, HasCreator;

    /** Nonconformities raise an NCR; observations and opportunities are noted only. */
    public const TYPES = ['major-nonconformity', 'minor-nonconformity', 'observation', 'opportunity'];

    protected $guarded = ['id', 'created_by'];

    /**
     * Not named audit(): that is the audit-trail method from Auditable.
     *
     * @return BelongsTo<QualityAudit, $this>
     */
    public function qualityAudit(): BelongsTo
    {
        return $this->belongsTo(QualityAudit::class, 'quality_audit_id');
    }

    /**
     * @return BelongsTo<IsoClause, $this>
     */
    public function clause(): BelongsTo
    {
        return $this->belongsTo(IsoClause::class, 'iso_clause_id');
    }

    /**
     * @return MorphOne<Ncr, $this>
     */
    public function ncr(): MorphOne
    {
        return $this->morphOne(Ncr::class, 'sourceable');
    }

    public function isNonconformity(): bool
    {
        return str_ends_with($this->type, 'nonconformity');
    }
}
