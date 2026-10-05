<?php

use App\Livewire\Audits\Index;
use App\Livewire\Audits\Show;
use App\Models\IsoClause;
use App\Models\Ncr;
use App\Models\QualityAudit;
use Database\Seeders\IsoClauseSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(IsoClauseSeeder::class);
    $this->actingAs($this->userWithRole('auditor'));
});

it('lets an auditor plan an audit with clauses in scope', function () {
    $clause = IsoClause::query()->where('number', '8.4')->sole();

    Livewire::test(Index::class)
        ->call('create')
        ->set('title', 'Supplier control')
        ->set('planned_on', now()->addWeek()->toDateString())
        ->set('clauseIds', [(string) $clause->id])
        ->call('save')
        ->assertHasNoErrors();

    $audit = QualityAudit::query()->sole();
    expect($audit)->number->toBe('AUD-'.now()->year.'-0001')->status->toBe('planned')
        ->and($audit->clauses->pluck('number')->all())->toBe(['8.4']);
});

it('records findings only while in progress; nonconformities raise NCRs, observations do not', function () {
    $audit = QualityAudit::factory()->create();
    $page = Livewire::test(Show::class, ['audit' => $audit]);

    $page->set('description', 'Too early')->call('addFinding')->assertHasErrors('status');

    $page->call('start')
        ->set('type', 'major-nonconformity')
        ->set('description', 'No calibration records for CMM.')
        ->call('addFinding')
        ->set('type', 'observation')
        ->set('description', 'Labels could be clearer.')
        ->call('addFinding')
        ->assertHasNoErrors();

    $ncr = Ncr::query()->sole();
    expect($ncr)->source->toBe('internal-audit')->severity->toBe('major')->status->toBe('draft')
        ->and($ncr->sourceUrl())->toBe(route('audits.show', $audit))
        ->and($audit->findings()->count())->toBe(2)
        ->and($audit->findings()->first()?->auditLogs()->where('event', 'created')->exists())->toBeTrue();
});

it('needs a summary to complete, then locks the audit', function () {
    $audit = QualityAudit::factory()->status('in-progress')->create();

    Livewire::test(Show::class, ['audit' => $audit])
        ->call('complete')
        ->assertHasErrors('status')
        ->set('summary', 'System is effective.')
        ->call('complete')
        ->assertHasNoErrors();

    expect($audit->refresh())->status->toBe('completed')->completed_at->not->toBeNull();
    Livewire::test(Show::class, ['audit' => $audit])->call('saveSummary')->assertForbidden();
});

it('keeps viewers to reading audits', function () {
    $audit = QualityAudit::factory()->create();
    $this->actingAs($this->userWithRole('viewer'));

    $this->get(route('audits.show', $audit))->assertOk();
    Livewire::test(Show::class, ['audit' => $audit])->call('start')->assertForbidden();
    Livewire::test(Index::class)->call('create')->assertForbidden();
});
