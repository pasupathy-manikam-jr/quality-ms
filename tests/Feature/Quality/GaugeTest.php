<?php

namespace Tests\Feature\Quality;

use App\Livewire\Gauges\Index;
use App\Livewire\Gauges\Show;
use App\Models\Gauge;
use App\Models\User;
use App\Notifications\CalibrationDue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class GaugeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 09:00');
    }

    public function test_state_comes_from_the_due_date_and_lifecycle(): void
    {
        config(['qms.due_soon_days' => 14]);

        $this->assertSame('calibrated', Gauge::factory()->dueIn(15)->create()->refresh()->state());
        $this->assertSame('due', Gauge::factory()->dueIn(14)->create()->refresh()->state());
        $this->assertSame('due', Gauge::factory()->dueIn(0)->create()->refresh()->state(), 'Usable on its due date.');
        $this->assertSame('overdue', Gauge::factory()->dueIn(-1)->create()->refresh()->state());
        $this->assertSame('overdue', Gauge::factory()->dueIn(null)->create()->refresh()->state(), 'Never calibrated.');
        $this->assertSame('out-of-service', Gauge::factory()->status('out-of-service')->create()->refresh()->state());

        $this->assertTrue(Gauge::factory()->dueIn(0)->create()->refresh()->isUsable());
        $this->assertFalse(Gauge::factory()->dueIn(-1)->create()->refresh()->isUsable());
    }

    public function test_seeded_list_counts_each_state_and_filters_by_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->userWithRole('viewer'));

        Livewire::test(Index::class)
            ->assertSet('stateCounts', ['calibrated' => 1, 'due' => 1, 'overdue' => 2, 'out-of-service' => 1, 'retired' => 0])
            ->set('state', 'overdue')
            ->assertSee('HGT-003')
            ->assertSee('TPG-M8')
            ->assertDontSee('CAL-001');
    }

    public function test_a_pass_sets_the_next_due_date_and_returns_the_gauge_to_service(): void
    {
        Storage::fake('local');
        $gauge = Gauge::factory()->status('out-of-service')->create(['interval_days' => 180]);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['gauge' => $gauge])
            ->call('openCalibration')
            ->set('performed_on', '2026-10-01')
            ->set('performed_by', 'Metrologi Lab')
            ->set('result', 'pass')
            ->set('file', UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'))
            ->call('saveCalibration')
            ->assertHasNoErrors();

        $gauge->refresh();
        $this->assertSame('active', $gauge->status);
        $this->assertSame('2027-03-30', $gauge->next_due_on?->format('Y-m-d'));

        $calibration = $gauge->calibrations()->firstOrFail();
        Storage::disk('local')->assertExists((string) $calibration->file_path);
        $this->get(route('calibrations.file', $calibration))->assertOk()->assertDownload('cert.pdf');
    }

    public function test_a_failure_takes_the_gauge_out_of_service_and_keeps_its_due_date(): void
    {
        $gauge = Gauge::factory()->dueIn(30)->create();
        $due = $gauge->refresh()->next_due_on;
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['gauge' => $gauge])
            ->call('openCalibration')
            ->set('performed_by', 'In-house')
            ->set('result', 'fail')
            ->call('saveCalibration')
            ->assertHasErrors(['as_found' => 'required_if'])
            ->set('as_found', 'Reads +0.05 mm at 100 mm')
            ->call('saveCalibration')
            ->assertHasNoErrors();

        $gauge->refresh();
        $this->assertSame('out-of-service', $gauge->status);
        $this->assertEquals($due, $gauge->next_due_on);
        $this->assertNull($gauge->calibrations()->firstOrFail()->next_due_on);
    }

    public function test_back_dated_and_future_calibrations_are_refused(): void
    {
        $gauge = Gauge::factory()->create();
        $gauge->recordCalibration(['performed_on' => '2026-09-01', 'performed_by' => 'Lab', 'result' => 'pass']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['gauge' => $gauge])
            ->call('openCalibration')
            ->set('performed_by', 'Lab')
            ->set('performed_on', '2026-08-01')
            ->call('saveCalibration')
            ->assertHasErrors('performed_on')
            ->set('performed_on', '2026-10-06')
            ->call('saveCalibration')
            ->assertHasErrors(['performed_on' => 'before_or_equal']);

        $this->assertSame(1, $gauge->calibrations()->count());
    }

    public function test_retired_gauges_cannot_be_calibrated_or_brought_back(): void
    {
        $gauge = Gauge::factory()->create();
        $gauge->transitionTo('retired');

        try {
            $gauge->recordCalibration(['performed_on' => '2026-10-01', 'performed_by' => 'Lab', 'result' => 'pass']);
            $this->fail('A retired gauge was calibrated.');
        } catch (ValidationException) {
            $this->assertSame('retired', $gauge->refresh()->status);
        }

        $this->expectException(ValidationException::class);
        $gauge->transitionTo('active');
    }

    public function test_out_of_service_can_only_return_through_calibration(): void
    {
        $gauge = Gauge::factory()->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Show::class, ['gauge' => $gauge])
            ->call('confirmStatus', 'out-of-service')
            ->call('changeStatus');
        $this->assertSame('out-of-service', $gauge->refresh()->status);

        Livewire::test(Show::class, ['gauge' => $gauge])->call('confirmStatus', 'active')->assertForbidden();
    }

    public function test_create_validates_and_opens_the_new_gauge(): void
    {
        Gauge::factory()->create(['code' => 'CAL-001']);
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Index::class)
            ->call('create')
            ->set('code', 'CAL-001')
            ->set('interval_days', '0')
            ->call('save')
            ->assertHasErrors(['code' => 'unique', 'description' => 'required', 'interval_days' => 'min'])
            ->set('code', 'CAL-002')
            ->set('description', 'Digital caliper')
            ->set('interval_days', '365')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('gauges.show', Gauge::query()->where('code', 'CAL-002')->firstOrFail()));

        $this->assertSame('overdue', Gauge::query()->where('code', 'CAL-002')->firstOrFail()->state(), 'New gauges are unusable until calibrated.');
    }

    public function test_only_gauges_without_calibrations_can_be_deleted(): void
    {
        $used = Gauge::factory()->create();
        $used->recordCalibration(['performed_on' => '2026-10-01', 'performed_by' => 'Lab', 'result' => 'pass']);
        $unused = Gauge::factory()->create();
        $this->actingAs($this->userWithRole('quality-manager'));

        Livewire::test(Index::class)->call('confirmDelete', $used->id)->call('delete');
        Livewire::test(Index::class)->call('confirmDelete', $unused->id)->call('delete');

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
    }

    public function test_inspector_can_view_but_not_calibrate_or_change(): void
    {
        $gauge = Gauge::factory()->create();
        $this->actingAs($this->userWithRole('inspector'));

        $this->get(route('gauges.show', $gauge))->assertOk()->assertDontSee(__('Record calibration'));
        Livewire::test(Show::class, ['gauge' => $gauge])->call('openCalibration')->assertForbidden();
        Livewire::test(Show::class, ['gauge' => $gauge])->call('confirmStatus', 'retired')->assertForbidden();
        Livewire::test(Index::class)->call('create')->assertForbidden();
    }

    public function test_reminders_go_to_each_owner_for_due_and_overdue_gauges_only(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $due = Gauge::factory()->dueIn(3)->create(['owner_id' => $owner->id]);
        $overdue = Gauge::factory()->dueIn(-3)->create(['owner_id' => $owner->id]);
        Gauge::factory()->dueIn(100)->create(['owner_id' => $other->id]);
        Gauge::factory()->dueIn(-3)->status('out-of-service')->create(['owner_id' => $other->id]);

        $this->artisan('qms:calibration-reminders')->assertSuccessful();

        Notification::assertSentTo($owner, CalibrationDue::class, fn (CalibrationDue $n) => $n->gauges->pluck('id')->sort()->values()->all() === collect([$due->id, $overdue->id])->sort()->values()->all());
        Notification::assertNotSentTo($other, CalibrationDue::class);
    }
}
