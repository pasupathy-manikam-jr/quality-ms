<?php

use App\Livewire\Dashboard;
use App\Models\Ncr;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

it('shows the seeded demo state to a quality manager', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs($this->userWithRole('quality-manager'));

    $tiles = collect(Livewire::test(Dashboard::class)->get('tiles'))->pluck('value', 'label');

    expect($tiles)
        ->get('Open NCRs')->toBe(3)
        ->get('Gauges overdue')->toBe(2)
        ->get('Gauges due soon')->toBe(1)
        ->get('Certificates to verify')->toBe(3)
        ->get('Inspections in progress')->toBe(1)
        ->get('Documents due for review')->toBe(1);
});

it('ranks NCR sources largest first with a running share', function () {
    $this->actingAs($this->userWithRole('quality-manager'));
    Ncr::factory()->count(3)->create(['source' => 'inspection']);
    Ncr::factory()->create(['source' => 'supplier']);
    Ncr::factory()->status('cancelled')->create(['source' => 'other']);

    expect(Livewire::test(Dashboard::class)->get('pareto'))->toBe([
        ['source' => 'inspection', 'count' => 3, 'share' => 75.0, 'cumulative' => 75.0],
        ['source' => 'supplier', 'count' => 1, 'share' => 25.0, 'cumulative' => 100.0],
    ]);
});

it('marks clauses with neither documents nor records as gaps', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs($this->userWithRole('quality-manager'));

    $matrix = collect(Livewire::test(Dashboard::class)->get('isoMatrix'))->keyBy('number');

    expect($matrix['8.7']['documents'])->toBe(1)
        ->and($matrix['8.7']['records'])->toBeGreaterThan(0)
        ->and($matrix['9.2']['records'])->toBe(1)
        ->and($matrix['6.3']['documents'] + $matrix['6.3']['records'])->toBe(0);
});

it('only shows tiles a person may open', function () {
    $this->actingAs($this->userWithRole('admin')->syncRoles([]));

    expect(collect(Livewire::test(Dashboard::class)->get('tiles'))->pluck('label')->all())->toBe(['Documents for me to read']);
});
