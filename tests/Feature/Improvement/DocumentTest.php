<?php

use App\Livewire\Capas\Show as CapaShow;
use App\Livewire\Documents\Index;
use App\Livewire\Documents\ReadingList;
use App\Livewire\Documents\Show;
use App\Models\Capa;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\User;
use App\Notifications\DocumentReviewDue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->author = $this->userWithRole('quality-manager');
    $this->approver = User::factory()->create()->assignRole('quality-manager');
    $this->actingAs($this->author);
});

/**
 * A document whose revision A the author wrote and sent for review.
 */
function inReview(): Document
{
    $document = Document::factory()->create(['review_interval_months' => 6]);
    $revision = $document->startRevision();
    $revision->attachUpload(UploadedFile::fake()->create('qp.pdf', 50, 'application/pdf'));
    $revision->fill(['change_summary' => 'Initial issue'])->save();
    $revision->submit();

    return $document;
}

it('creates a document with draft revision A', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->set('number', 'QP-075')
        ->set('title', 'Control of documented information')
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('number', 'QP-075')->sole();
    expect($document->revisions()->sole())->revision->toBe('A')->status->toBe('draft');
});

it('needs a file and a summary before review', function () {
    $document = Document::factory()->create();
    $document->startRevision();

    Livewire::test(Show::class, ['document' => $document])
        ->call('submit')
        ->assertHasErrors('status')
        ->set('file', UploadedFile::fake()->create('qp.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->set('change_summary', 'Initial issue')
        ->call('submit')
        ->assertHasNoErrors();

    expect($document->revisions()->sole())->status->toBe('in-review')->file_name->toBe('qp.docx');
});

it('stops authors approving their own revision', function () {
    $document = inReview();

    Livewire::test(Show::class, ['document' => $document])->call('approve')->assertHasErrors('status');

    expect($document->revisions()->sole()->status)->toBe('in-review');
});

it('makes an approved revision effective, supersedes the old one and sets the review date', function () {
    $document = inReview();
    $this->actingAs($this->approver);
    Livewire::test(Show::class, ['document' => $document])->call('approve')->assertHasNoErrors();

    expect($document->refresh()->next_review_on?->toDateString())->toBe(now()->addMonths(6)->toDateString());

    $this->actingAs($this->author);
    $revisionB = $document->startRevision();
    expect($revisionB->revision)->toBe('B');
    $revisionB->attachUpload(UploadedFile::fake()->create('qp-b.pdf', 50, 'application/pdf'));
    $revisionB->fill(['change_summary' => 'Add receiving step'])->save();
    $revisionB->submit();

    $this->actingAs($this->approver);
    Livewire::test(Show::class, ['document' => $document])->call('approve');

    expect($document->revisions()->pluck('status', 'revision')->all())->toBe(['B' => 'effective', 'A' => 'superseded']);
});

it('allows one revision in progress at a time', function () {
    $document = inReview();

    expect(fn () => $document->startRevision())->toThrow(ValidationException::class);
});

it('asks readers to read, lets them open the file and records their acknowledgement', function () {
    $document = inReview();
    $this->actingAs($this->approver);
    Livewire::test(Show::class, ['document' => $document])->call('approve');
    $reader = User::factory()->create();
    $outsider = User::factory()->create();
    $revision = $document->effectiveRevision()->sole();

    Livewire::test(Show::class, ['document' => $document])
        ->call('openReaders')
        ->set('readerIds', [(string) $reader->id])
        ->call('saveReaders');

    $this->actingAs($outsider)->get(route('document-revisions.file', $revision))->assertForbidden();

    $this->actingAs($reader)->get(route('document-revisions.file', $revision))->assertOk();
    Livewire::test(ReadingList::class)->assertSee($document->number)->call('acknowledge', $revision->id);

    expect($revision->readers()->whereKey($reader->id)->first()?->pivot->acknowledged_at)->not->toBeNull();
    Livewire::test(ReadingList::class)->call('acknowledge', $revision->id)->assertNotFound();
});

it('lets the owner confirm a periodic review without a new revision', function () {
    $document = inReview();
    $this->actingAs($this->approver);
    Livewire::test(Show::class, ['document' => $document])->call('approve');
    $document->forceFill(['next_review_on' => now()->subDay()])->save();

    Livewire::test(Show::class, ['document' => $document])->call('confirmReview');

    expect($document->refresh()->next_review_on?->toDateString())->toBe(now()->addMonths(6)->toDateString())
        ->and($document->auditLogs()->where('event', 'reviewed')->exists())->toBeTrue();
});

it('emails owners about documents due for review', function () {
    Notification::fake();
    $due = Document::factory()->create(['owner_id' => $this->author->id]);
    $due->forceFill(['next_review_on' => now()->addDays(3)])->save();
    Document::factory()->create(['owner_id' => $this->approver->id])->forceFill(['next_review_on' => now()->addYear()])->save();

    $this->artisan('qms:document-review-reminders')->assertSuccessful();

    Notification::assertSentTo($this->author, DocumentReviewDue::class, fn (DocumentReviewDue $n) => $n->documents->pluck('id')->all() === [$due->id]);
    Notification::assertNotSentTo($this->approver, DocumentReviewDue::class);
});

it('starts a document revision from a CAPA (D7) and links it back', function () {
    $document = inReview();
    $this->actingAs($this->approver);
    Livewire::test(Show::class, ['document' => $document])->call('approve');
    $capa = Capa::factory()->create();

    Livewire::test(CapaShow::class, ['capa' => $capa])
        ->set('documentToRevise', (string) $document->id)
        ->call('requestDocumentChange')
        ->assertRedirect(route('documents.show', $document));

    expect(DocumentRevision::query()->where('capa_id', $capa->id)->sole()->revision)->toBe('B');
});

it('keeps viewers out of document changes', function () {
    $document = inReview();
    $this->actingAs($this->userWithRole('viewer'));

    $this->get(route('documents.show', $document))->assertOk();
    Livewire::test(Show::class, ['document' => $document])->call('edit')->assertForbidden();
    Livewire::test(Show::class, ['document' => $document])->call('approve')->assertForbidden();
});
