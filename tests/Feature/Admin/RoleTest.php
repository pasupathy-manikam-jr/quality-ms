<?php

use App\Livewire\Roles\Index;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->actingAs($this->userWithRole('admin')));

it('lists built-in and custom roles with their user counts', function () {
    Role::findOrCreate('Store keeper', 'web');

    $this->get(route('roles.index'))->assertOk()->assertSee('Quality Manager')->assertSee('Store keeper');
});

it('creates a custom role with chosen permissions, audited, and it can be given to a user', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Store keeper')
        ->set('permissions', ['manage-certificates', 'create-certificates'])
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::findByName('Store keeper', 'web');
    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['create-certificates', 'manage-certificates'])
        ->and(AuditLog::query()->where('auditable_type', $role->getMorphClass())->where('auditable_id', $role->id)->exists())->toBeTrue();

    $user = User::factory()->create();
    Livewire::test(UsersIndex::class)->call('edit', $user->id)->set('userRole', 'Store keeper')->call('save')->assertHasNoErrors();

    expect($user->fresh()?->can('create-certificates'))->toBeTrue()
        ->and($user->fresh()?->can('verify-certificates'))->toBeFalse();
});

it('keeps built-in roles read-only and their names reserved', function () {
    $inspector = Role::findByName('inspector', 'web');

    Livewire::test(Index::class)->call('edit', $inspector->id)->assertSet('readOnly', true)->call('save')->assertForbidden();
    Livewire::test(Index::class)->call('confirmDelete', $inspector->id)->assertForbidden();
    Livewire::test(Index::class)->call('create')->set('name', 'viewer')->call('save')->assertHasErrors(['name' => 'not_in']);
});

it('rejects permissions that do not exist', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Odd')
        ->set('permissions', ['launch-rockets'])
        ->call('save')
        ->assertHasErrors('permissions.0');
});

it('only deletes custom roles nobody holds', function () {
    $held = Role::findOrCreate('Held', 'web');
    User::factory()->create()->assignRole($held);
    $free = Role::findOrCreate('Free', 'web');

    Livewire::test(Index::class)->call('confirmDelete', $held->id)->call('delete');
    Livewire::test(Index::class)->call('confirmDelete', $free->id)->call('delete');

    expect(Role::query()->where('name', 'Held')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'Free')->exists())->toBeFalse();
});

it('is for administrators only', function () {
    $this->actingAs($this->userWithRole('quality-manager'))->get(route('roles.index'))->assertForbidden();
});
