<?php

namespace App\Livewire\Materials;

use App\Models\Material;
use App\Models\MaterialLimit;
use App\Support\Decimal;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $materialId;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $property = '';

    public string $unit = '';

    public string $min = '';

    public string $max = '';

    public string $size_from = '';

    public string $size_to = '';

    public function mount(Material $material): void
    {
        $this->materialId = $material->id;
    }

    public function material(): Material
    {
        return Material::query()->with('limits')->withCount('lots')->findOrFail($this->materialId);
    }

    public function create(): void
    {
        $this->authorize('edit-materials');

        $this->resetForm();
        Flux::modal('limit-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('edit-materials');

        $limit = $this->limit($id);

        $this->resetForm();
        $this->editingId = $limit->id;
        $this->property = $limit->property;
        $this->unit = (string) $limit->unit;
        $this->min = (string) $limit->min;
        $this->max = (string) $limit->max;
        $this->size_from = (string) $limit->size_from;
        $this->size_to = (string) $limit->size_to;

        Flux::modal('limit-form')->show();
    }

    public function save(): void
    {
        $this->authorize('edit-materials');

        $number = ['nullable', 'numeric', 'decimal:0,6', 'min:-999999999999', 'max:999999999999'];
        $size = ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999'];

        $validated = $this->validate([
            'property' => ['required', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:20'],
            'min' => [...$number, 'required_without:max'],
            'max' => [...$number, 'required_without:min'],
            'size_from' => $size,
            'size_to' => $size,
        ], [
            'min.required_without' => __('Enter a minimum, a maximum or both.'),
            'max.required_without' => __('Enter a minimum, a maximum or both.'),
        ]);

        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        foreach (['max' => 'min', 'size_to' => 'size_from'] as $upper => $lower) {
            if ($validated[$upper] !== null && $validated[$lower] !== null && Decimal::compare($validated[$upper], $validated[$lower]) < 0) {
                $this->addError($upper, __('This must be greater than or equal to the value before it.'));

                return;
            }
        }

        $overlapping = $this->material()->limits
            ->where('property', $validated['property'])
            ->reject(fn (MaterialLimit $limit) => $limit->id === $this->editingId)
            ->first(fn (MaterialLimit $limit) => $limit->overlaps($validated['size_from'], $validated['size_to']));

        if ($overlapping) {
            $this->addError('size_from', __('This size range overlaps the :property limit for sizes :range.', ['property' => $overlapping->property, 'range' => $overlapping->sizeLabel()]));

            return;
        }

        $limit = $this->editingId ? $this->limit($this->editingId) : new MaterialLimit(['material_id' => $this->materialId]);
        $limit->fill($validated)->save();

        Flux::modal('limit-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? __('Limit updated.') : __('Limit added.'));
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('edit-materials');

        $this->deletingId = $this->limit($id)->id;
        Flux::modal('confirm-limit-delete')->show();
    }

    public function delete(): void
    {
        $this->authorize('edit-materials');

        $this->limit((int) $this->deletingId)->delete();
        $this->deletingId = null;

        Flux::modal('confirm-limit-delete')->close();
        Flux::toast(variant: 'success', text: __('Limit removed.'));
    }

    public function render(): View
    {
        $material = $this->material();

        return view('livewire.materials.show', ['material' => $material])->title($material->code);
    }

    private function limit(int $id): MaterialLimit
    {
        return MaterialLimit::query()->where('material_id', $this->materialId)->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'property', 'unit', 'min', 'max', 'size_from', 'size_to');
        $this->resetValidation();
    }
}
