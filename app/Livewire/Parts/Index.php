<?php

namespace App\Livewire\Parts;

use App\Livewire\Concerns\WithTable;
use App\Models\Material;
use App\Models\Part;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, Material> $materials
 */
#[Title('Parts')]
class Index extends Component
{
    use WithTable;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $part_number = '';

    public string $revision = 'A';

    public string $name = '';

    public string $material_id = '';

    /**
     * @return LengthAwarePaginator<int, Part>
     */
    #[Computed]
    public function parts(): LengthAwarePaginator
    {
        return $this->paginateTable(
            Part::query()->with('material:id,code')->withCount('plans'),
            ['part_number', 'name'],
            ['part_number', 'name', 'created_at'],
            'part_number',
        );
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
        $this->authorize('create-parts');

        $this->resetForm();
        Flux::modal('part-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('edit-parts');

        $part = Part::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $part->id;
        $this->part_number = $part->part_number;
        $this->revision = $part->revision;
        $this->name = $part->name;
        $this->material_id = (string) $part->material_id;

        Flux::modal('part-form')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit-parts' : 'create-parts');

        $part = $this->editingId ? Part::query()->findOrFail($this->editingId) : new Part;

        $validated = $this->validate([
            'part_number' => ['required', 'string', 'max:100'],
            'revision' => ['required', 'string', 'max:20', Rule::unique('parts')->where('part_number', $this->part_number)->ignore($part->id)],
            'name' => ['required', 'string', 'max:255'],
            'material_id' => ['nullable', 'integer', Rule::exists('materials', 'id')],
        ], ['revision.unique' => __('This part number already has that revision.')], ['material_id' => __('material')]);

        $part->fill([...$validated, 'material_id' => $validated['material_id'] ?: null])->save();

        Flux::modal('part-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? __('Part updated.') : __('Part created.'));
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete-parts');

        $this->deletingId = Part::query()->findOrFail($id)->id;
        Flux::modal('confirm-part-delete')->show();
    }

    public function delete(): void
    {
        $this->authorize('delete-parts');

        $part = Part::query()->findOrFail($this->deletingId);
        Flux::modal('confirm-part-delete')->close();
        $this->deletingId = null;

        if ($part->plans()->exists()) {
            Flux::toast(variant: 'danger', text: __('This part has inspection plans, so it cannot be deleted.'));

            return;
        }

        $part->delete();
        Flux::toast(variant: 'success', text: __('Part deleted.'));
    }

    public function render(): View
    {
        return view('livewire.parts.index');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'part_number', 'revision', 'name', 'material_id');
        $this->resetValidation();
    }
}
