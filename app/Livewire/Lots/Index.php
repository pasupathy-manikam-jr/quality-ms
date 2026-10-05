<?php

namespace App\Livewire\Lots;

use App\Livewire\Concerns\WithTable;
use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Material;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Traceability lookup: find a lot (heat, batch, date code) and the certificate behind it.
 * Inspections, NCRs and CAPAs that used the lot join this page as those modules arrive.
 */
class Index extends Component
{
    use WithTable;

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
        $query = Lot::query()
            ->with(['material:id,code,name,size_label', 'certificate:id,number,status,supplier_id', 'certificate.supplier:id,name'])
            ->when($this->material !== '', fn (Builder $q) => $q->where('material_id', (int) $this->material))
            ->when(in_array($this->status, Certificate::STATUSES, true), fn (Builder $q) => $q->whereRelation('certificate', 'status', $this->status));

        return $this->paginateTable($query, ['lot_number', 'certificate.number', 'certificate.supplier.name'], ['lot_number', 'expires_on', 'created_at']);
    }

    /**
     * @return Collection<int, Material>
     */
    #[Computed]
    public function materials(): Collection
    {
        return Material::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function render(): View
    {
        return view('livewire.lots.index')->title(Lot::label());
    }
}
