<?php

use App\Livewire\Capas\Show as CapaShow;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Show as DocumentShow;
use App\Livewire\Ncrs\Show as NcrShow;
use App\Models\Capa;
use App\Models\Document;
use App\Models\Gauge;
use App\Models\Ncr;
use App\Models\User;
use App\Notifications\ActionNeeded;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    $this->author = $this->userWithRole('quality-manager');
    $this->approver = User::factory()->create()->assignRole('quality-manager');
    $this->inspector = User::factory()->create()->assignRole('inspector');
});

it('emails approvers, not the author, when a revision is sent for review, and lists it for them', function () {
    $this->actingAs($this->author);
    $document = Document::factory()->create();
    $document->startRevision();

    Livewire::test(DocumentShow::class, ['document' => $document])
        ->set('file', UploadedFile::fake()->create('qp.pdf', 20, 'application/pdf'))
        ->set('change_summary', 'Initial issue')
        ->call('submit');

    Notification::assertSentTo($this->approver, ActionNeeded::class);
    Notification::assertNotSentTo([$this->author, $this->inspector], ActionNeeded::class);

    expect(collect(Livewire::test(Dashboard::class)->get('waiting'))->pluck('label')->join('|'))->not->toContain($document->number);
    $this->actingAs($this->approver);
    expect(collect(Livewire::test(Dashboard::class)->get('waiting'))->pluck('label')->join('|'))->toContain($document->number);
});

it('emails NCR approvers when a disposition is proposed', function () {
    $ncr = Ncr::factory()->status('open')->create();
    $this->actingAs($this->inspector);

    Livewire::test(NcrShow::class, ['ncr' => $ncr])->set('disposition', 'scrap')->call('saveDisposition');

    Notification::assertSentTo([$this->author, $this->approver], ActionNeeded::class);
    Notification::assertNotSentTo($this->inspector, ActionNeeded::class);
});

it('emails the owner of a new CAPA action, unless they added it themselves', function () {
    $capa = Capa::factory()->create(['owner_id' => $this->author->id]);
    $this->actingAs($this->author);

    Livewire::test(CapaShow::class, ['capa' => $capa])
        ->set('actionDescription', 'Lock tool counter')->set('actionOwnerId', (string) $this->inspector->id)->call('addAction')
        ->set('actionDescription', 'Own task')->set('actionOwnerId', (string) $this->author->id)->call('addAction');

    Notification::assertSentToTimes($this->inspector, ActionNeeded::class, 1);
    Notification::assertNotSentTo($this->author, ActionNeeded::class);
});

it('puts overdue items first in the waiting list', function () {
    Capa::factory()->create(['owner_id' => $this->author->id, 'due_on' => now()->addDays(5), 'title' => 'Later']);
    Capa::factory()->create(['owner_id' => $this->author->id, 'due_on' => now()->subDay(), 'title' => 'Late']);
    Gauge::factory()->dueIn(3)->create(['owner_id' => $this->author->id]);
    $this->actingAs($this->author);

    $waiting = collect(Livewire::test(Dashboard::class)->get('waiting'));

    expect($waiting->first())->detail->toBe('Late')->overdue->toBeTrue()
        ->and($waiting)->toHaveCount(3);
});
