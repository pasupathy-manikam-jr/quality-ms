<?php

namespace App\Livewire\InspectionPlans;

use App\Livewire\Concerns\WithTable;
use App\Models\InspectionPlan;
use App\Models\Material;
use App\Models\Part;
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
 * @property-read Collection<int, Part> $parts
 * @property-read Collection<int, Material> $materials
 */
#[Title('Inspection plans')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['title', 'part.part_number', 'material.code'];

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $stage = '';

    public string $subject_type = 'part';

    public string $part_id = '';

    public string $material_id = '';

    public string $planStage = 'final';

    public string $title = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedStage(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, InspectionPlan>
     */
    #[Computed]
    public function plans(): LengthAwarePaginator
    {
        $query = $this->filtered()
            ->with(['part:id,part_number,revision', 'material:id,code'])
            ->withCount('items')
            ->when(in_array($this->status, InspectionPlan::STATUSES, true), fn (Builder $q) => $q->where('status', $this->status));

        return $this->paginateTable($query, self::SEARCHABLE, ['title', 'updated_at'], 'updated_at');
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return $this->countBy($this->filtered(), 'status', self::SEARCHABLE);
    }

    /**
     * @return Collection<int, Part>
     */
    #[Computed]
    public function parts(): Collection
    {
        return Part::query()->orderBy('part_number')->orderBy('revision')->get(['id', 'part_number', 'revision', 'name']);
    }

    /**
     * @return Collection<int, Material>
     */
    #[Computed]
    public function materials(): Collection
    {
        return Material::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function create(): void
    {
        $this->authorize('create-inspection-plans');

        $this->reset('subject_type', 'part_id', 'material_id', 'planStage', 'title');
        $this->resetValidation();
        Flux::modal('plan-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-inspection-plans');

        $validated = $this->validate([
            'subject_type' => ['required', Rule::in(['part', 'material'])],
            'part_id' => ['nullable', 'required_if:subject_type,part', 'integer', Rule::exists('parts', 'id')],
            'material_id' => ['nullable', 'required_if:subject_type,material', 'integer', Rule::exists('materials', 'id')],
            'planStage' => ['required', Rule::in(InspectionPlan::STAGES)],
            'title' => ['required', 'string', 'max:255'],
        ], attributes: ['part_id' => __('part'), 'material_id' => __('material'), 'planStage' => __('stage')]);

        $plan = new InspectionPlan([
            'part_id' => $validated['subject_type'] === 'part' ? (int) $validated['part_id'] : null,
            'material_id' => $validated['subject_type'] === 'material' ? (int) $validated['material_id'] : null,
            'stage' => $validated['planStage'],
            'title' => $validated['title'],
        ]);

        if ($plan->siblings()->exists()) {
            $this->addError('planStage', __('A plan for this :stage stage already exists. Open it and create a new revision instead.', ['stage' => __($plan->stage)]));

            return;
        }

        $plan->save();

        Flux::toast(variant: 'success', text: __('Plan created. Now add its characteristics.'));
        $this->redirectRoute('inspection-plans.show', $plan, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inspection-plans.index');
    }

    /**
     * @return Builder<InspectionPlan>
     */
    private function filtered(): Builder
    {
        return InspectionPlan::query()
            ->when(in_array($this->stage, InspectionPlan::STAGES, true), fn (Builder $q) => $q->where('stage', $this->stage));
    }
}
