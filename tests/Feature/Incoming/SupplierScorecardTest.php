<?php

use App\Livewire\Suppliers\Index;
use App\Models\Certificate;
use App\Models\Ncr;
use App\Models\Supplier;
use Livewire\Livewire;

it('shows each supplier\'s acceptance rate and NCRs from the last 12 months', function () {
    $supplier = Supplier::factory()->create(['name' => 'Seri Mutiara Steel']);
    Certificate::factory()->count(3)->status('verified')->for($supplier)->create();
    Certificate::factory()->status('rejected')->for($supplier)->create();
    Certificate::factory()->for($supplier)->create();
    Ncr::factory()->create(['supplier_id' => $supplier->id]);
    Ncr::factory()->create(['supplier_id' => $supplier->id, 'created_at' => now()->subYears(2)]);
    Ncr::factory()->status('cancelled')->create(['supplier_id' => $supplier->id]);
    $this->actingAs($this->userWithRole('viewer'));

    $row = Livewire::test(Index::class)->get('suppliers')->first();

    expect($row->acceptanceRate())->toBe(75.0)
        ->and($row->recent_ncrs_count)->toBe(1);
});

it('has no rate before any certificate is decided', function () {
    $supplier = Supplier::factory()->create();
    Certificate::factory()->for($supplier)->create();
    $this->actingAs($this->userWithRole('viewer'));

    expect(Livewire::test(Index::class)->get('suppliers')->first()->acceptanceRate())->toBeNull();
});
