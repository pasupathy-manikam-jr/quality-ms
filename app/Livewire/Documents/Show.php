<?php

namespace App\Livewire\Documents;

use App\Livewire\Concerns\SignsRecords;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\IsoClause;
use App\Models\User;
use App\Support\Notify;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * @property-read Document $document
 * @property-read DocumentRevision|null $working the draft or in-review revision, if any
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, IsoClause> $isoClauses
 */
class Show extends Component
{
    use SignsRecords, WithFileUploads;

    #[Locked]
    public int $documentId;

    public string $title = '';

    public string $type = '';

    public string $owner_id = '';

    public string $review_interval_months = '';

    /** @var array<int, string> */
    public array $clauseIds = [];

    public string $change_summary = '';

    public ?TemporaryUploadedFile $file = null;

    /** @var array<int, string> */
    public array $readerIds = [];

    public function mount(Document $document): void
    {
        $this->documentId = $document->id;
        $this->change_summary = (string) $this->working?->change_summary;
    }

    #[Computed]
    public function document(): Document
    {
        return Document::query()
            ->with(['owner', 'clauses', 'revisions.approver', 'revisions.creator', 'revisions.capa', 'revisions.signatures', 'effectiveRevision.readers'])
            ->findOrFail($this->documentId);
    }

    #[Computed]
    public function working(): ?DocumentRevision
    {
        return $this->document->revisions->first(fn (DocumentRevision $r) => in_array($r->status, ['draft', 'in-review'], true));
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, IsoClause>
     */
    #[Computed]
    public function isoClauses(): Collection
    {
        return IsoClause::query()->orderBy('id')->get();
    }

    /**
     * @return array<string, string>
     */
    protected function signedActions(): array
    {
        return ['approve' => 'approved'];
    }

    public function edit(): void
    {
        $this->authorize('edit-documents');

        $document = $this->document;
        $this->title = $document->title;
        $this->type = $document->type;
        $this->owner_id = (string) $document->owner_id;
        $this->review_interval_months = (string) $document->review_interval_months;
        $this->clauseIds = $document->clauses->map(fn (IsoClause $c) => (string) $c->id)->values()->all();
        $this->resetValidation();
        Flux::modal('document-form')->show();
    }

    public function save(): void
    {
        $this->authorize('edit-documents');

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Document::TYPES)],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'review_interval_months' => ['required', 'integer', 'min:1', 'max:60'],
            'clauseIds' => ['array'],
            'clauseIds.*' => ['integer', Rule::exists('iso_clauses', 'id')],
        ], attributes: ['owner_id' => __('owner'), 'clauseIds' => __('ISO clauses')]);

        $this->document->update(Arr::except($validated, 'clauseIds'));
        $this->document->clauses()->sync(array_map('intval', $validated['clauseIds']));

        unset($this->document);
        Flux::modal('document-form')->close();
        Flux::toast(variant: 'success', text: __('Document updated.'));
    }

    public function saveDraft(): void
    {
        $revision = $this->editableWorking();

        $this->validate([
            'change_summary' => ['nullable', 'string', 'max:5000'],
            'file' => DocumentRevision::uploadRules(),
        ]);

        if ($this->file) {
            $revision->attachUpload($this->file);
        }

        $revision->fill(['change_summary' => $this->change_summary ?: null])->save();

        unset($this->document, $this->working);
        $this->reset('file');
        Flux::toast(variant: 'success', text: __('Draft saved.'));
    }

    public function submit(): void
    {
        $this->saveDraft();
        $revision = $this->editableWorking();
        $revision->submit();
        Notify::documentReviewRequested($revision);

        unset($this->document, $this->working);
        Flux::toast(variant: 'success', text: __('Sent for review.'));
    }

    public function returnToDraft(): void
    {
        $this->authorize('approve-documents');

        $this->workingRevision()->returnToDraft();

        unset($this->document, $this->working);
        Flux::toast(variant: 'success', text: __('Returned to draft.'));
    }

    public function approve(): void
    {
        $this->authorize('approve-documents');

        $revision = $this->workingRevision();
        $this->signAs($revision, 'approved', fn () => $revision->approve());

        unset($this->document, $this->working);
        Flux::toast(variant: 'success', text: __('Revision approved and now effective.'));
    }

    public function startRevision(): void
    {
        $this->authorize('edit-documents');

        $this->document->startRevision();

        unset($this->document, $this->working);
        $this->change_summary = '';
        Flux::toast(variant: 'success', text: __('New draft revision started.'));
    }

    public function confirmReview(): void
    {
        $this->authorize('edit-documents');

        $this->document->confirmReview();

        unset($this->document);
        Flux::toast(variant: 'success', text: __('Review recorded. Next review :date.', ['date' => $this->document->next_review_on?->format('Y-m-d')]));
    }

    public function openReaders(): void
    {
        $this->authorize('edit-documents');
        abort_unless($this->document->effectiveRevision !== null, 403);

        $this->readerIds = $this->document->effectiveRevision->readers->map(fn (User $u) => (string) $u->id)->values()->all();
        Flux::modal('readers-form')->show();
    }

    /**
     * Ask people to read the effective revision. Existing acknowledgements are kept.
     */
    public function saveReaders(): void
    {
        $this->authorize('edit-documents');

        $revision = $this->document->effectiveRevision;
        abort_unless($revision !== null, 403);

        $validated = $this->validate([
            'readerIds' => ['array'],
            'readerIds.*' => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]);

        $revision->readers()->sync(array_map('intval', $validated['readerIds']));

        unset($this->document);
        Flux::modal('readers-form')->close();
        Flux::toast(variant: 'success', text: __('Readers updated.'));
    }

    public function render(): View
    {
        return view('livewire.documents.show')->title($this->document->number);
    }

    private function workingRevision(): DocumentRevision
    {
        abort_unless($this->working !== null, 404);

        return $this->working;
    }

    private function editableWorking(): DocumentRevision
    {
        $this->authorize('edit-documents');
        $revision = $this->workingRevision();
        abort_unless($revision->isEditable(), 403, __('Only a draft can be changed.'));

        return $revision;
    }
}
