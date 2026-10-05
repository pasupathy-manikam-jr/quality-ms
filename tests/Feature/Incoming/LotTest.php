<?php

namespace Tests\Feature\Incoming;

use App\Livewire\Lots\Index;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LotTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lot_number_leads_to_its_certificate_and_supplier(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->userWithRole('viewer'));

        Livewire::test(Index::class)
            ->set('search', 'H24-11873')
            ->assertSee('SMS-MTC-24-08817')
            ->assertSee('Seri Mutiara Steel Sdn Bhd')
            ->assertDontSee('PA-2409-117')
            ->set('search', 'Lembah')
            ->assertSee('PA-2409-117')
            ->assertSee('PA-2409-131')
            ->assertDontSee('H24-11873');
    }

    public function test_filters_by_certificate_status(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->userWithRole('viewer'));

        Livewire::test(Index::class)
            ->set('status', 'verified')
            ->assertSee('H24-11902')
            ->assertDontSee('H24-11873');
    }

    public function test_the_label_follows_configuration(): void
    {
        config(['qms.lot_label' => 'Heat number']);
        $this->actingAs($this->userWithRole('viewer'));

        $this->get(route('lots.index'))->assertOk()->assertSee('Heat number');
    }
}
