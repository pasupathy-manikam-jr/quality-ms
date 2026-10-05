<?php

namespace Tests\Feature\Foundation;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_and_delete_are_logged_with_the_acting_user(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $user = User::factory()->create(['name' => 'Before']);
        $user->update(['name' => 'After']);
        $user->delete();

        $logs = AuditLog::query()->whereMorphedTo('auditable', $user)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('event')->all());
        $this->assertSame(['name' => 'Before'], $logs[1]->old_values);
        $this->assertSame(['name' => 'After'], $logs[1]->new_values);
        $this->assertTrue($logs->every(fn (AuditLog $log) => $log->user_id === $actor->id));
    }

    public function test_hidden_attributes_are_never_logged(): void
    {
        $user = User::factory()->create();
        $user->update(['password' => 'Another-secret-1']);

        $logs = AuditLog::query()->whereMorphedTo('auditable', $user)->get();

        $this->assertCount(1, $logs, 'A password-only change writes no update entry.');
        $this->assertArrayNotHasKey('password', $logs[0]->new_values ?? []);
        $this->assertArrayNotHasKey('remember_token', $logs[0]->new_values ?? []);
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $log = AuditLog::query()->whereMorphedTo('auditable', User::factory()->create())->firstOrFail();

        $this->expectException(LogicException::class);
        $log->update(['event' => 'tampered']);
    }

    public function test_entries_cannot_be_deleted(): void
    {
        $log = AuditLog::query()->whereMorphedTo('auditable', User::factory()->create())->firstOrFail();

        $this->expectException(LogicException::class);
        $log->delete();
    }
}
