<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasSignatures;
use Carbon\CarbonImmutable;
use Database\Factories\InspectionPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * What to check, and how, for one part or material at one stage. Characteristics can only
 * change while the plan is a draft; an approved plan changes through a new revision, so
 * every inspection stays pinned to the exact plan it was done against.
 *
 * @property int $id
 * @property int|null $part_id
 * @property int|null $material_id
 * @property string $stage
 * @property int $revision
 * @property string $title
 * @property string $status
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class InspectionPlan extends Model
{
    /** @use HasFactory<InspectionPlanFactory> */
    use Auditable, HasCreator, HasFactory, HasSignatures;

    public const STAGES = ['receiving', 'in-process', 'final'];

    public const STATUSES = ['draft', 'approved', 'obsolete'];

    protected $guarded = ['id', 'created_by', 'status', 'revision', 'approved_by', 'approved_at'];

    protected $attributes = ['status' => 'draft', 'revision' => 1];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    /**
     * @return HasMany<InspectionPlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InspectionPlanItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Inspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Other revisions of the same plan (same subject and stage).
     *
     * @return Builder<InspectionPlan>
     */
    public function siblings(): Builder
    {
        return self::query()
            ->where('part_id', $this->part_id)
            ->where('material_id', $this->material_id)
            ->where('stage', $this->stage)
            ->whereKeyNot($this->id);
    }

    public function subjectLabel(): string
    {
        return $this->part?->label() ?? (string) $this->material?->code;
    }

    /**
     * The material lots must be of: the plan's material, or the part's material.
     */
    public function lotMaterialId(): ?int
    {
        return $this->material_id ?? $this->part?->material_id;
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Approve this draft and make the previously approved revision obsolete.
     */
    public function approve(): void
    {
        if ($this->status !== 'draft') {
            throw ValidationException::withMessages(['status' => __('Only a draft plan can be approved.')]);
        }

        if (! $this->items()->exists()) {
            throw ValidationException::withMessages(['status' => __('Add at least one characteristic before approving.')]);
        }

        DB::transaction(function () {
            $this->siblings()->where('status', 'approved')->get()->each(fn (self $old) => $old->forceFill(['status' => 'obsolete'])->save());

            $this->forceFill(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()])->save();
        });
    }

    public function makeObsolete(): void
    {
        if ($this->status !== 'approved') {
            throw ValidationException::withMessages(['status' => __('Only an approved plan can be made obsolete.')]);
        }

        $this->forceFill(['status' => 'obsolete'])->save();
    }

    /**
     * Copy this plan and its characteristics into a new draft revision.
     */
    public function newRevision(): self
    {
        if ($this->siblings()->where('status', 'draft')->exists() || $this->status === 'draft') {
            throw ValidationException::withMessages(['status' => __('A draft revision of this plan already exists.')]);
        }

        return DB::transaction(function () {
            $draft = $this->replicate(['status', 'approved_by', 'approved_at', 'created_by']);
            $draft->forceFill(['status' => 'draft', 'revision' => max($this->revision, (int) $this->siblings()->max('revision')) + 1])->save();

            foreach ($this->items as $item) {
                $draft->items()->create($item->replicate(['inspection_plan_id'])->getAttributes());
            }

            return $draft;
        });
    }
}
