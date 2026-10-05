<?php

namespace Tests\Feature\Incoming;

use App\Livewire\Suppliers\Index;
use App\Models\Certificate;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_needs_permission(): void
    {
        $this->actingAs($this->userWithRole('viewer'))->get(route('suppliers.index'))->assertOk();
        $this->actingAs($this->userWithRole('admin')->syncRoles([]))->get(route('suppliers.index'))->assertForbidden();
    }

    public function test_list_searches_and_filters_by_approval(): void
    {
        Supplier::factory()->create(['name' => 'Seri Mutiara Steel', 'is_approved' => true]);
        Supplier::factory()->create(['name' => 'Jaya Fasteners', 'is_approved' => false]);
        $this->actingAs($this->userWithRole('viewer'));

        Livewire::test(Index::class)
            ->set('search', 'mutiara')
            ->assertSee('Seri Mutiara Steel')
            ->assertDontSee('Jaya Fasteners')
            ->set('search', '')
            ->set('approval', 'not-approved')
            ->assertSee('Jaya Fasteners')
            ->assertDontSee('Seri Mutiara Steel');
    }

    public function test_quality_manager_creates_and_edits_a_supplier(): void
    {
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Index::class)
            ->call('create')
            ->set('code', 'SUP-900')
            ->set('name', 'Lembah Polymers')
            ->set('is_approved', true)
            ->call('save')
            ->assertHasErrors(['approved_on' => 'required_if_accepted'])
            ->set('approved_on', '2026-01-15')
            ->call('save')
            ->assertHasNoErrors();

        $supplier = Supplier::query()->where('code', 'SUP-900')->firstOrFail();
        $this->assertTrue($supplier->is_approved);
        $this->assertSame('2026-01-15', $supplier->approved_on?->format('Y-m-d'));

        Livewire::test(Index::class)
            ->call('edit', $supplier->id)
            ->set('is_approved', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($supplier->refresh()->approved_on, 'Withdrawing approval clears the date.');
    }

    public function test_codes_are_unique(): void
    {
        Supplier::factory()->create(['code' => 'SUP-001']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Index::class)
            ->call('create')
            ->set('code', 'SUP-001')
            ->set('name', 'Another')
            ->call('save')
            ->assertHasErrors(['code' => 'unique']);
    }

    public function test_suppliers_with_certificates_cannot_be_deleted(): void
    {
        $used = Certificate::factory()->create()->supplier;
        $unused = Supplier::factory()->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Index::class)->call('confirmDelete', $used->id)->call('delete');
        Livewire::test(Index::class)->call('confirmDelete', $unused->id)->call('delete');

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
    }

    public function test_inspector_cannot_change_suppliers(): void
    {
        $supplier = Supplier::factory()->create();
        $this->actingAs($this->userWithRole('inspector'));

        Livewire::test(Index::class)->call('create')->assertForbidden();
        Livewire::test(Index::class)->call('edit', $supplier->id)->assertForbidden();
        Livewire::test(Index::class)->call('confirmDelete', $supplier->id)->assertForbidden();
    }
}
