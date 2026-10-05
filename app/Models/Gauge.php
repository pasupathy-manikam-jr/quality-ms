<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasCreator;
use Carbon\CarbonImmutable;
use Database\Factories\GaugeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A measuring instrument that must be calibrated every interval_days.
 *
 * Only the lifecycle (active, out-of-service, retired) is stored. Whether an active gauge
 * is calibrated, due or overdue is worked out from next_due_on, so it is never stale.
 *
 * @property int $id
 * @property string $code
 * @property string $description
 * @property string|null $type
 * @property string|null $measuring_range
 * @property string|null $resolution
 * @property string|null $location
 * @property int|null $owner_id
 * @property int $interval_days
 * @property string $status
 * @property CarbonImmutable|null $next_due_on
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Gauge extends Model
{
    /** @use HasFactory<GaugeFactory> */
    use Auditable, HasCreator, HasFactory;

    public const STATUSES = ['active', 'out-of-service', 'retired'];

    /** What the list shows: active gauges split by due date, plus the stored lifecycle states. */
    public const STATES = ['calibrated', 'due', 'overdue', 'out-of-service', 'retired'];

    /** Manual moves. Out-of-service gauges only return to active through a passing calibration. */
    public const TRANSITIONS = [
        'active' => ['out-of-service', 'retired'],
        'out-of-service' => ['retired'],
    ];

    public const RESULTS = ['pass', 'adjusted', 'fail'];

    protected $guarded = ['id', 'created_by', 'status', 'next_due_on'];

    /** Matches the column default, so a gauge created in code knows its status before a reload. */
    protected $attributes = ['status' => 'active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interval_days' => 'integer',
            'next_due_on' => 'date:Y-m-d',
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
     * @return HasMany<Calibration, $this>
     */
    public function calibrations(): HasMany
    {
        return $this->hasMany(Calibration::class)->orderByDesc('performed_on')->orderByDesc('id');
    }

    /**
     * The last day that still counts as "due soon" rather than calibrated.
     */
    public static function dueWindowEnd(): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays((int) config('qms.due_soon_days'));
    }

    public function state(): string
    {
        return match (true) {
            $this->status !== 'active' => $this->status,
            $this->next_due_on === null || $this->next_due_on->lt(CarbonImmutable::today()) => 'overdue',
            $this->next_due_on->lte(self::dueWindowEnd()) => 'due',
            default => 'calibrated',
        };
    }

    /**
     * Whether readings may be taken with this gauge today.
     */
    public function isUsable(): bool
    {
        return in_array($this->state(), ['calibrated', 'due'], true);
    }

    /**
     * @param  Builder<Gauge>  $query
     */
    public function scopeInState(Builder $query, string $state): void
    {
        $today = CarbonImmutable::today()->toDateString();

        match ($state) {
            'overdue' => $query->where('status', 'active')->where(fn (Builder $q) => $q->whereNull('next_due_on')->orWhere('next_due_on', '<', $today)),
            'due' => $query->where('status', 'active')->whereBetween('next_due_on', [$today, self::dueWindowEnd()->toDateString()]),
            'calibrated' => $query->where('status', 'active')->where('next_due_on', '>', self::dueWindowEnd()->toDateString()),
            default => $query->where('status', $state),
        };
    }

    /**
     * Record a calibration and update the gauge: a pass or adjustment sets the next due date and
     * returns the gauge to service; a failure takes it out of service.
     *
     * @param  array{performed_on: string, performed_by: string, result: string, as_found?: string|null, as_left?: string|null}  $data
     */
    public function recordCalibration(array $data, ?UploadedFile $file = null): Calibration
    {
        return DB::transaction(function () use ($data, $file) {
            $gauge = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($gauge->status === 'retired') {
                throw ValidationException::withMessages(['result' => __('A retired gauge cannot be calibrated.')]);
            }

            $latest = $gauge->calibrations()->value('performed_on');

            if ($latest !== null && CarbonImmutable::parse($data['performed_on'])->lt(CarbonImmutable::parse($latest))) {
                throw ValidationException::withMessages(['performed_on' => __('A later calibration (:date) is already recorded.', ['date' => CarbonImmutable::parse($latest)->format('Y-m-d')])]);
            }

            $passed = $data['result'] !== 'fail';
            $nextDue = $passed ? CarbonImmutable::parse($data['performed_on'])->addDays($gauge->interval_days) : null;

            $calibration = new Calibration([...$data, 'gauge_id' => $gauge->id, 'next_due_on' => $nextDue]);

            if ($file) {
                $calibration->attachUpload($file);
            }

            $calibration->save();

            $gauge->forceFill([
                'status' => $passed ? 'active' : 'out-of-service',
                'next_due_on' => $passed ? $nextDue : $gauge->next_due_on,
            ])->save();

            $this->setRawAttributes($gauge->getAttributes(), true);

            if (! $passed) {
                $suspect = $gauge->suspectInspections();

                Ncr::raise($calibration, [
                    'source' => 'calibration',
                    'title' => __('Gauge :code failed calibration', ['code' => $gauge->code]),
                    'description' => trim(($calibration->as_found ?? '')."\n".trans_choice(':count inspection used it since its last good calibration.|:count inspections used it since its last good calibration.', $suspect->count())),
                    'severity' => $suspect->isEmpty() ? 'minor' : 'major',
                ]);
            }

            return $calibration;
        });
    }

    /**
     * Inspections that may be wrong because this gauge failed its latest calibration: every
     * inspection that used it on or after its last good calibration (or ever, if it never had one).
     *
     * @return Collection<int, Inspection>
     */
    public function suspectInspections(): Collection
    {
        $latest = $this->calibrations()->first();

        if ($latest === null || $latest->result !== 'fail') {
            return new Collection;
        }

        $lastGood = $this->calibrations()->where('result', '!=', 'fail')->value('performed_on');

        return Inspection::query()
            ->whereHas('readings', fn (Builder $q) => $q->where('gauge_id', $this->id))
            ->when($lastGood, fn (Builder $q) => $q->where('inspected_on', '>=', $lastGood))
            ->with('plan.part', 'plan.material', 'lot')
            ->orderByDesc('inspected_on')
            ->get();
    }

    public function transitionTo(string $status): void
    {
        if (! in_array($status, self::TRANSITIONS[$this->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => __('A :from gauge cannot be marked :to.', ['from' => __($this->status), 'to' => __($status)])]);
        }

        $this->forceFill(['status' => $status])->save();
    }
}
