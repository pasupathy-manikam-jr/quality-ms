<?php

namespace App\Livewire\Roles;

use App\Models\AuditLog;
use Database\Seeders\RolesSeeder;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and what each may do. Built-in roles are defined in RolesSeeder and re-synced on every
 * deploy, so they are shown read-only; custom roles are created and edited here.
 *
 * @property-read Collection<int, Role> $roles
 */
#[Title('Roles')]
class Index extends Component
{
    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public bool $readOnly = false;

    #[Locked]
    public ?int $deletingId = null;

    public string $name = '';

    /** @var array<int, string> permission names */
    public array $permissions = [];

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->withCount(['users', 'permissions'])->orderBy('id')->get();
    }

    public function isBuiltIn(Role $role): bool
    {
        return array_key_exists($role->name, RolesSeeder::ROLES);
    }

    public function create(): void
    {
        $this->authorize('create-roles');

        $this->resetForm();
        Flux::modal('role-form')->show();
    }

    public function edit(int $id): void
    {
        $role = Role::query()->with('permissions')->findOrFail($id);
        $this->readOnly = $this->isBuiltIn($role) || ! auth()->user()?->can('edit-roles');

        if (! $this->readOnly) {
            $this->authorize('edit-roles');
        }

        $this->resetValidation();
        $this->editingId = (int) $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->values()->all();

        Flux::modal('role-form')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit-roles' : 'create-roles');

        $role = $this->editingId ? Role::query()->findOrFail($this->editingId) : new Role(['guard_name' => 'web']);
        abort_if($this->readOnly || $this->isBuiltIn($role), 403, __('Built-in roles are defined by the system.'));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::notIn(array_keys(RolesSeeder::ROLES)), Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ], ['name.not_in' => __('That name belongs to a built-in role.')], ['name' => __('role name')]);

        $old = $role->exists ? ['name' => $role->name, 'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all()] : null;

        DB::transaction(function () use ($role, $validated, $old) {
            $role->name = trim($validated['name']);
            $role->save();
            $role->syncPermissions(Permission::query()->whereIn('name', $validated['permissions'])->get());

            AuditLog::query()->create([
                'user_id' => auth()->id(),
                'auditable_type' => $role->getMorphClass(),
                'auditable_id' => $role->id,
                'event' => $old ? 'updated' : 'created',
                'old_values' => $old,
                'new_values' => ['name' => $role->name, 'permissions' => $this->sorted($validated['permissions'])],
                'ip_address' => request()->ip(),
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        unset($this->roles);
        Flux::modal('role-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? __('Role updated.') : __('Role created.'));
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete-roles');

        $role = Role::query()->findOrFail($id);
        abort_if($this->isBuiltIn($role), 403);

        $this->deletingId = (int) $role->id;
        Flux::modal('confirm-role-delete')->show();
    }

    public function delete(): void
    {
        $this->authorize('delete-roles');

        $role = Role::query()->withCount('users')->findOrFail($this->deletingId);
        abort_if($this->isBuiltIn($role), 403);
        Flux::modal('confirm-role-delete')->close();
        $this->deletingId = null;

        if ($role->users_count > 0) {
            Flux::toast(variant: 'danger', text: __('Move its users to another role first.'));

            return;
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        unset($this->roles);
        Flux::toast(variant: 'success', text: __('Role deleted.'));
    }

    public function render(): View
    {
        return view('livewire.roles.index');
    }

    /**
     * @param  array<int, string>  $names
     * @return list<string>
     */
    private function sorted(array $names): array
    {
        sort($names);

        return $names;
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'readOnly', 'name', 'permissions');
        $this->resetValidation();
    }
}
