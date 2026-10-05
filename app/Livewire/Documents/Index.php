<?php

namespace App\Livewire\Documents;

use App\Livewire\Concerns\WithTable;
use App\Models\Document;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read Collection<int, User> $owners
 */
#[Title('Documents')]
class Index extends Component
{
    use WithTable;

    private const SEARCHABLE = ['number', 'title'];

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: false)]
    public bool $due = false;

    public string $number = '';

    public string $title = '';

    public string $docType = 'procedure';

    public string $owner_id = '';

    public string $review_interval_months = '12';

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedDue(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Document>
     */
    #[Computed]
    public function documents(): LengthAwarePaginator
    {
        return $this->paginateTable($this->listQuery(), self::SEARCHABLE, ['number', 'title', 'next_review_on'], 'number');
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function owners(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->authorize('create-documents');

        $this->reset('number', 'title', 'docType', 'review_interval_months');
        $this->owner_id = (string) auth()->id();
        $this->resetValidation();
        Flux::modal('document-form')->show();
    }

    public function save(): void
    {
        $this->authorize('create-documents');

        $validated = $this->validate([
            'number' => ['required', 'string', 'max:50', Rule::unique('documents', 'number')],
            'title' => ['required', 'string', 'max:255'],
            'docType' => ['required', Rule::in(Document::TYPES)],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'review_interval_months' => ['required', 'integer', 'min:1', 'max:60'],
        ], attributes: ['docType' => __('type'), 'owner_id' => __('owner')]);

        $document = Document::query()->create([
            'number' => $validated['number'],
            'title' => $validated['title'],
            'type' => $validated['docType'],
            'owner_id' => (int) $validated['owner_id'],
            'review_interval_months' => (int) $validated['review_interval_months'],
        ]);
        $document->startRevision();

        Flux::toast(variant: 'success', text: __('Document created with draft revision A. Upload its file next.'));
        $this->redirectRoute('documents.show', $document, navigate: true);
    }

    /**
     * The list as on screen (search excluded; WithTable adds it), shared by the table and the export.
     *
     * @return Builder<Document>
     */
    private function listQuery(): Builder
    {
        return Document::query()
            ->with(['owner:id,name', 'effectiveRevision:id,document_id,revision', 'revisions' => fn ($q) => $q->whereIn('status', ['draft', 'in-review'])->select('id', 'document_id', 'revision', 'status')])
            ->when(in_array($this->type, Document::TYPES, true), fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->due, fn (Builder $q) => $q->dueForReview());
    }

    public function export(): StreamedResponse
    {
        return $this->exportCsv($this->listQuery(), self::SEARCHABLE, 'documents', [
            'Number' => fn (Document $d) => $d->number,
            'Title' => fn (Document $d) => $d->title,
            'Type' => fn (Document $d) => __(Str::headline($d->type)),
            'Effective' => fn (Document $d) => $d->effectiveRevision?->revision,
            'Owner' => fn (Document $d) => $d->owner?->name,
            'Next review' => fn (Document $d) => $d->next_review_on?->format('Y-m-d'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.documents.index');
    }
}
