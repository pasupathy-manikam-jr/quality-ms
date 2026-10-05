<?php

namespace App\Livewire\Gauges;

use App\Livewire\Concerns\WithTable;
use App\Models\Gauge;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read Collection<int, User> $owners
 */
#[Title('Gauges')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['code', 'description', 'type', 'location'];

    #[Url(except: '')]
    public string $state = '';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $code = '';

    public string $description = '';

    public string $type = '';

    public string $measuring_range = '';

    public string $resolution = '';

    public string $location = '';

    public string $owner_id = '';

    public string $interval_days = '365';

    public function updatedState(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Gauge>
     */
    #[Computed]
    public function gauges(): LengthAwarePaginator
    {
        return $this->paginateTable($this->listQuery(), self::SEARCHABLE, ['code', 'description', 'next_due_on'], 'next_due_on');
    }

    /**
     * Count per state; states are derived from dates, so each is its own small query.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stateCounts(): array
    {
        return collect(Gauge::STATES)
            ->mapWithKeys(fn (string $state) => [$state => $this->applySearch(Gauge::query()->inState($state), self::SEARCHABLE)->count()])
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function owners(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->authorize('create-gauges');

        $this->resetForm();
        Flux::modal('gauge-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('edit-gauges');

        $gauge = Gauge::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $gauge->id;
        $this->code = $gauge->code;
        $this->description = $gauge->description;
        $this->type = (string) $gauge->type;
        $this->measuring_range = (string) $gauge->measuring_range;
        $this->resolution = (string) $gauge->resolution;
        $this->location = (string) $gauge->location;
        $this->owner_id = (string) $gauge->owner_id;
        $this->interval_days = (string) $gauge->interval_days;

        Flux::modal('gauge-form')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit-gauges' : 'create-gauges');

        $gauge = $this->editingId ? Gauge::query()->findOrFail($this->editingId) : new Gauge;

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique(Gauge::class)->ignore($gauge->id)],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'measuring_range' => ['nullable', 'string', 'max:100'],
            'resolution' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ], attributes: ['owner_id' => __('owner'), 'interval_days' => __('interval')]);

        $gauge->fill(array_map(fn ($value) => $value === '' ? null : $value, $validated))->save();

        Flux::modal('gauge-form')->close();
        $this->resetForm();

        if ($gauge->wasRecentlyCreated) {
            Flux::toast(variant: 'success', text: __('Gauge added. Record its calibration before it is used.'));
            $this->redirectRoute('gauges.show', $gauge, navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('Gauge updated. A new interval applies from the next calibration.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete-gauges');

        $this->deletingId = Gauge::query()->findOrFail($id)->id;
        Flux::modal('confirm-gauge-delete')->show();
    }

    public function delete(): void
    {
        $this->authorize('delete-gauges');

        $gauge = Gauge::query()->findOrFail($this->deletingId);
        Flux::modal('confirm-gauge-delete')->close();
        $this->deletingId = null;

        if ($gauge->calibrations()->exists()) {
            Flux::toast(variant: 'danger', text: __('This gauge has calibration records, so it cannot be deleted. Retire it instead.'));

            return;
        }

        $gauge->delete();
        Flux::toast(variant: 'success', text: __('Gauge deleted.'));
    }

    /**
     * The list as on screen (search excluded; WithTable adds it), shared by the table and the export.
     *
     * @return Builder<Gauge>
     */
    private function listQuery(): Builder
    {
        return Gauge::query()
            ->with('owner:id,name')
            ->when(in_array($this->state, Gauge::STATES, true), fn ($q) => $q->inState($this->state));
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv($this->listQuery(), self::SEARCHABLE, 'gauges', [
            'Code' => fn (Gauge $g) => $g->code,
            'Description' => fn (Gauge $g) => $g->description,
            'Type' => fn (Gauge $g) => $g->type,
            'Range' => fn (Gauge $g) => $g->measuring_range,
            'Resolution' => fn (Gauge $g) => $g->resolution,
            'Location' => fn (Gauge $g) => $g->location,
            'Owner' => fn (Gauge $g) => $g->owner?->name,
            'Calibration interval (days)' => fn (Gauge $g) => $g->interval_days,
            'Next due' => fn (Gauge $g) => $g->next_due_on?->format('Y-m-d'),
            'Status' => fn (Gauge $g) => __(Str::headline($g->state())),
        ]);
    }

    public function render(): View
    {
        return view('livewire.gauges.index');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'description', 'type', 'measuring_range', 'resolution', 'location', 'owner_id', 'interval_days');
        $this->resetValidation();
    }
}
