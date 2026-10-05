<?php

namespace App\Livewire\Ncrs;

use App\Livewire\Concerns\WithTable;
use App\Livewire\Forms\NcrForm;
use App\Models\Ncr;
use App\Models\Part;
use App\Models\Supplier;
use App\Support\Decimal;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read Collection<int, Part> $parts
 * @property-read Collection<int, Supplier> $suppliers
 */
#[Title('Non-conformances')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['number', 'title', 'customer', 'supplier.name', 'part.part_number'];

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $source = '';

    #[Url(except: '')]
    public string $severity = '';

    public NcrForm $form;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSource(): void
    {
        $this->resetPage();
    }

    public function updatedSeverity(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Ncr>
     */
    #[Computed]
    public function ncrs(): LengthAwarePaginator
    {
        return $this->paginateTable($this->listQuery(), self::SEARCHABLE, ['number', 'created_at'], 'created_at');
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
        return Part::query()->orderBy('part_number')->get(['id', 'part_number', 'revision']);
    }

    /**
     * @return Collection<int, Supplier>
     */
    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::query()->orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->authorize('create-ncrs');

        $this->form->reset();
        $this->resetValidation();
        Flux::modal('ncr-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-ncrs');

        $ncr = $this->form->save();

        Flux::toast(variant: 'success', text: __('NCR :number created as a draft.', ['number' => $ncr->number]));
        $this->redirectRoute('ncrs.show', $ncr, navigate: true);
    }

    /**
     * The list as on screen (search excluded; WithTable adds it), shared by the table and the export.
     *
     * @return Builder<Ncr>
     */
    private function listQuery(): Builder
    {
        return $this->filtered()
            ->with(['part:id,part_number,revision', 'supplier:id,name'])
            ->when(in_array($this->status, Ncr::STATUSES, true), fn (Builder $q) => $q->where('status', $this->status));
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv($this->listQuery(), self::SEARCHABLE, 'ncrs', [
            'Number' => fn (Ncr $n) => $n->number,
            'Title' => fn (Ncr $n) => $n->title,
            'Source' => fn (Ncr $n) => __(Str::headline($n->source)),
            'Severity' => fn (Ncr $n) => __(Str::headline($n->severity)),
            'Part' => fn (Ncr $n) => $n->part?->label(),
            'Supplier' => fn (Ncr $n) => $n->supplier?->name,
            'Customer' => fn (Ncr $n) => $n->customer,
            'Quantity affected' => fn (Ncr $n) => $n->quantity_affected !== null ? Decimal::format($n->quantity_affected) : null,
            'Disposition' => fn (Ncr $n) => $n->disposition ? __(Str::headline($n->disposition)) : null,
            'Status' => fn (Ncr $n) => __(Str::headline($n->status)),
            'Raised' => fn (Ncr $n) => $n->created_at?->format('Y-m-d'),
            'Closed' => fn (Ncr $n) => $n->closed_at?->format('Y-m-d'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.ncrs.index');
    }

    /**
     * @return Builder<Ncr>
     */
    private function filtered(): Builder
    {
        return Ncr::query()
            ->when(in_array($this->source, Ncr::SOURCES, true), fn (Builder $q) => $q->where('source', $this->source))
            ->when(in_array($this->severity, Ncr::SEVERITIES, true), fn (Builder $q) => $q->where('severity', $this->severity));
    }
}
