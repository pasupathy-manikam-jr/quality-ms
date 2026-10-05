<?php

use App\Livewire\Certificates\Index as Certificates;
use App\Livewire\Ncrs\Index as Ncrs;
use App\Livewire\Ncrs\Show as NcrShow;
use App\Models\Certificate;
use App\Models\Ncr;
use Livewire\Livewire;

it('exports the list as filtered on screen, as CSV', function () {
    Certificate::factory()->create(['number' => 'KEEP-1']);
    Certificate::factory()->status('verified')->create(['number' => 'DROP-1']);
    $this->actingAs($this->userWithRole('viewer'));

    $csv = Livewire::test(Certificates::class)->set('status', 'received')->call('export')->assertFileDownloaded()->effects['download']['content'];
    $csv = base64_decode($csv);

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->toContain('Number,Supplier,Type')
        ->toContain('KEEP-1')
        ->not->toContain('DROP-1');
});

it('neutralises spreadsheet formulas in exported text', function () {
    Ncr::factory()->create(['title' => '=HYPERLINK("http://evil")']);
    $this->actingAs($this->userWithRole('viewer'));

    $csv = base64_decode(Livewire::test(Ncrs::class)->call('export')->effects['download']['content']);

    expect($csv)->toContain("'=HYPERLINK");
});

it('shows who changed what on the record page', function () {
    $manager = $this->userWithRole('quality-manager');
    $this->actingAs($manager);
    $ncr = Ncr::factory()->create(['title' => 'Old title']);
    $ncr->update(['title' => 'New title']);

    Livewire::test(NcrShow::class, ['ncr' => $ncr])
        ->assertSee(__('History'))
        ->assertSee($manager->name)
        ->assertSeeInOrder(['Old title', 'New title']);
});
