<?php

use App\Livewire\Gauges\Show as GaugeShow;
use App\Livewire\Inspections\Index;
use App\Livewire\Inspections\Show;
use App\Models\Certificate;
use App\Models\Gauge;
use App\Models\Inspection;
use App\Models\InspectionPlan;
use App\Models\Lot;
use App\Models\Material;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->userWithRole('inspector'));
    $this->inspection = Inspection::factory()->create();
    $this->items = $this->inspection->plan->items;
    $this->gauge = Gauge::factory()->create();
});

/**
 * Fill every reading: two hole diameters with the gauge, and the visual check.
 *
 * @return array<string, string>
 */
function readings(Inspection $inspection, string $first, string $second, string $visual = 'ok'): array
{
    [$hole, $finish] = $inspection->plan->items->all();

    return ["{$hole->id}-1" => $first, "{$hole->id}-2" => $second, "{$finish->id}-1" => $visual];
}

it('numbers inspections and starts them against an approved plan only', function () {
    $draft = InspectionPlan::factory()->create();

    expect($this->inspection->number)->toBe('INS-'.now()->year.'-0001');

    Livewire::test(Index::class)
        ->call('create')
        ->set('plan_id', (string) $draft->id)
        ->call('save')
        ->assertHasErrors(['plan_id' => 'exists']);
});

it('passes when every reading is within specification', function () {
    Livewire::test(Show::class, ['inspection' => $this->inspection])
        ->set('values', readings($this->inspection, '10.01', '9.95'))
        ->set('gauges', [$this->items[0]->id => (string) $this->gauge->id])
        ->call('complete')
        ->assertHasNoErrors();

    expect($this->inspection->refresh())
        ->status->toBe('passed')
        ->completed_at->not->toBeNull()
        ->and($this->inspection->readings()->where('gauge_id', $this->gauge->id)->count())->toBe(2);
});

it('fails when any reading is out of specification, including attribute checks', function (string $first, string $visual) {
    Livewire::test(Show::class, ['inspection' => $this->inspection])
        ->set('values', readings($this->inspection, $first, '10.00', $visual))
        ->set('gauges', [$this->items[0]->id => (string) $this->gauge->id])
        ->call('complete');

    expect($this->inspection->refresh()->status)->toBe('failed');
})->with([
    'diameter just over max' => ['10.051', 'ok'],
    'visual not OK' => ['10.00', 'nok'],
]);

it('refuses to complete with readings missing', function () {
    [$hole] = $this->items->all();

    Livewire::test(Show::class, ['inspection' => $this->inspection])
        ->set('values', ["{$hole->id}-1" => '10.00'])
        ->set('gauges', [$hole->id => (string) $this->gauge->id])
        ->call('complete')
        ->assertHasErrors('status');

    expect($this->inspection->refresh()->status)->toBe('in-progress')
        ->and($this->inspection->readings()->count())->toBe(1, 'Entered readings are still saved.');
});

it('refuses measured values without a gauge, or with a gauge out of calibration', function (?int $dueIn, string $status) {
    $gauge = Gauge::factory()->dueIn($dueIn)->status($status)->create();
    [$hole] = $this->items->all();

    Livewire::test(Show::class, ['inspection' => $this->inspection])
        ->set('values', ["{$hole->id}-1" => '10.00'])
        ->call('save')
        ->assertHasErrors("gauges.{$hole->id}")
        ->set('gauges', [$hole->id => (string) $gauge->id])
        ->call('save')
        ->assertHasErrors("gauges.{$hole->id}");

    expect($this->inspection->readings()->count())->toBe(0);
})->with([
    'overdue' => [-1, 'active'],
    'never calibrated' => [null, 'active'],
    'out of service' => [100, 'out-of-service'],
]);

it('rejects values that are not plain numbers', function () {
    [$hole] = $this->items->all();

    Livewire::test(Show::class, ['inspection' => $this->inspection])
        ->set('values', ["{$hole->id}-1" => '1e3'])
        ->set('gauges', [$hole->id => (string) $this->gauge->id])
        ->call('save')
        ->assertHasErrors("values.{$hole->id}-1");
});

it('locks a completed inspection', function () {
    Livewire::test(Show::class, ['inspection' => $this->inspection])
        ->set('values', readings($this->inspection, '10.00', '10.00'))
        ->set('gauges', [$this->items[0]->id => (string) $this->gauge->id])
        ->call('complete');

    Livewire::test(Show::class, ['inspection' => $this->inspection])->call('save')->assertForbidden();
});

it('only lets a receiving inspection use a verified, unexpired lot of the plan material', function () {
    $material = Material::factory()->create();
    $plan = InspectionPlan::factory()->approvedWithItems()->create(['part_id' => null, 'material_id' => $material->id, 'stage' => 'receiving']);
    $verified = Certificate::factory()->status('verified')->create();
    $good = Lot::factory()->for($verified)->for($material)->create();
    $pending = Lot::factory()->for(Certificate::factory())->for($material)->create();
    $expired = Lot::factory()->for($verified)->for($material)->create(['expires_on' => now()->subDay()]);
    $otherMaterial = Lot::factory()->for($verified)->create();

    $start = fn (?Lot $lot) => Livewire::test(Index::class)
        ->call('create')
        ->set('plan_id', (string) $plan->id)
        ->set('lot_id', (string) $lot?->id)
        ->call('save');

    $start(null)->assertHasErrors('lot_id');
    $start($pending)->assertHasErrors('lot_id');
    $start($expired)->assertHasErrors('lot_id');
    $start($otherMaterial)->assertHasErrors('lot_id');
    $start($good)->assertHasNoErrors();

    expect(Inspection::query()->where('lot_id', $good->id)->exists())->toBeTrue();
});

it('blocks auditors from recording readings', function () {
    $this->actingAs($this->userWithRole('auditor'));

    $this->get(route('inspections.show', $this->inspection))->assertOk()->assertDontSee(__('Complete inspection'));
    Livewire::test(Show::class, ['inspection' => $this->inspection])->call('save')->assertForbidden();
    Livewire::test(Index::class)->call('create')->assertForbidden();
});

it('lists the seeded bracket inspection as suspect after the hardness tester failed', function () {
    $this->seed(DatabaseSeeder::class);
    $tester = Gauge::query()->where('code', 'HRD-002')->firstOrFail();
    $this->actingAs($this->userWithRole('viewer'));

    expect($tester->suspectInspections()->pluck('reference')->all())->toBe(['WO-2026-0871']);

    Livewire::test(GaugeShow::class, ['gauge' => $tester])->assertSee('WO-2026-0871');
});

it('clears the suspect list once the gauge passes again', function () {
    $this->inspection->readings()->create(['inspection_plan_item_id' => $this->items[0]->id, 'sample' => 1, 'gauge_id' => $this->gauge->id, 'value' => '10', 'passed' => true]);
    $this->gauge->recordCalibration(['performed_on' => now()->toDateString(), 'performed_by' => 'Lab', 'result' => 'fail', 'as_found' => 'Off']);

    expect($this->gauge->suspectInspections())->toHaveCount(1);

    $this->gauge->recordCalibration(['performed_on' => now()->toDateString(), 'performed_by' => 'Lab', 'result' => 'pass']);

    expect($this->gauge->suspectInspections())->toBeEmpty();
});
