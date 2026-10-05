<?php

namespace App\Livewire\Gauges;

use App\Models\Calibration;
use App\Models\Gauge;
use App\Models\Inspection;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * @property-read Gauge $gauge
 * @property-read Collection<int, Inspection> $suspectInspections
 */
class Show extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $gaugeId;

    #[Locked]
    public string $pendingStatus = '';

    public string $performed_on = '';

    public string $performed_by = '';

    public string $result = 'pass';

    public string $as_found = '';

    public string $as_left = '';

    public ?TemporaryUploadedFile $file = null;

    public function mount(Gauge $gauge): void
    {
        $this->gaugeId = $gauge->id;
    }

    #[Computed]
    public function gauge(): Gauge
    {
        return Gauge::query()->with(['owner', 'calibrations.creator'])->findOrFail($this->gaugeId);
    }

    /**
     * @return Collection<int, Inspection>
     */
    #[Computed]
    public function suspectInspections(): Collection
    {
        return $this->gauge->suspectInspections();
    }

    public function openCalibration(): void
    {
        $this->authorize('calibrate-gauges');

        $this->reset('performed_on', 'performed_by', 'result', 'as_found', 'as_left', 'file');
        $this->performed_on = now()->toDateString();
        $this->resetValidation();
        Flux::modal('calibration-form')->show();
    }

    public function saveCalibration(): void
    {
        $this->authorize('calibrate-gauges');

        $this->validate([
            'performed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'performed_by' => ['required', 'string', 'max:255'],
            'result' => ['required', Rule::in(Gauge::RESULTS)],
            'as_found' => ['nullable', 'required_if:result,adjusted,fail', 'string', 'max:2000'],
            'as_left' => ['nullable', 'required_if:result,adjusted', 'string', 'max:2000'],
            'file' => Calibration::uploadRules(),
        ], [
            'as_found.required_if' => __('Say what was found when the result is :result.', ['result' => __($this->result)]),
            'as_left.required_if' => __('Say how the gauge was left after adjustment.'),
        ]);

        $calibration = $this->gauge->recordCalibration([
            'performed_on' => $this->performed_on,
            'performed_by' => $this->performed_by,
            'result' => $this->result,
            'as_found' => $this->as_found ?: null,
            'as_left' => $this->result === 'adjusted' ? ($this->as_left ?: null) : null,
        ], $this->file);

        unset($this->gauge, $this->suspectInspections);
        Flux::modal('calibration-form')->close();
        Flux::toast(
            variant: $calibration->result === 'fail' ? 'warning' : 'success',
            text: $calibration->result === 'fail'
                ? __('Calibration failed. The gauge is out of service until it passes a calibration.')
                : __('Calibration recorded. Next due :date.', ['date' => $calibration->next_due_on?->format('Y-m-d')]),
        );
    }

    public function confirmStatus(string $status): void
    {
        $this->authorize('edit-gauges');
        abort_unless(in_array($status, Gauge::TRANSITIONS[$this->gauge->status] ?? [], true), 403);

        $this->pendingStatus = $status;
        Flux::modal('confirm-gauge-status')->show();
    }

    public function changeStatus(): void
    {
        $this->authorize('edit-gauges');

        $this->gauge->transitionTo($this->pendingStatus);

        unset($this->gauge);
        $this->pendingStatus = '';
        Flux::modal('confirm-gauge-status')->close();
        Flux::toast(variant: 'success', text: __('Gauge status changed.'));
    }

    public function render(): View
    {
        return view('livewire.gauges.show')->title($this->gauge->code);
    }
}
