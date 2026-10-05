<?php

namespace App\Livewire\Materials;

use App\Livewire\Concerns\WithTable;
use App\Models\Material;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Materials')]
class Index extends Component
{
    use WithTable;

    #[Locked]
    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $specification = '';

    public string $size_label = '';

    /**
     * @return LengthAwarePaginator<int, Material>
     */
    #[Computed]
    public function materials(): LengthAwarePaginator
    {
        return $this->paginateTable(
            Material::query()->withCount(['limits', 'lots']),
            ['code', 'name', 'specification'],
            ['code', 'name', 'created_at'],
            'code',
        );
    }

    public function create(): void
    {
        $this->authorize('create-materials');

        $this->resetForm();
        Flux::modal('material-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('edit-materials');

        $material = Material::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $material->id;
        $this->code = $material->code;
        $this->name = $material->name;
        $this->specification = (string) $material->specification;
        $this->size_label = (string) $material->size_label;

        Flux::modal('material-form')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit-materials' : 'create-materials');

        $material = $this->editingId ? Material::query()->findOrFail($this->editingId) : new Material;

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique(Material::class)->ignore($material->id)],
            'name' => ['required', 'string', 'max:255'],
            'specification' => ['nullable', 'string', 'max:255'],
            'size_label' => ['nullable', 'string', 'max:50'],
        ]);

        $material->fill(array_map(fn ($value) => $value === '' ? null : $value, $validated))->save();

        Flux::modal('material-form')->close();
        $this->resetForm();

        if ($material->wasRecentlyCreated) {
            Flux::toast(variant: 'success', text: __('Material created. Now add its limits.'));
            $this->redirectRoute('materials.show', $material, navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('Material updated.'));
    }

    public function render(): View
    {
        return view('livewire.materials.index');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'name', 'specification', 'size_label');
        $this->resetValidation();
    }
}
