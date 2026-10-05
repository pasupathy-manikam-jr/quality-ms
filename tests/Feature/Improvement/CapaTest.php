<?php

use App\Livewire\Capas\Index;
use App\Livewire\Capas\Show;
use App\Models\Capa;
use App\Models\Ncr;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs($this->userWithRole('quality-manager')));

/**
 * @param  list<string>  $fields
 * @return array<string, string>
 */
function disciplines(array $fields): array
{
    return collect(Capa::DISCIPLINES)->keys()->mapWithKeys(fn ($f) => [$f => in_array($f, $fields, true) ? "Filled {$f}" : ''])->all();
}

it('numbers CAPAs and creates them from the list', function () {
    $page = Livewire::test(Index::class)
        ->call('create')
        ->set('title', 'Undersize holes')
        ->call('save')
        ->assertHasNoErrors();

    $capa = Capa::query()->sole();
    expect($capa->number)->toBe('CAPA-'.now()->year.'-0001');
    $page->assertRedirect(route('capas.show', $capa));
});

it('only advances when each stage has its disciplines, actions and effectiveness check', function () {
    $capa = Capa::factory()->create();
    $page = Livewire::test(Show::class, ['capa' => $capa]);

    $page->set('signaturePassword', 'password')->call('advance')->assertHasErrors('status');

    $page->set('disciplines', disciplines(['d1_team', 'd2_problem']))->call('save')->set('signaturePassword', 'password')->call('advance')->assertHasNoErrors();
    expect($capa->refresh()->status)->toBe('investigating');

    $page->set('disciplines', disciplines(['d1_team', 'd2_problem', 'd3_containment', 'd4_root_cause', 'd5_actions']))
        ->call('save')->set('signaturePassword', 'password')->call('advance')
        ->set('actionDescription', 'Lock tool counter')
        ->call('addAction');
    expect($capa->refresh()->status)->toBe('implementing');

    $page->set('disciplines', disciplines(['d1_team', 'd2_problem', 'd3_containment', 'd4_root_cause', 'd5_actions', 'd6_implementation']))
        ->call('save')->set('signaturePassword', 'password')->call('advance')->assertHasErrors('status');

    $page->call('toggleAction', $capa->actions()->sole()->id)->set('signaturePassword', 'password')->call('advance')->assertHasNoErrors();
    expect($capa->refresh()->status)->toBe('verifying');

    $page->set('disciplines', disciplines(array_keys(Capa::DISCIPLINES)))->call('save')->set('signaturePassword', 'password')->call('advance')->assertHasErrors('status');

    $page->set('effectiveness_notes', 'No recurrence in 3 batches.')->set('signaturePassword', 'password')->call('verifyEffectiveness')->set('signaturePassword', 'password')->call('advance')->assertHasNoErrors();

    expect($capa->refresh())->status->toBe('closed')->closed_at->not->toBeNull();
    Livewire::test(Show::class, ['capa' => $capa])->call('save')->assertForbidden();
});

it('only lets a verifier close or verify effectiveness', function () {
    $capa = Capa::factory()->status('verifying')->create(collect(Capa::DISCIPLINES)->keys()->mapWithKeys(fn ($f) => [$f => 'x'])->all());
    $this->actingAs($this->userWithRole('inspector'));

    Livewire::test(Show::class, ['capa' => $capa])->set('signaturePassword', 'password')->call('verifyEffectiveness')->assertForbidden();
    Livewire::test(Show::class, ['capa' => $capa])->set('signaturePassword', 'password')->call('advance')->assertForbidden();
});

it('links further open NCRs for a recurring problem', function () {
    $capa = Capa::factory()->create();
    $open = Ncr::factory()->status('open')->create();
    $closed = Ncr::factory()->status('closed')->create();

    Livewire::test(Show::class, ['capa' => $capa])
        ->set('ncrToLink', (string) $closed->id)
        ->call('linkNcr')
        ->assertHasErrors('ncrToLink')
        ->set('ncrToLink', (string) $open->id)
        ->call('linkNcr')
        ->assertHasNoErrors();

    expect($capa->ncrs()->pluck('ncrs.id')->all())->toBe([$open->id]);
});

it('filters overdue CAPAs', function () {
    Capa::factory()->create(['title' => 'Late one', 'due_on' => now()->subDay()]);
    Capa::factory()->create(['title' => 'On time', 'due_on' => now()->addDay()]);
    Capa::factory()->status('closed')->create(['title' => 'Closed late', 'due_on' => now()->subDay()]);

    Livewire::test(Index::class)
        ->set('overdue', true)
        ->assertSee('Late one')
        ->assertDontSee('On time')
        ->assertDontSee('Closed late');
});
