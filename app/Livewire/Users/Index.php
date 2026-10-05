<?php

namespace App\Livewire\Users;

use App\Concerns\PasswordValidationRules;
use App\Livewire\Concerns\WithTable;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Users')]
class Index extends Component
{
    use PasswordValidationRules, WithTable;

    #[Url(except: '')]
    public string $role = '';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    public string $name = '';

    public string $email = '';

    public string $userRole = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles:id,name')
            ->when(array_key_exists($this->role, RolesSeeder::ROLES), fn ($q) => $q->role($this->role));

        return $this->paginateTable($query, ['name', 'email'], ['name', 'email', 'created_at']);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function roles(): array
    {
        return collect(RolesSeeder::ROLES)->keys()->mapWithKeys(fn (string $name) => [$name => Str::headline($name)])->all();
    }

    public function create(): void
    {
        $this->authorize('create-users');

        $this->resetForm();
        Flux::modal('user-form')->show();
    }

    public function edit(int $id): void
    {
        $this->authorize('edit-users');

        $user = User::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->userRole = $user->getRoleNames()->first() ?? '';

        Flux::modal('user-form')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit-users' : 'create-users');

        $user = $this->editingId ? User::query()->findOrFail($this->editingId) : new User;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'userRole' => ['required', Rule::in(array_keys(RolesSeeder::ROLES))],
            'password' => $user->exists ? ['nullable', 'string', Password::default(), 'confirmed'] : $this->passwordRules(),
        ], attributes: ['userRole' => __('role')]);

        if ($user->is(auth()->user()) && $validated['userRole'] !== 'admin' && $user->hasRole('admin')) {
            $this->addError('userRole', __('You cannot remove your own admin role.'));

            return;
        }

        $user->fill(['name' => $validated['name'], 'email' => $validated['email']]);

        if ($validated['password']) {
            $user->password = $validated['password'];
        }

        if (! $user->exists) {
            $user->email_verified_at = now();
        }

        $user->save();

        $oldRoles = $user->getRoleNames()->all();

        if ($oldRoles !== [$validated['userRole']]) {
            $user->syncRoles([$validated['userRole']]);
            $user->audit('roles-changed', ['roles' => $oldRoles], ['roles' => [$validated['userRole']]]);
        }

        Flux::modal('user-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? __('User updated.') : __('User created.'));
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete-users');

        $this->deletingId = User::query()->findOrFail($id)->id;
        Flux::modal('confirm-user-delete')->show();
    }

    public function delete(): void
    {
        $this->authorize('delete-users');

        $user = User::query()->findOrFail($this->deletingId);

        if ($user->is(auth()->user())) {
            Flux::modal('confirm-user-delete')->close();
            Flux::toast(variant: 'danger', text: __('You cannot delete your own account here.'));

            return;
        }

        $user->delete();

        $this->deletingId = null;
        Flux::modal('confirm-user-delete')->close();
        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'email', 'userRole', 'password', 'password_confirmation');
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.users.index');
    }
}
