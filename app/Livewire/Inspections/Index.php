<?php

namespace App\Livewire\Inspections;

use App\Livewire\Concerns\WithTable;
use App\Models\Inspection;
use App\Models\InspectionPlan;
use App\Models\Lot;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, InspectionPlan> $plans
 * @property-read Collection<int, Lot> $lots
 */
#[Title('Inspections')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['number', 'reference', 'plan.title', 'lot.lot_number'];

    #[Url(except: '')]
    public string $status = '';

    public string $plan_id = '';

    public string $lot_id = '';

    public string $reference = '';

    public string $quantity = '';

    public string $inspected_on = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPlanId(): void
    {
        $this->lot_id = '';
    }

    /**
     * @return LengthAwarePaginator<int, Inspection>
     */
    #[Computed]
    public function inspections(): LengthAwarePaginator
    {
        $query = Inspection::query()
            ->with(['plan:id,title,stage,part_id,material_id', 'plan.part:id,part_number,revision', 'plan.material:id,code', 'lot:id,lot_number', 'creator:id,name'])
            ->when(in_array($this->status, Inspection::STATUSES, true), fn (Builder $q) => $q->where('status', $this->status));

        return $this->paginateTable($query, self::SEARCHABLE, ['number', 'inspected_on'], 'inspected_on');
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return $this->countBy(Inspection::query(), 'status', self::SEARCHABLE);
    }

    /**
     * @return Collection<int, InspectionPlan>
     */
    #[Computed]
    public function plans(): Collection
    {
        return InspectionPlan::query()->where('status', 'approved')->with(['part', 'material'])->orderBy('title')->get();
    }

    /**
     * Lots that may be inspected under the chosen plan: the plan's material, from a verified
     * certificate, not expired.
     *
     * @return Collection<int, Lot>
     */
    #[Computed]
    public function lots(): Collection
    {
        $plan = $this->plans->firstWhere('id', (int) $this->plan_id);

        if (! $plan) {
            return new Collection;
        }

        return Lot::query()
            ->with('certificate:id,number')
            ->whereRelation('certificate', 'status', 'verified')
            ->when($plan->lotMaterialId(), fn (Builder $q, int $material) => $q->where('material_id', $material))
            ->where(fn (Builder $q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', now()->toDateString()))
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create-inspections');

        $this->reset('plan_id', 'lot_id', 'reference', 'quantity');
        $this->inspected_on = now()->toDateString();
        $this->resetValidation();
        Flux::modal('inspection-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-inspections');

        $validated = $this->validate([
            'plan_id' => ['required', 'integer', Rule::exists('inspection_plans', 'id')->where('status', 'approved')],
            'lot_id' => ['nullable', 'integer', Rule::exists('lots', 'id')],
            'reference' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999999'],
            'inspected_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], ['plan_id.exists' => __('Choose an approved plan.')], ['plan_id' => __('plan'), 'lot_id' => Lot::label()]);

        $plan = InspectionPlan::query()->with('part')->findOrFail((int) $validated['plan_id']);
        $lot = $validated['lot_id'] ? Lot::query()->with('certificate')->findOrFail((int) $validated['lot_id']) : null;

        if ($error = $this->lotError($plan, $lot, $validated['inspected_on'])) {
            $this->addError('lot_id', $error);

            return;
        }

        $inspection = Inspection::query()->create([
            'inspection_plan_id' => $plan->id,
            'lot_id' => $lot?->id,
            'reference' => $validated['reference'] ?: null,
            'quantity' => $validated['quantity'] ?: null,
            'inspected_on' => $validated['inspected_on'],
        ]);

        Flux::toast(variant: 'success', text: __('Inspection :number started.', ['number' => $inspection->number]));
        $this->redirectRoute('inspections.show', $inspection, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inspections.index');
    }

    /**
     * Receiving inspections need a lot; any lot used must be verified, unexpired and the right material.
     */
    private function lotError(InspectionPlan $plan, ?Lot $lot, string $inspectedOn): ?string
    {
        $replace = ['lot' => strtolower(Lot::label())];

        $key = match (true) {
            $lot === null => $plan->stage === 'receiving' ? 'A receiving inspection needs a :lot.' : null,
            $lot->certificate->status !== 'verified' => 'The certificate for this :lot is not verified.',
            $lot->expires_on !== null && $lot->expires_on->toDateString() < $inspectedOn => 'This :lot expired on :date.',
            $plan->lotMaterialId() !== null && $lot->material_id !== $plan->lotMaterialId() => 'This :lot is not the plan\'s material.',
            default => null,
        };

        if ($key === null) {
            return null;
        }

        $message = trans($key, [...$replace, 'date' => $lot?->expires_on?->format('Y-m-d')]);

        return is_string($message) ? $message : $key;
    }
}
