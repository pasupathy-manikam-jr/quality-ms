<?php

namespace Tests\Feature\Incoming;

use App\Livewire\Certificates\Index;
use App\Livewire\Certificates\Show;
use App\Models\Certificate;
use App\Models\Lot;
use App\Models\Material;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_out_of_limit_steel_and_resin_certificates_are_blocked(): void
    {
        $this->seed(DatabaseSeeder::class);

        $steel = $this->loaded(Certificate::query()->where('number', 'SMS-MTC-24-08817')->firstOrFail());
        $resin = $this->loaded(Certificate::query()->where('number', 'LP-COA-2409-117')->firstOrFail());
        $good = $this->loaded(Certificate::query()->where('number', 'LP-COA-2409-131')->firstOrFail());

        $this->assertSame(['H24-11873: C 0.26 is outside 0.24 max.'], $steel->issues());
        $this->assertSame(['PA-2409-117: Moisture 0.35 is outside 0.2 max.'], $resin->issues());
        $this->assertSame([], $good->issues());
    }

    public function test_size_dependent_limits_pick_the_range_for_the_lot_size(): void
    {
        $material = Material::factory()->create(['size_label' => 'Thickness (mm)']);
        $material->limits()->createMany([
            ['property' => 'Yield', 'min' => '355', 'size_to' => '16'],
            ['property' => 'Yield', 'min' => '345', 'size_from' => '16.001', 'size_to' => '40'],
        ]);

        $thin = $this->lotWith($material, '12', ['Yield' => '350']);
        $thick = $this->lotWith($material, '20', ['Yield' => '350']);
        $huge = $this->lotWith($material, '60', ['Yield' => '350']);

        $this->assertSame('fail', $thin->checks()[0]['outcome']);
        $this->assertSame('pass', $thick->checks()[0]['outcome']);
        $this->assertSame('no-limit', $huge->checks()[0]['outcome']);
    }

    public function test_missing_results_unapproved_suppliers_and_3_2_without_inspector_block_verification(): void
    {
        $material = Material::factory()->create();
        $material->limits()->createMany([['property' => 'C', 'max' => '0.2'], ['property' => 'Mn', 'max' => '1.6']]);
        $lot = $this->lotWith($material, null, ['C' => '0.1']);
        $certificate = $lot->certificate;
        $certificate->supplier->update(['is_approved' => false]);
        $certificate->update(['type' => 'en10204-3.2']);

        $issues = $this->loaded($certificate)->issues();

        $this->assertContains("{$lot->lot_number}: no result for Mn.", $issues);
        $this->assertContains("{$certificate->supplier->name} is not an approved supplier.", $issues);
        $this->assertContains('An EN 10204 3.2 certificate needs the third-party inspector\'s name.', $issues);
    }

    public function test_declaration_only_certificates_need_lots_but_no_results(): void
    {
        $certificate = Certificate::factory()->create(['type' => 'coc']);
        $this->assertSame(['Add at least one Lot.'], $this->loaded($certificate)->issues());

        $material = Material::factory()->create();
        $material->limits()->create(['property' => 'C', 'max' => '0.2']);
        Lot::factory()->for($certificate)->for($material)->create();

        $this->assertSame([], $this->loaded($certificate)->issues());
    }

    public function test_quality_manager_verifies_a_passing_certificate_and_it_locks(): void
    {
        $lot = $this->passingLot();
        $manager = $this->userWithRole('quality-manager');
        $this->actingAs($manager);

        Livewire::test(Show::class, ['certificate' => $lot->certificate])
            ->call('verify')
            ->assertHasNoErrors();

        $certificate = $lot->certificate->refresh();
        $this->assertSame('verified', $certificate->status);
        $this->assertSame($manager->id, $certificate->decided_by);
        $this->assertTrue($certificate->auditLogs()->where('event', 'updated')->where('new_values->status', 'verified')->exists());

        Livewire::test(Show::class, ['certificate' => $certificate])->call('addLot')->assertForbidden();
        Livewire::test(Show::class, ['certificate' => $certificate])->call('enterResults', $lot->id)->assertForbidden();
    }

    public function test_a_failing_certificate_cannot_be_verified_even_by_calling_the_action(): void
    {
        $material = Material::factory()->create();
        $material->limits()->create(['property' => 'C', 'max' => '0.2']);
        $lot = $this->lotWith($material, null, ['C' => '0.3']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['certificate' => $lot->certificate])
            ->call('verify')
            ->assertHasErrors('status');

        $this->assertSame('received', $lot->certificate->refresh()->status);
    }

    public function test_rejecting_needs_a_reason_and_records_it(): void
    {
        $certificate = Certificate::factory()->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['certificate' => $certificate])
            ->call('openReject')
            ->call('reject')
            ->assertHasErrors(['reason' => 'required'])
            ->set('reason', 'Carbon over limit; supplier to resend.')
            ->call('reject')
            ->assertHasNoErrors();

        $certificate->refresh();
        $this->assertSame('rejected', $certificate->status);
        $this->assertSame('Carbon over limit; supplier to resend.', $certificate->rejection_reason);

        $this->expectException(ValidationException::class);
        $certificate->transitionTo('verified');
    }

    public function test_inspector_can_record_lots_and_results_but_not_decide(): void
    {
        $certificate = Certificate::factory()->create();
        $material = Material::factory()->create();
        $material->limits()->createMany([['property' => 'C', 'max' => '0.2'], ['property' => 'Mn', 'max' => '1.6']]);
        $this->actingAs($this->userWithRole('inspector'));

        $page = Livewire::test(Show::class, ['certificate' => $certificate])
            ->call('addLot')
            ->set('material_id', (string) $material->id)
            ->set('lot_number', 'H26-00001')
            ->set('quantity', '12')
            ->call('saveLot')
            ->assertHasNoErrors();

        $lot = $certificate->lots()->firstOrFail();

        $page->call('enterResults', $lot->id)
            ->assertSet('resultValues', ['', ''])
            ->set('resultValues', ['0.18', 'abc'])
            ->call('saveResults')
            ->assertHasErrors('resultValues.1')
            ->set('resultValues', ['0.18', '1.41'])
            ->call('saveResults')
            ->assertHasNoErrors();

        $this->assertSame(['C' => '0.180000', 'Mn' => '1.410000'], $lot->results()->pluck('value', 'property')->all());

        $page->call('verify')->assertForbidden();
    }

    public function test_viewer_can_look_but_not_change(): void
    {
        $certificate = Certificate::factory()->create();
        $this->actingAs($this->userWithRole('viewer'));

        $this->get(route('certificates.show', $certificate))->assertOk()->assertDontSee(__('Verify'));
        Livewire::test(Show::class, ['certificate' => $certificate])->call('edit')->assertForbidden();
        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_upload_is_stored_privately_hashed_and_downloadable_only_with_permission(): void
    {
        Storage::fake('local');
        $supplier = Supplier::factory()->create();
        $this->actingAs($this->userWithRole('inspector'));

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.supplier_id', (string) $supplier->id)
            ->set('form.number', 'MTC-1')
            ->set('form.type', 'en10204-3.1')
            ->set('form.issued_on', '2026-09-01')
            ->set('form.file', UploadedFile::fake()->create('mtc.pdf', 200, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $certificate = Certificate::query()->where('number', 'MTC-1')->firstOrFail();
        Storage::disk('local')->assertExists((string) $certificate->file_path);
        $this->assertStringStartsWith('certificates/', (string) $certificate->file_path);
        $this->assertSame(64, strlen((string) $certificate->file_sha256));

        $this->get(route('certificates.file', $certificate))->assertOk()->assertDownload('mtc.pdf');

        $this->actingAs($this->userWithRole('admin')->syncRoles([]));
        $this->get(route('certificates.file', $certificate))->assertForbidden();
    }

    public function test_create_validates_number_per_supplier_type_and_3_2_inspector(): void
    {
        $existing = Certificate::factory()->create(['number' => 'DUP-1']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Index::class)
            ->call('create')
            ->set('form.supplier_id', (string) $existing->supplier_id)
            ->set('form.number', 'DUP-1')
            ->set('form.type', 'en10204-3.2')
            ->set('form.issued_on', now()->addDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['form.number' => 'unique', 'form.third_party_inspector' => 'required_if', 'form.issued_on' => 'before_or_equal']);
    }

    public function test_list_counts_statuses_and_finds_certificates_by_lot_number(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->userWithRole('viewer'));

        Livewire::test(Index::class)
            ->assertSet('statusCounts', ['received' => 3, 'verified' => 1])
            ->set('search', 'PA-2409-117')
            ->assertSee('LP-COA-2409-117')
            ->assertDontSee('SMS-MTC-24-08817')
            ->set('search', '')
            ->set('status', 'verified')
            ->assertSee('SMS-MTC-24-08902')
            ->assertDontSee('LP-COA-2409-131');
    }

    public function test_only_undecided_certificates_can_be_deleted(): void
    {
        Storage::fake('local');
        $certificate = Certificate::factory()->status('verified')->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['certificate' => $certificate])->call('delete')->assertForbidden();
        $this->assertModelExists($certificate);
    }

    private function passingLot(): Lot
    {
        $material = Material::factory()->create();
        $material->limits()->create(['property' => 'C', 'max' => '0.2']);

        return $this->lotWith($material, null, ['C' => '0.15']);
    }

    /**
     * @param  array<string, string>  $results
     */
    private function lotWith(Material $material, ?string $size, array $results): Lot
    {
        $lot = Lot::factory()->for($material)->create(['size' => $size]);

        foreach ($results as $property => $value) {
            $lot->results()->create(['property' => $property, 'value' => $value]);
        }

        return $lot->load(['material.limits', 'results', 'certificate']);
    }

    private function loaded(Certificate $certificate): Certificate
    {
        return $certificate->load(['supplier', 'lots.material.limits', 'lots.results']);
    }
}
