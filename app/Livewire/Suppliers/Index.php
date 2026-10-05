<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\WithTable;
use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Suppliers')]
class Index extends Component
{
    use WithTable;

    #[Url(except: '')]
    public string $approval = '';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $code = '';

    public string $name = '';

    public string $contact_name = '';

    public string $email = '';

    public string $phone = '';

    public bool $is_approved = false;

    public string $approved_on = '';

    public function updatedApproval(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Supplier>
     */
    #[Computed]
    public function suppliers(): LengthAwarePaginator
    {
        $query = Supplier::query()
            ->withCount([
                'certificates',
                'certificates as verified_count' => fn ($q) => $q->where('status', 'verified'),
                'certificates as rejected_count' => fn ($q) => $q->where('status', 'rejected'),
                'ncrs as recent_ncrs_count' => fn ($q) => $q->where('created_at', '>=', now()->subYear())->where('status', '!=', 'cancelled'),
            ])
            ->when($this->approval !== '', fn ($q) => $q->where('is_approved', $this->approval === 'approved'));

        return $this->paginateTable($query, ['code', 'name', 'contact_name', 'email'], ['code', 'name', 'created_at'], 'code');
    }

    public function create(): void
    {
        $this->authorize('create-suppliers');

        $this->resetForm();
        Flux::modal('supplier-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('edit-suppliers');

        $supplier = Supplier::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $supplier->id;
        $this->fill($supplier->only(['code', 'name', 'is_approved']));
        $this->contact_name = (string) $supplier->contact_name;
        $this->email = (string) $supplier->email;
        $this->phone = (string) $supplier->phone;
        $this->approved_on = (string) $supplier->approved_on?->format('Y-m-d');

        Flux::modal('supplier-form')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit-suppliers' : 'create-suppliers');

        $supplier = $this->editingId ? Supplier::query()->findOrFail($this->editingId) : new Supplier;

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique(Supplier::class)->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_approved' => ['boolean'],
            'approved_on' => ['nullable', 'required_if_accepted:is_approved', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $supplier->fill([
            ...$validated,
            'approved_on' => $validated['is_approved'] ? $validated['approved_on'] : null,
        ])->save();

        Flux::modal('supplier-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? __('Supplier updated.') : __('Supplier created.'));
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete-suppliers');

        $this->deletingId = Supplier::query()->findOrFail($id)->id;
        Flux::modal('confirm-supplier-delete')->show();
    }

    public function delete(): void
    {
        $this->authorize('delete-suppliers');

        $supplier = Supplier::query()->findOrFail($this->deletingId);
        Flux::modal('confirm-supplier-delete')->close();
        $this->deletingId = null;

        if ($supplier->certificates()->exists()) {
            Flux::toast(variant: 'danger', text: __('This supplier has certificates, so it cannot be deleted.'));

            return;
        }

        $supplier->delete();
        Flux::toast(variant: 'success', text: __('Supplier deleted.'));
    }

    public function render(): View
    {
        return view('livewire.suppliers.index');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'name', 'contact_name', 'email', 'phone', 'is_approved', 'approved_on');
        $this->resetValidation();
    }
}
