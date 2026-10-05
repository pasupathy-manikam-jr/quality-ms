<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:subheading>{{ __('People who can sign in, and the role each one holds.') }}</flux:subheading>
        </div>

        @can('create-users')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add user') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name or email')" clearable />
        </div>

        <div class="w-full sm:w-48">
            <x-select wire:model.live="role">
                <x-select.option value="">{{ __('All roles') }}</x-select.option>
                @foreach ($this->roles as $value => $label)
                    <x-select.option :value="$value">{{ $label }}</x-select.option>
                @endforeach
            </x-select>
        </div>

        <flux:spacer />

        <div class="w-24">
            <x-select wire:model.live="perPage" :aria-label="__('Rows per page')">
                @foreach ($this->perPageOptions() as $option)
                    <x-select.option :value="$option">{{ $option }}</x-select.option>
                @endforeach
            </x-select>
        </div>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'email'" :direction="$sortDirection" wire:click="sort('email')">{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('Added') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:avatar size="xs" :name="$user->name" :initials="$user->initials()" />
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $user->name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @foreach ($user->roles as $userRole)
                            <flux:badge size="sm" color="zinc">{{ \Database\Seeders\RolesSeeder::label($userRole->name) }}</flux:badge>
                        @endforeach
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $user->created_at?->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @canany(['edit-users', 'delete-users'])
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" :aria-label="__('Actions')" />
                                <flux:menu>
                                    @can('edit-users')
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $user->id }})">{{ __('Edit') }}</flux:menu.item>
                                    @endcan
                                    @can('delete-users')
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $user->id }})">{{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        @endcanany
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="py-8 text-center">{{ __('No users found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @canany(['create-users', 'edit-users'])
    <x-modal.form name="user-form" :title="$editingId ? __('Edit user') : __('Add user')" submit="save" icon="user">
        <flux:input wire:model="name" :label="__('Name')" badge="*" />
        <flux:input wire:model="email" :label="__('Email')" type="email" badge="*" />

        <x-select wire:model="userRole" :label="__('Role')" badge="*" :placeholder="__('Choose a role')">
            @foreach ($this->roles as $value => $label)
                <x-select.option :value="$value">{{ $label }}</x-select.option>
            @endforeach
        </x-select>

        <flux:input wire:model="password" :label="__('Password')" type="password" viewable :badge="$editingId ? null : '*'"
            :description="$editingId ? __('Leave blank to keep the current password.') : null" />
        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" viewable />
    </x-modal.form>
    @endcanany

    @can('delete-users')
    <x-modal.confirm name="confirm-user-delete" :title="__('Delete this user?')" :text="__('They will no longer be able to sign in. Their name stays on every record they created or changed.')" confirm="delete" icon="trash" :confirm-label="__('Delete')" />
    @endcan
</section>
