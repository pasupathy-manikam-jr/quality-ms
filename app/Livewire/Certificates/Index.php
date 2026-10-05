<?php

namespace App\Livewire\Certificates;

use App\Livewire\Concerns\WithTable;
use App\Livewire\Forms\CertificateForm;
use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Certificates')]
class Index extends Component
{
    use WithFileUploads, WithTable;

    private const SEARCHABLE = ['number', 'po_number', 'supplier.name', 'lots.lot_number'];

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $supplier = '';

    #[Url(except: '')]
    public string $type = '';

    public CertificateForm $form;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSupplier(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Certificate>
     */
    #[Computed]
    public function certificates(): LengthAwarePaginator
    {
        return $this->paginateTable($this->listQuery(), self::SEARCHABLE, ['number', 'issued_on', 'created_at'], 'issued_on');
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
     * @return Collection<int, Supplier>
     */
    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::query()->orderBy('name')->get(['id', 'code', 'name', 'is_approved']);
    }

    public function create(): void
    {
        $this->authorize('create-certificates');

        $this->form->reset();
        $this->resetValidation();
        Flux::modal('certificate-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-certificates');

        $certificate = $this->form->save();

        Flux::toast(variant: 'success', text: __('Certificate added. Now add its :lots.', ['lots' => strtolower(Lot::label())]));
        $this->redirectRoute('certificates.show', $certificate, navigate: true);
    }

    /**
     * The list as on screen (search excluded; WithTable adds it), shared by the table and the export.
     *
     * @return Builder<Certificate>
     */
    private function listQuery(): Builder
    {
        return $this->filtered()
            ->with('supplier:id,name')
            ->withCount('lots')
            ->when(in_array($this->status, Certificate::STATUSES, true), fn (Builder $q) => $q->where('status', $this->status));
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv($this->listQuery(), self::SEARCHABLE, 'certificates', [
            'Number' => fn (Certificate $c) => $c->number,
            'Supplier' => fn (Certificate $c) => $c->supplier->name,
            'Type' => fn (Certificate $c) => $c->typeLabel(),
            'Issued on' => fn (Certificate $c) => $c->issued_on->format('Y-m-d'),
            'Purchase order' => fn (Certificate $c) => $c->po_number,
            'Lots' => fn (Certificate $c) => $c->lots_count,
            'Status' => fn (Certificate $c) => __(Str::headline($c->status)),
        ]);
    }

    public function render(): View
    {
        return view('livewire.certificates.index');
    }

    /**
     * Supplier and type filters; status is applied separately so the tabs can count across it.
     *
     * @return Builder<Certificate>
     */
    private function filtered(): Builder
    {
        return Certificate::query()
            ->when($this->supplier !== '', fn (Builder $q) => $q->where('supplier_id', (int) $this->supplier))
            ->when(array_key_exists($this->type, Certificate::TYPES), fn (Builder $q) => $q->where('type', $this->type));
    }
}
