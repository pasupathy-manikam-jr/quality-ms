<?php

use App\Livewire\Ncrs\Index;
use App\Livewire\Ncrs\Show;
use App\Models\Capa;
use App\Models\Certificate;
use App\Models\Gauge;
use App\Models\Inspection;
use App\Models\Ncr;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs($this->userWithRole('quality-manager')));

it('raises a draft NCR when an inspection fails, major if a critical characteristic failed', function () {
    $inspection = Inspection::factory()->create(['quantity' => '25']);
    [$hole, $finish] = $inspection->plan->items->all();
    foreach ([[$hole, 1, '10.20', false], [$hole, 2, '10.00', true], [$finish, 1, null, true]] as [$item, $sample, $value, $passed]) {
        $inspection->readings()->create(['inspection_plan_item_id' => $item->id, 'sample' => $sample, 'value' => $value, 'is_ok' => $value === null ? true : null, 'passed' => $passed]);
    }

    $inspection->complete();

    $ncr = Ncr::query()->sole();
    expect($ncr)
        ->source->toBe('inspection')
        ->status->toBe('draft')
        ->severity->toBe('major')
        ->part_id->toBe($inspection->plan->part_id)
        ->quantity_affected->toBe('25.000')
        ->title->toContain('Hole diameter')
        ->description->toContain('10.2')
        ->and($ncr->sourceable->is($inspection))->toBeTrue();
});

it('raises nothing when an inspection passes', function () {
    $inspection = Inspection::factory()->create();
    foreach ($inspection->plan->items as $item) {
        for ($sample = 1; $sample <= $item->sample_size; $sample++) {
            $inspection->readings()->create(['inspection_plan_item_id' => $item->id, 'sample' => $sample, 'value' => $item->isNumeric() ? '10' : null, 'is_ok' => $item->isNumeric() ? null : true, 'passed' => true]);
        }
    }

    $inspection->complete();

    expect(Ncr::query()->count())->toBe(0);
});

it('raises an NCR against the supplier when a certificate is rejected', function () {
    $certificate = Certificate::factory()->create();

    $certificate->transitionTo('rejected', 'Carbon over limit.');

    expect(Ncr::query()->sole())
        ->source->toBe('certificate')
        ->supplier_id->toBe($certificate->supplier_id)
        ->description->toBe('Carbon over limit.');
});

it('raises an NCR when a calibration fails, major if inspections used the gauge', function () {
    $gauge = Gauge::factory()->create();
    $gauge->recordCalibration(['performed_on' => now()->toDateString(), 'performed_by' => 'Lab', 'result' => 'fail', 'as_found' => 'Reads high']);

    expect(Ncr::query()->sole())->source->toBe('calibration')->severity->toBe('minor');
});

it('walks an NCR from draft to closed with the right checks at each step', function () {
    $ncr = Ncr::factory()->create(['description' => null]);
    $page = Livewire::test(Show::class, ['ncr' => $ncr]);

    $page->call('open')->assertHasErrors('status');
    $ncr->update(['description' => 'Bolt holes undersize.']);
    $page->call('open')->assertHasNoErrors();

    $page->call('approveDisposition')->assertHasErrors('status')
        ->set('disposition', 'use-as-is')
        ->call('saveDisposition')
        ->assertHasErrors(['disposition_notes' => 'required_if'])
        ->set('disposition', 'scrap')
        ->call('saveDisposition')
        ->call('approveDisposition')
        ->assertHasNoErrors();

    $capa = Capa::factory()->create();
    $capa->ncrs()->attach($ncr);
    $page->call('confirmStatus', 'closed')->call('changeStatus')->assertHasErrors('status');

    $capa->forceFill(['status' => 'closed'])->save();
    $page->call('confirmStatus', 'closed')->set('notes', 'All scrapped.')->call('changeStatus')->assertHasNoErrors();

    expect($ncr->refresh())
        ->status->toBe('closed')
        ->disposition->toBe('scrap')
        ->disposition_approved_by->toBe(auth()->id())
        ->closed_at->not->toBeNull();
});

it('needs a reason to cancel a draft', function () {
    $ncr = Ncr::factory()->create();

    Livewire::test(Show::class, ['ncr' => $ncr])
        ->call('confirmStatus', 'cancelled')
        ->call('changeStatus')
        ->assertHasErrors('status')
        ->set('notes', 'Raised twice.')
        ->call('changeStatus')
        ->assertHasNoErrors();

    expect($ncr->refresh()->status)->toBe('cancelled');
});

it('validates a hand-entered NCR by its source', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->set('form.source', 'customer-complaint')
        ->call('save')
        ->assertHasErrors(['form.title' => 'required', 'form.customer' => 'required_if'])
        ->set('form.source', 'supplier')
        ->call('save')
        ->assertHasErrors(['form.supplier_id' => 'required_if'])
        ->set('form.source', 'inspection')
        ->call('save')
        ->assertHasErrors(['form.source' => 'in']);
});

it('starts a CAPA linked to the NCR', function () {
    $ncr = Ncr::factory()->status('open')->create();

    Livewire::test(Show::class, ['ncr' => $ncr])->call('startCapa');

    expect($ncr->capas()->sole())->status->toBe('open')->d2_problem->toBe($ncr->description);
});

it('lets inspectors raise and work NCRs but not approve or close them', function () {
    $ncr = Ncr::factory()->status('open')->create(['disposition' => 'rework']);
    $this->actingAs($this->userWithRole('inspector'));

    Livewire::test(Show::class, ['ncr' => $ncr])->call('approveDisposition')->assertForbidden();
    Livewire::test(Show::class, ['ncr' => $ncr])->call('confirmStatus', 'closed')->assertForbidden();
    Livewire::test(Show::class, ['ncr' => $ncr])->call('startCapa')->assertForbidden();
    Livewire::test(Index::class)->call('create')->assertOk();
});
