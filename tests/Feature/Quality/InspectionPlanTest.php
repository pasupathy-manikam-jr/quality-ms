<?php

use App\Livewire\InspectionPlans\Index;
use App\Livewire\InspectionPlans\Show;
use App\Models\InspectionPlan;
use App\Models\Part;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs($this->userWithRole('quality-manager')));

it('creates a plan for a part and refuses a second plan for the same part and stage', function () {
    $part = Part::factory()->create();

    Livewire::test(Index::class)
        ->call('create')
        ->set('part_id', (string) $part->id)
        ->set('planStage', 'final')
        ->set('title', 'Final check')
        ->call('save')
        ->assertHasNoErrors();

    expect(InspectionPlan::query()->where('part_id', $part->id)->firstOrFail())
        ->status->toBe('draft')
        ->revision->toBe(1);

    Livewire::test(Index::class)
        ->call('create')
        ->set('part_id', (string) $part->id)
        ->set('planStage', 'final')
        ->set('title', 'Duplicate')
        ->call('save')
        ->assertHasErrors('planStage');
});

it('validates numeric characteristics: limits, order and nominal inside them', function () {
    $plan = InspectionPlan::factory()->create();

    Livewire::test(Show::class, ['plan' => $plan])
        ->call('create')
        ->set('characteristic', 'Hole diameter')
        ->call('save')
        ->assertHasErrors(['min' => 'required_without'])
        ->set('min', '10.05')
        ->set('max', '9.95')
        ->call('save')
        ->assertHasErrors('max')
        ->set('min', '9.95')
        ->set('max', '10.05')
        ->set('nominal', '11')
        ->call('save')
        ->assertHasErrors('nominal')
        ->set('nominal', '10')
        ->call('save')
        ->assertHasNoErrors();

    expect($plan->items()->firstOrFail()->rangeLabel())->toBe('9.95 – 10.05');
});

it('needs a characteristic to approve, then locks and obsoletes the previous revision', function () {
    $plan = InspectionPlan::factory()->create();

    Livewire::test(Show::class, ['plan' => $plan])->set('signaturePassword', 'password')->call('approve')->assertHasErrors('status');

    $plan->items()->create(['position' => 1, 'characteristic' => 'Finish', 'kind' => 'attribute']);
    Livewire::test(Show::class, ['plan' => $plan])->set('signaturePassword', 'password')->call('approve')->assertHasNoErrors();

    expect($plan->refresh()->status)->toBe('approved');
    Livewire::test(Show::class, ['plan' => $plan])->call('create')->assertForbidden();

    $draft = $plan->newRevision();
    expect($draft)->revision->toBe(2)->status->toBe('draft')
        ->and($draft->items()->count())->toBe(1);

    Livewire::test(Show::class, ['plan' => $draft])->set('signaturePassword', 'password')->call('approve');

    expect($plan->refresh()->status)->toBe('obsolete')
        ->and($draft->refresh()->status)->toBe('approved');
});

it('allows only one draft revision at a time', function () {
    $plan = InspectionPlan::factory()->approvedWithItems()->create();
    $plan->newRevision();

    Livewire::test(Show::class, ['plan' => $plan])->call('newRevision')->assertHasErrors('status');
});

it('lets inspectors read plans but not change or approve them', function () {
    $plan = InspectionPlan::factory()->create();
    $this->actingAs($this->userWithRole('inspector'));

    $this->get(route('inspection-plans.show', $plan))->assertOk();
    Livewire::test(Show::class, ['plan' => $plan])->call('create')->assertForbidden();
    Livewire::test(Show::class, ['plan' => $plan])->set('signaturePassword', 'password')->call('approve')->assertForbidden();
    Livewire::test(Index::class)->call('create')->assertForbidden();
});
