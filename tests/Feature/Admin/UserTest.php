<?php

namespace Tests\Feature\Admin;

use App\Livewire\Users\Index;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE = Index::class;

    public function test_admin_can_open_the_users_page(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee(__('Add user'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs($this->userWithRole('viewer'))
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_list_searches_filters_by_role_and_sorts(): void
    {
        $admin = $this->userWithRole('admin');
        User::factory()->create(['name' => 'Aisyah Rahman', 'email' => 'aisyah@example.com'])->assignRole('inspector');
        User::factory()->create(['name' => 'Wei Ming Tan', 'email' => 'wei@example.com'])->assignRole('auditor');

        $this->actingAs($admin);

        Livewire::test(self::PAGE)
            ->set('search', 'aisyah')
            ->assertSee('Aisyah Rahman')
            ->assertDontSee('Wei Ming Tan')
            ->set('search', '')
            ->set('role', 'auditor')
            ->assertSee('Wei Ming Tan')
            ->assertDontSee('Aisyah Rahman')
            ->set('role', '')
            ->call('sort', 'name')
            ->assertSeeInOrder(['Aisyah Rahman', 'Wei Ming Tan']);
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        User::factory()->create(['name' => 'Someone Else']);

        Livewire::test(self::PAGE)
            ->set('search', '%')
            ->assertDontSee('Someone Else');
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        Livewire::test(self::PAGE)
            ->call('create')
            ->set('name', 'Nurul Huda')
            ->set('email', 'nurul@example.com')
            ->set('userRole', 'inspector')
            ->set('password', 'Secret-pass-123')
            ->set('password_confirmation', 'Secret-pass-123')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'nurul@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('inspector'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(AuditLog::query()->whereMorphedTo('auditable', $user)->where('event', 'roles-changed')->exists());
    }

    public function test_create_validates_input(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(self::PAGE)
            ->call('create')
            ->set('email', 'taken@example.com')
            ->set('userRole', 'superuser')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'email' => 'unique', 'userRole' => 'in', 'password' => 'required']);
    }

    public function test_admin_can_edit_a_user_and_keep_the_password(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole('viewer');
        $hash = $user->password;

        Livewire::test(self::PAGE)
            ->call('edit', $user->id)
            ->assertSet('userRole', 'viewer')
            ->set('name', 'New Name')
            ->set('userRole', 'quality-manager')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame($hash, $user->password);
        $this->assertTrue($user->hasRole('quality-manager'));
        $this->assertFalse($user->hasRole('viewer'));
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        Livewire::test(self::PAGE)
            ->call('edit', $admin->id)
            ->set('userRole', 'viewer')
            ->call('save')
            ->assertHasErrors('userRole');

        $this->assertTrue($admin->fresh()?->hasRole('admin'));
    }

    public function test_deleted_users_are_soft_deleted_and_cannot_sign_in(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $user = User::factory()->create();

        Livewire::test(self::PAGE)
            ->call('confirmDelete', $user->id)
            ->call('delete');

        $this->assertSoftDeleted($user);

        auth()->logout();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        Livewire::test(self::PAGE)
            ->call('confirmDelete', $admin->id)
            ->call('delete');

        $this->assertNotSoftDeleted($admin);
    }

    public function test_actions_are_forbidden_without_permission(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->userWithRole('viewer'));

        Livewire::test(self::PAGE)->call('create')->assertForbidden();
        Livewire::test(self::PAGE)->call('edit', $target->id)->assertForbidden();
        Livewire::test(self::PAGE)->call('confirmDelete', $target->id)->assertForbidden();
    }
}
