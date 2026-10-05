<?php

namespace App\Livewire\Capas;

use App\Models\Capa;
use App\Models\CapaAction;
use App\Models\Document;
use App\Models\Ncr;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Capa $capa
 * @property-read Collection<int, User> $owners
 * @property-read Collection<int, Ncr> $linkableNcrs
 * @property-read Collection<int, Document> $documents
 */
class Show extends Component
{
    #[Locked]
    public int $capaId;

    public string $title = '';

    public string $type = 'corrective';

    public string $owner_id = '';

    public string $due_on = '';

    /** @var array<string, string> 8D text, keyed by Capa::DISCIPLINES */
    public array $disciplines = [];

    public string $actionDescription = '';

    public string $actionOwnerId = '';

    public string $actionDueOn = '';

    public string $ncrToLink = '';

    public string $documentToRevise = '';

    public string $effectiveness_check_on = '';

    public string $effectiveness_notes = '';

    public function mount(Capa $capa): void
    {
        $this->capaId = $capa->id;
        $this->fillForm($capa);
    }

    #[Computed]
    public function capa(): Capa
    {
        return Capa::query()->with(['owner', 'verifier', 'ncrs', 'actions.owner', 'creator'])->findOrFail($this->capaId);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function owners(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Open NCRs not yet on this CAPA, so a recurring problem can share one CAPA.
     *
     * @return Collection<int, Ncr>
     */
    #[Computed]
    public function linkableNcrs(): Collection
    {
        return Ncr::query()
            ->whereIn('status', ['open', 'disposition-approved'])
            ->whereNotIn('id', $this->capa->ncrs->modelKeys())
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'number', 'title']);
    }

    public function save(): void
    {
        $this->authorizeEdit();

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Capa::TYPES)],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'due_on' => ['required', 'date_format:Y-m-d'],
            'disciplines' => ['array'],
            'disciplines.*' => ['nullable', 'string', 'max:10000'],
        ], attributes: ['owner_id' => __('owner')]);

        $this->capa->update([
            ...Arr::except($validated, 'disciplines'),
            ...collect(Capa::DISCIPLINES)->keys()->mapWithKeys(fn (string $field) => [$field => trim($validated['disciplines'][$field] ?? '') ?: null])->all(),
        ]);

        unset($this->capa);
        Flux::toast(variant: 'success', text: __('CAPA saved.'));
    }

    public function advance(): void
    {
        $this->authorizeEdit();

        if ($this->capa->nextStatus() === 'closed') {
            $this->authorize('verify-capas');
        }

        $this->capa->advance();

        unset($this->capa);
        Flux::toast(variant: 'success', text: __('CAPA moved to :status.', ['status' => __($this->capa->status)]));
    }

    public function addAction(): void
    {
        $this->authorizeEdit();
        abort_unless(in_array($this->capa->status, ['open', 'investigating', 'implementing'], true), 403);

        $validated = $this->validate([
            'actionDescription' => ['required', 'string', 'max:255'],
            'actionOwnerId' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'actionDueOn' => ['nullable', 'date_format:Y-m-d'],
        ], attributes: ['actionDescription' => __('action'), 'actionOwnerId' => __('owner'), 'actionDueOn' => __('due date')]);

        $this->capa->actions()->create([
            'description' => $validated['actionDescription'],
            'owner_id' => $validated['actionOwnerId'] ?: null,
            'due_on' => $validated['actionDueOn'] ?: null,
        ]);

        unset($this->capa);
        $this->reset('actionDescription', 'actionOwnerId', 'actionDueOn');
    }

    public function toggleAction(int $id): void
    {
        $this->authorizeEdit();

        $action = $this->action($id);
        $action->update(['done_at' => $action->done_at ? null : now()]);

        unset($this->capa);
    }

    public function removeAction(int $id): void
    {
        $this->authorizeEdit();
        abort_unless(in_array($this->capa->status, ['open', 'investigating', 'implementing'], true), 403);

        $this->action($id)->delete();

        unset($this->capa);
    }

    public function linkNcr(): void
    {
        $this->authorizeEdit();

        $this->validate(['ncrToLink' => ['required', 'integer', Rule::in($this->linkableNcrs->modelKeys())]], attributes: ['ncrToLink' => __('NCR')]);
        $this->capa->ncrs()->attach((int) $this->ncrToLink);

        unset($this->capa, $this->linkableNcrs);
        $this->reset('ncrToLink');
    }

    /**
     * D7: start a draft revision of a controlled document, linked back to this CAPA.
     */
    public function requestDocumentChange(): void
    {
        $this->authorizeEdit();
        $this->authorize('edit-documents');

        $this->validate(['documentToRevise' => ['required', 'integer', Rule::exists('documents', 'id')]], attributes: ['documentToRevise' => __('document')]);

        $document = Document::query()->findOrFail((int) $this->documentToRevise);
        $revision = $document->startRevision($this->capaId);

        Flux::toast(variant: 'success', text: __('Draft revision :r of :number started.', ['r' => $revision->revision, 'number' => $document->number]));
        $this->redirectRoute('documents.show', $document, navigate: true);
    }

    /**
     * @return Collection<int, Document>
     */
    #[Computed]
    public function documents(): Collection
    {
        return Document::query()->orderBy('number')->get(['id', 'number', 'title']);
    }

    public function verifyEffectiveness(): void
    {
        $this->authorize('verify-capas');

        $this->validate([
            'effectiveness_check_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'effectiveness_notes' => ['required', 'string', 'max:5000'],
        ]);

        $this->capa->verifyEffectiveness($this->effectiveness_check_on, $this->effectiveness_notes);

        unset($this->capa);
        Flux::toast(variant: 'success', text: __('Effectiveness verified. The CAPA can now be closed.'));
    }

    public function render(): View
    {
        return view('livewire.capas.show')->title($this->capa->number);
    }

    private function fillForm(Capa $capa): void
    {
        $this->title = $capa->title;
        $this->type = $capa->type;
        $this->owner_id = (string) $capa->owner_id;
        $this->due_on = (string) $capa->due_on?->format('Y-m-d');
        $this->disciplines = collect(Capa::DISCIPLINES)->keys()->mapWithKeys(fn (string $field) => [$field => (string) $capa->{$field}])->all();
        $this->effectiveness_check_on = (string) ($capa->effectiveness_check_on?->format('Y-m-d') ?? now()->toDateString());
        $this->effectiveness_notes = (string) $capa->effectiveness_notes;
    }

    private function authorizeEdit(): void
    {
        $this->authorize('edit-capas');
        abort_if($this->capa->status === 'closed', 403, __('A closed CAPA cannot be changed.'));
    }

    private function action(int $id): CapaAction
    {
        return CapaAction::query()->where('capa_id', $this->capaId)->findOrFail($id);
    }
}
