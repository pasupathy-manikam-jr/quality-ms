<?php

namespace Tests\Feature\Incoming;

use App\Livewire\Materials\Index;
use App\Livewire\Materials\Show;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_material_opens_its_limits_page(): void
    {
        $this->actingAs($this->userWithRole('quality-manager'));

        $page = Livewire::test(Index::class)
            ->call('create')
            ->set('code', 'S355JR')
            ->set('name', 'Structural steel plate')
            ->set('size_label', 'Thickness (mm)')
            ->call('save')
            ->assertHasNoErrors();

        $material = Material::query()->where('code', 'S355JR')->firstOrFail();
        $page->assertRedirect(route('materials.show', $material));
        $this->get(route('materials.show', $material))->assertOk()->assertSee('Structural steel plate');
    }

    public function test_limits_need_a_min_or_max_and_max_must_not_be_below_min(): void
    {
        $material = Material::factory()->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['material' => $material])
            ->call('create')
            ->set('property', 'UTS')
            ->call('save')
            ->assertHasErrors(['min' => 'required_without', 'max' => 'required_without'])
            ->set('min', '630')
            ->set('max', '470')
            ->call('save')
            ->assertHasErrors('max')
            ->set('min', '470')
            ->set('max', '630')
            ->set('unit', 'MPa')
            ->call('save')
            ->assertHasNoErrors();

        $limit = $material->limits()->firstOrFail();
        $this->assertSame(['470.000000', '630.000000'], [$limit->min, $limit->max]);
    }

    public function test_a_max_only_limit_with_a_negative_value_is_accepted(): void
    {
        $material = Material::factory()->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['material' => $material])
            ->call('create')
            ->set('property', 'Impact temperature')
            ->set('max', '-20')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_size_ranges_for_one_property_may_not_overlap(): void
    {
        $material = Material::factory()->create(['size_label' => 'Thickness (mm)']);
        $material->limits()->create(['property' => 'Yield', 'min' => '355', 'size_to' => '16']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['material' => $material])
            ->call('create')
            ->set('property', 'Yield')
            ->set('min', '345')
            ->set('size_from', '16')
            ->set('size_to', '40')
            ->call('save')
            ->assertHasErrors('size_from')
            ->set('size_from', '16.001')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, $material->limits()->count());
    }

    public function test_an_open_ended_limit_overlaps_every_range(): void
    {
        $material = Material::factory()->create(['size_label' => 'Thickness (mm)']);
        $material->limits()->create(['property' => 'C', 'max' => '0.24']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['material' => $material])
            ->call('create')
            ->set('property', 'C')
            ->set('max', '0.25')
            ->set('size_from', '40.001')
            ->call('save')
            ->assertHasErrors('size_from');
    }

    public function test_limits_are_removed_after_confirmation(): void
    {
        $material = Material::factory()->create();
        $limit = $material->limits()->create(['property' => 'C', 'max' => '0.2']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['material' => $material])
            ->call('confirmDelete', $limit->id)
            ->call('delete');

        $this->assertModelMissing($limit);
    }

    public function test_inspector_can_view_but_not_change_limits(): void
    {
        $material = Material::factory()->create();
        $this->actingAs($this->userWithRole('inspector'));

        $this->get(route('materials.show', $material))->assertOk()->assertDontSee(__('Add limit'));
        Livewire::test(Show::class, ['material' => $material])->call('create')->assertForbidden();
        Livewire::test(Index::class)->call('create')->assertForbidden();
    }
}
