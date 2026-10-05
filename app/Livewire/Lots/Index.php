<?php

namespace App\Livewire\Lots;

use App\Livewire\Concerns\WithTable;
use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Material;
use App\Support\Decimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Traceability lookup: find a lot (heat, batch, date code) and the certificate behind it.
 * Inspections, NCRs and CAPAs that used the lot join this page as those modules arrive.
 */
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['lot_number', 'certificate.number', 'certificate.supplier.name'];

    #[Url(except: '')]
    public string $material = '';

    #[Url(except: '')]
    public string $status = '';

    public function updatedMaterial(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Lot>
     */
    #[Computed]
    public function lots(): LengthAwarePaginator
    {
        return $this->paginateTable($this->listQuery(), self::SEARCHABLE, ['lot_number', 'expires_on', 'created_at']);
    }

    /**
     * @return Collection<int, Material>
     */
    #[Computed]
    public function materials(): Collection
    {
        return Material::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    /**
     * The list as on screen (search excluded; WithTable adds it), shared by the table and the export.
     *
     * @return Builder<Lot>
     */
    private function listQuery(): Builder
    {
        return Lot::query()
            ->with(['material:id,code,name,size_label', 'certificate:id,number,status,supplier_id', 'certificate.supplier:id,name'])
            ->when($this->material !== '', fn (Builder $q) => $q->where('material_id', (int) $this->material))
            ->when(in_array($this->status, Certificate::STATUSES, true), fn (Builder $q) => $q->whereRelation('certificate', 'status', $this->status));
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv($this->listQuery(), self::SEARCHABLE, 'lots', [
            'Lot' => fn (Lot $l) => $l->lot_number,
            'Material' => fn (Lot $l) => $l->material->code,
            'Size' => fn (Lot $l) => $l->size !== null ? Decimal::format($l->size) : null,
            'Quantity' => fn (Lot $l) => $l->quantity !== null ? Decimal::format($l->quantity) : null,
            'Unit' => fn (Lot $l) => $l->quantity_unit,
            'Certificate' => fn (Lot $l) => $l->certificate->number,
            'Status' => fn (Lot $l) => __(Str::headline($l->certificate->status)),
            'Supplier' => fn (Lot $l) => $l->certificate->supplier->name,
            'Expires' => fn (Lot $l) => $l->expires_on?->format('Y-m-d'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.lots.index')->title(Lot::label());
    }
}
