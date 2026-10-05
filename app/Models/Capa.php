<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasSignatures;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Database\Factories\CapaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * A corrective or preventive action, worked through the eight disciplines (8D).
 * It moves forward one stage at a time; each stage needs its disciplines filled in.
 *
 * @property int $id
 * @property string $number
 * @property string $type
 * @property string $title
 * @property int|null $owner_id
 * @property CarbonImmutable|null $due_on
 * @property string $status
 * @property string|null $d1_team
 * @property string|null $d2_problem
 * @property string|null $d3_containment
 * @property string|null $d4_root_cause
 * @property string|null $d5_actions
 * @property string|null $d6_implementation
 * @property string|null $d7_prevention
 * @property string|null $d8_closure
 * @property CarbonImmutable|null $effectiveness_check_on
 * @property string|null $effectiveness_notes
 * @property int|null $effectiveness_verified_by
 * @property CarbonImmutable|null $effectiveness_verified_at
 * @property CarbonImmutable|null $closed_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Capa extends Model
{
    /** @use HasFactory<CapaFactory> */
    use Auditable, HasCreator, HasFactory, HasSignatures;

    public const TYPES = ['corrective', 'preventive'];

    public const STATUSES = ['open', 'investigating', 'implementing', 'verifying', 'closed'];

    /** @var array<string, string> */
    public const DISCIPLINES = [
        'd1_team' => 'D1 Team',
        'd2_problem' => 'D2 Problem description',
        'd3_containment' => 'D3 Containment',
        'd4_root_cause' => 'D4 Root cause (5 Why / fishbone)',
        'd5_actions' => 'D5 Chosen corrective actions',
        'd6_implementation' => 'D6 Implementation',
        'd7_prevention' => 'D7 Prevent recurrence',
        'd8_closure' => 'D8 Closure and recognition',
    ];

    /** Disciplines that must be filled in before the CAPA can move to each stage. */
    public const REQUIRED = [
        'investigating' => ['d1_team', 'd2_problem'],
        'implementing' => ['d3_containment', 'd4_root_cause', 'd5_actions'],
        'verifying' => ['d6_implementation'],
        'closed' => ['d7_prevention', 'd8_closure'],
    ];

    protected $guarded = ['id', 'number', 'created_by', 'status', 'effectiveness_verified_by', 'effectiveness_verified_at', 'closed_at'];

    protected $attributes = ['status' => 'open', 'type' => 'corrective'];

    protected static function booted(): void
    {
        static::creating(function (self $capa) {
            $capa->number ??= Sequence::next('CAPA');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date:Y-m-d',
            'effectiveness_check_on' => 'date:Y-m-d',
            'effectiveness_verified_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'effectiveness_verified_by')->withTrashed();
    }

    /**
     * @return BelongsToMany<Ncr, $this>
     */
    public function ncrs(): BelongsToMany
    {
        return $this->belongsToMany(Ncr::class);
    }

    /**
     * @return HasMany<CapaAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(CapaAction::class)->orderBy('id');
    }

    /**
     * @param  Builder<Capa>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', '!=', 'closed')->where('due_on', '<', CarbonImmutable::today()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'closed' && $this->due_on !== null && $this->due_on->lt(CarbonImmutable::today());
    }

    public function nextStatus(): ?string
    {
        $index = array_search($this->status, self::STATUSES, true);

        return self::STATUSES[(int) $index + 1] ?? null;
    }

    /**
     * What still stops the CAPA moving to its next stage. Empty means it can advance.
     *
     * @return list<string>
     */
    public function blockers(): array
    {
        $next = $this->nextStatus();

        if ($next === null) {
            return [];
        }

        $blockers = [];

        foreach (self::REQUIRED[$next] ?? [] as $field) {
            if (blank($this->{$field})) {
                $blockers[] = __('Fill in :discipline.', ['discipline' => __(self::DISCIPLINES[$field])]);
            }
        }

        if ($next === 'verifying' && $this->actions()->whereNull('done_at')->exists()) {
            $blockers[] = __('Finish every action.');
        }

        if ($next === 'closed' && $this->effectiveness_verified_at === null) {
            $blockers[] = __('Verify that the actions were effective.');
        }

        return $blockers;
    }

    public function advance(): void
    {
        if (($next = $this->nextStatus()) === null) {
            throw ValidationException::withMessages(['status' => __('This CAPA is already closed.')]);
        }

        if (($blockers = $this->blockers()) !== []) {
            throw ValidationException::withMessages(['status' => $blockers]);
        }

        $this->forceFill(['status' => $next, 'closed_at' => $next === 'closed' ? now() : null])->save();
    }

    /**
     * Record that the actions worked; only once the CAPA is being verified.
     */
    public function verifyEffectiveness(string $checkedOn, string $notes): void
    {
        if ($this->status !== 'verifying') {
            throw ValidationException::withMessages(['status' => __('Effectiveness is checked once the actions are implemented.')]);
        }

        $this->forceFill([
            'effectiveness_check_on' => $checkedOn,
            'effectiveness_notes' => $notes,
            'effectiveness_verified_by' => auth()->id(),
            'effectiveness_verified_at' => now(),
        ])->save();
    }
}
