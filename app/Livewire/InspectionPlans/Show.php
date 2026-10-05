<?php

namespace App\Livewire\InspectionPlans;

use App\Livewire\Concerns\SignsRecords;
use App\Models\InspectionPlan;
use App\Models\InspectionPlanItem;
use App\Support\Decimal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read InspectionPlan $plan
 * @property-read Collection<int, InspectionPlan> $revisions
 */
class Show extends Component
{
    use SignsRecords;

    #[Locked]
    public int $planId;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $characteristic = '';

    public string $kind = 'numeric';

    public string $method = '';

    public string $unit = '';

    public string $nominal = '';

    public string $min = '';

    public string $max = '';

    public string $sample_size = '1';

    public bool $is_critical = false;

    public function mount(InspectionPlan $plan): void
    {
        $this->planId = $plan->id;
    }

    #[Computed]
    public function plan(): InspectionPlan
    {
        return InspectionPlan::query()->with(['part', 'material', 'items', 'approver', 'creator', 'signatures'])->withCount('inspections')->findOrFail($this->planId);
    }

    /**
     * @return Collection<int, InspectionPlan>
     */
    #[Computed]
    public function revisions(): Collection
    {
        return $this->plan->siblings()->orderByDesc('revision')->get(['id', 'revision', 'status', 'approved_at']);
    }

    /**
     * @return array<string, string>
     */
    protected function signedActions(): array
    {
        return ['approve' => 'approved'];
    }

    public function create(): void
    {
        $this->authorizeEdit();

        $this->resetForm();
        Flux::modal('item-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorizeEdit();

        $item = $this->item($id);

        $this->resetForm();
        $this->editingId = $item->id;
        $this->characteristic = $item->characteristic;
        $this->kind = $item->kind;
        $this->method = (string) $item->method;
        $this->unit = (string) $item->unit;
        $this->nominal = $item->nominal !== null ? Decimal::format($item->nominal) : '';
        $this->min = $item->min !== null ? Decimal::format($item->min) : '';
        $this->max = $item->max !== null ? Decimal::format($item->max) : '';
        $this->sample_size = (string) $item->sample_size;
        $this->is_critical = $item->is_critical;

        Flux::modal('item-form')->show();
    }

    public function save(): void
    {
        $this->authorizeEdit();

        $number = ['nullable', 'numeric', 'decimal:0,6', 'min:-999999999999', 'max:999999999999'];
        $numeric = $this->kind === 'numeric';

        $validated = $this->validate([
            'characteristic' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(InspectionPlanItem::KINDS)],
            'method' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:20'],
            'nominal' => $number,
            'min' => $numeric ? [...$number, 'required_without:max'] : ['nullable'],
            'max' => $numeric ? [...$number, 'required_without:min'] : ['nullable'],
            'sample_size' => ['required', 'integer', 'min:1', 'max:50'],
            'is_critical' => ['boolean'],
        ], [
            'min.required_without' => __('Enter a minimum, a maximum or both.'),
            'max.required_without' => __('Enter a minimum, a maximum or both.'),
        ]);

        $values = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        if (! $numeric) {
            $values = [...$values, 'unit' => null, 'nominal' => null, 'min' => null, 'max' => null];
        } elseif ($error = $this->rangeError($values['nominal'], $values['min'], $values['max'])) {
            $this->addError(...$error);

            return;
        }

        $item = $this->editingId
            ? $this->item($this->editingId)
            : new InspectionPlanItem(['inspection_plan_id' => $this->planId, 'position' => (int) $this->plan->items->max('position') + 1]);
        $item->fill($values)->save();

        unset($this->plan);
        Flux::modal('item-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? __('Characteristic updated.') : __('Characteristic added.'));
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeEdit();

        $this->deletingId = $this->item($id)->id;
        Flux::modal('confirm-item-delete')->show();
    }

    public function delete(): void
    {
        $this->authorizeEdit();

        $this->item((int) $this->deletingId)->delete();

        unset($this->plan);
        $this->deletingId = null;
        Flux::modal('confirm-item-delete')->close();
        Flux::toast(variant: 'success', text: __('Characteristic removed.'));
    }

    public function approve(): void
    {
        $this->authorize('approve-inspection-plans');

        $this->signAs($this->plan, 'approved', fn () => $this->plan->approve());

        unset($this->plan, $this->revisions);
        Flux::toast(variant: 'success', text: __('Plan approved. Inspections can now use it.'));
    }

    public function makeObsolete(): void
    {
        $this->authorize('approve-inspection-plans');

        $this->plan->makeObsolete();

        unset($this->plan);
        Flux::modal('confirm-obsolete')->close();
        Flux::toast(variant: 'success', text: __('Plan made obsolete.'));
    }

    public function newRevision(): void
    {
        $this->authorize('create-inspection-plans');

        $draft = $this->plan->newRevision();

        Flux::toast(variant: 'success', text: __('Revision :n created as a draft.', ['n' => $draft->revision]));
        $this->redirectRoute('inspection-plans.show', $draft, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inspection-plans.show')->title($this->plan->title);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function rangeError(?string $nominal, ?string $min, ?string $max): ?array
    {
        return match (true) {
            $min !== null && $max !== null && Decimal::compare($max, $min) < 0 => ['max', __('The maximum must not be below the minimum.')],
            $nominal !== null && $min !== null && Decimal::compare($nominal, $min) < 0 => ['nominal', __('The nominal must be within the limits.')],
            $nominal !== null && $max !== null && Decimal::compare($nominal, $max) > 0 => ['nominal', __('The nominal must be within the limits.')],
            default => null,
        };
    }

    /**
     * Characteristics only change while the plan is a draft.
     */
    private function authorizeEdit(): void
    {
        $this->authorize('edit-inspection-plans');
        abort_unless($this->plan->isEditable(), 403, __('Approved plans change through a new revision.'));
    }

    private function item(int $id): InspectionPlanItem
    {
        return InspectionPlanItem::query()->where('inspection_plan_id', $this->planId)->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'characteristic', 'kind', 'method', 'unit', 'nominal', 'min', 'max', 'sample_size', 'is_critical');
        $this->resetValidation();
    }
}
