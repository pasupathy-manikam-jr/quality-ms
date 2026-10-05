<?php

namespace App\Livewire\Ncrs;

use App\Livewire\Concerns\SignsRecords;
use App\Livewire\Forms\NcrForm;
use App\Models\Capa;
use App\Models\Ncr;
use App\Models\Part;
use App\Models\Supplier;
use App\Support\Notify;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Ncr $ncr
 * @property-read Collection<int, Part> $parts
 * @property-read Collection<int, Supplier> $suppliers
 */
class Show extends Component
{
    use SignsRecords;

    #[Locked]
    public int $ncrId;

    public NcrForm $form;

    public string $disposition = '';

    public string $disposition_notes = '';

    #[Locked]
    public string $pendingStatus = '';

    public string $notes = '';

    public function mount(Ncr $ncr): void
    {
        $this->ncrId = $ncr->id;
        $this->disposition = (string) $ncr->disposition;
        $this->disposition_notes = (string) $ncr->disposition_notes;
    }

    #[Computed]
    public function ncr(): Ncr
    {
        return Ncr::query()
            ->with(['sourceable', 'part', 'lot', 'supplier', 'creator', 'dispositionApprover', 'capas.owner', 'signatures'])
            ->findOrFail($this->ncrId);
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

    public function edit(): void
    {
        $this->authorizeEdit();

        $this->form->load($this->ncr);
        $this->resetValidation();
        Flux::modal('ncr-form')->show();
    }

    public function save(): void
    {
        $this->authorizeEdit();

        $this->form->id = $this->ncrId;
        $this->form->save();

        unset($this->ncr);
        Flux::modal('ncr-form')->close();
        Flux::toast(variant: 'success', text: __('NCR updated.'));
    }

    public function open(): void
    {
        $this->authorize('edit-ncrs');

        $this->ncr->transitionTo('open');

        unset($this->ncr);
        Flux::toast(variant: 'success', text: __('NCR opened.'));
    }

    public function saveDisposition(): void
    {
        $this->authorize('edit-ncrs');
        abort_unless($this->ncr->status === 'open', 403);

        $validated = $this->validate([
            'disposition' => ['required', Rule::in(Ncr::DISPOSITIONS)],
            'disposition_notes' => ['nullable', 'required_if:disposition,use-as-is', 'string', 'max:5000'],
        ], ['disposition_notes.required_if' => __('Explain why the material can be used as it is.')]);

        $this->ncr->update([...$validated, 'disposition_notes' => $validated['disposition_notes'] ?: null]);
        Notify::ncrDispositionProposed($this->ncr, auth()->user()?->id);

        unset($this->ncr);
        Flux::toast(variant: 'success', text: __('Disposition saved. It now needs approval.'));
    }

    /**
     * @return array<string, string>
     */
    protected function signedActions(): array
    {
        return ['approveDisposition' => 'disposition-approved'];
    }

    public function approveDisposition(): void
    {
        $this->authorize('approve-ncrs');

        $this->signAs($this->ncr, 'disposition-approved', fn () => $this->ncr->transitionTo('disposition-approved'));

        unset($this->ncr);
        Flux::toast(variant: 'success', text: __('Disposition approved.'));
    }

    public function confirmStatus(string $status): void
    {
        $this->authorize($status === 'closed' ? 'approve-ncrs' : 'edit-ncrs');
        abort_unless(in_array($status, ['closed', 'cancelled'], true) && in_array($status, Ncr::TRANSITIONS[$this->ncr->status] ?? [], true), 403);

        $this->pendingStatus = $status;
        $this->reset('notes');
        $this->resetValidation();
        Flux::modal('confirm-ncr-status')->show();
    }

    public function changeStatus(): void
    {
        $this->authorize($this->pendingStatus === 'closed' ? 'approve-ncrs' : 'edit-ncrs');

        $this->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $this->signAs($this->ncr, $this->pendingStatus, fn () => $this->ncr->transitionTo($this->pendingStatus, $this->notes ?: null));

        unset($this->ncr);
        Flux::modal('confirm-ncr-status')->close();
        Flux::toast(variant: 'success', text: $this->pendingStatus === 'closed' ? __('NCR closed.') : __('NCR cancelled.'));
        $this->pendingStatus = '';
    }

    public function startCapa(): void
    {
        $this->authorize('create-capas');
        abort_unless(in_array($this->ncr->status, ['open', 'disposition-approved'], true), 403);

        $capa = Capa::query()->create([
            'title' => $this->ncr->title,
            'owner_id' => auth()->id(),
            'due_on' => now()->addDays(30)->toDateString(),
            'd2_problem' => $this->ncr->description,
        ]);
        $capa->ncrs()->attach($this->ncrId);

        Flux::toast(variant: 'success', text: __('CAPA :number started.', ['number' => $capa->number]));
        $this->redirectRoute('capas.show', $capa, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.ncrs.show')->title($this->ncr->number);
    }

    private function authorizeEdit(): void
    {
        $this->authorize('edit-ncrs');
        abort_unless($this->ncr->isEditable(), 403, __('This NCR can no longer be changed.'));
    }
}
