<?php

use App\Models\Capa;
use App\Models\Ncr;

it('prints an NCR and an 8D report for people who may see them', function () {
    $ncr = Ncr::factory()->create(['title' => 'Holes undersize']);
    $capa = Capa::factory()->create(['d4_root_cause' => 'Worn drill']);
    $capa->ncrs()->attach($ncr);
    $this->actingAs($this->userWithRole('viewer'));

    $this->get(route('ncrs.print', $ncr))->assertOk()->assertSee('Holes undersize')->assertSee('Uncontrolled when printed');
    $this->get(route('capas.print', $capa))->assertOk()->assertSee('Worn drill')->assertSee($ncr->number);

    $this->actingAs($this->userWithRole('admin')->syncRoles([]));
    $this->get(route('ncrs.print', $ncr))->assertForbidden();
});
