<?php

namespace App\Livewire\Inspections;

use App\Livewire\Concerns\SignsRecords;
use App\Models\Gauge;
use App\Models\Inspection;
use App\Models\InspectionPlanItem;
use App\Models\InspectionReading;
use App\Support\Decimal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Inspection $inspection
 * @property-read Collection<int, Gauge> $usableGauges
 */
class Show extends Component
{
    use SignsRecords;

    #[Locked]
    public int $inspectionId;

    /** @var array<string, string> "itemId-sample" => measured value, or "ok" / "nok" */
    public array $values = [];

    /** @var array<int|string, string> itemId => gauge id */
    public array $gauges = [];

    public function mount(Inspection $inspection): void
    {
        $this->inspectionId = $inspection->id;

        foreach ($this->inspection->readings as $reading) {
            $key = "{$reading->inspection_plan_item_id}-{$reading->sample}";
            $this->values[$key] = $reading->value !== null ? Decimal::format($reading->value) : ($reading->is_ok ? 'ok' : 'nok');
            $this->gauges[$reading->inspection_plan_item_id] ??= (string) $reading->gauge_id;
        }
    }

    #[Computed]
    public function inspection(): Inspection
    {
        return Inspection::query()
            ->with(['plan.items', 'plan.part', 'plan.material', 'lot.certificate', 'readings.gauge', 'creator', 'signatures'])
            ->findOrFail($this->inspectionId);
    }

    /**
     * Gauges that may be used today: active, and not past their calibration date.
     *
     * @return Collection<int, Gauge>
     */
    #[Computed]
    public function usableGauges(): Collection
    {
        return Gauge::query()->where('status', 'active')->orderBy('code')->get()->filter->isUsable()->values();
    }

    /**
     * @return array<string, string>
     */
    protected function signedActions(): array
    {
        return ['complete' => 'inspected'];
    }

    public function save(): void
    {
        $this->authorizeEdit();

        $this->storeReadings();

        unset($this->inspection);
        Flux::toast(variant: 'success', text: __('Readings saved.'));
    }

    public function complete(): void
    {
        $this->authorizeEdit();

        $this->storeReadings();
        $inspection = $this->inspection;
        $this->signAs($inspection, 'inspected', fn () => $inspection->complete());

        unset($this->inspection);
        $failed = $this->inspection->status === 'failed';
        Flux::toast(variant: $failed ? 'danger' : 'success', text: $failed ? __('Inspection failed.') : __('Inspection passed.'));
    }

    public function render(): View
    {
        return view('livewire.inspections.show')->title($this->inspection->number);
    }

    /**
     * Validate every entered value and save it as a reading; a cleared field removes its reading.
     * Measured values need a gauge that is usable today.
     */
    private function storeReadings(): void
    {
        $inspection = $this->inspection;
        $usable = $this->usableGauges->keyBy('id');
        $errors = [];
        $rows = [];

        foreach ($inspection->plan->items as $item) {
            $gaugeId = (int) ($this->gauges[$item->id] ?? 0);
            $entered = false;

            for ($sample = 1; $sample <= $item->sample_size; $sample++) {
                $key = "{$item->id}-{$sample}";
                $raw = trim((string) ($this->values[$key] ?? ''));

                if ($raw === '') {
                    $rows[$key] = null;

                    continue;
                }

                $entered = true;
                [$value, $isOk, $error] = $this->parse($item, $raw);

                if ($error) {
                    $errors["values.{$key}"] = $error;

                    continue;
                }

                $rows[$key] = ['item' => $item, 'sample' => $sample, 'value' => $value, 'is_ok' => $isOk,
                    'passed' => $isOk ?? $item->accepts((string) $value)];
            }

            if ($entered && $item->isNumeric() && ! $usable->has($gaugeId)) {
                $errors["gauges.{$item->id}"] = $gaugeId
                    ? __('This gauge is not in calibration, so it cannot be used.')
                    : __('Choose the gauge used for :characteristic.', ['characteristic' => $item->characteristic]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($inspection, $rows) {
            $existing = $inspection->readings->keyBy(fn (InspectionReading $r) => "{$r->inspection_plan_item_id}-{$r->sample}");

            foreach ($rows as $key => $row) {
                if ($row === null) {
                    $existing->get($key)?->delete();

                    continue;
                }

                $gauge = (int) ($this->gauges[$row['item']->id] ?? 0);

                $inspection->readings()->updateOrCreate(
                    ['inspection_plan_item_id' => $row['item']->id, 'sample' => $row['sample']],
                    ['value' => $row['value'], 'is_ok' => $row['is_ok'], 'passed' => $row['passed'], 'gauge_id' => $row['item']->isNumeric() ? $gauge : null],
                );
            }
        });
    }

    /**
     * @return array{0: string|null, 1: bool|null, 2: string|null} value, is_ok, error
     */
    private function parse(InspectionPlanItem $item, string $raw): array
    {
        if (! $item->isNumeric()) {
            return in_array($raw, ['ok', 'nok'], true) ? [null, $raw === 'ok', null] : [null, null, __('Choose OK or not OK.')];
        }

        if (! preg_match('/^[+-]?(\d+\.?\d{0,6}|\.\d{1,6})$/', $raw) || strlen($raw) > 20) {
            return [null, null, __('Enter a number (up to 6 decimals).')];
        }

        return [$raw, null, null];
    }

    private function authorizeEdit(): void
    {
        $this->authorize('edit-inspections');
        abort_unless($this->inspection->isEditable(), 403, __('A completed inspection cannot be changed.'));
    }
}
