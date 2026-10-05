@php($modules = \Database\Seeders\RolesSeeder::PERMISSIONS)
@php($actions = collect($modules)->flatten()->unique()->values())
@php($moduleNames = ['users' => 'Users', 'roles' => 'Roles', 'suppliers' => 'Suppliers', 'materials' => 'Materials', 'certificates' => 'Certificates', 'gauges' => 'Gauges', 'parts' => 'Parts', 'inspection-plans' => 'Inspection plans', 'inspections' => 'Inspections', 'ncrs' => 'Non-conformances', 'capas' => 'CAPA', 'documents' => 'Documents', 'audits' => 'Internal audits'])

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Roles') }}</flux:heading>
            <flux:subheading>{{ __('What each role may do. Built-in roles are fixed; add custom roles for anything else.') }}</flux:subheading>
        </div>

        @can('create-roles')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add role') }}</flux:button>
        @endcan
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Permissions') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Users') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->roles as $role)
                @php($builtIn = $this->isBuiltIn($role))
                <flux:table.row :key="$role->id">
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ \Database\Seeders\RolesSeeder::label($role->name) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$builtIn ? 'zinc' : 'blue'">{{ $builtIn ? __('Built-in') : __('Custom') }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">{{ $role->permissions_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:link :href="route('users.index', ['role' => $role->name])" wire:navigate>{{ $role->users_count }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" :aria-label="__('Actions')" />
                            <flux:menu>
                                @if ($builtIn || ! auth()->user()->can('edit-roles'))
                                    <flux:menu.item icon="eye" wire:click="edit({{ $role->id }})">{{ __('View permissions') }}</flux:menu.item>
                                @else
                                    <flux:menu.item icon="pencil-square" wire:click="edit({{ $role->id }})">{{ __('Edit') }}</flux:menu.item>
                                @endif
                                @if (! $builtIn)
                                    @can('delete-roles')
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $role->id }})">{{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <x-modal.form name="role-form" icon="shield-check" submit="save" width="xl"
        :title="$readOnly ? \Database\Seeders\RolesSeeder::label($name) : ($editingId ? __('Edit role') : __('Add role'))"
        :description="$readOnly ? __('Built-in roles are defined by the system and cannot be changed. Create a custom role to give a different set of permissions.') : __('Tick what people with this role may do. They always need View to open a module.')"
        :submit-label="__('Save')">
        @unless ($readOnly)
            <flux:input wire:model="name" :label="__('Role name')" badge="*" :placeholder="__('e.g. Store keeper')" />
        @endunless

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-start dark:border-zinc-700">
                        <th class="py-2 pe-4 text-start font-medium text-zinc-800 dark:text-white">{{ __('Module') }}</th>
                        @foreach ($actions as $action)
                            <th class="px-2 py-2 text-center font-medium text-zinc-800 dark:text-white">{{ $action === 'manage' ? __('View') : __(Str::headline($action)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modules as $module => $moduleActions)
                        <tr wire:key="perm-{{ $module }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="py-2 pe-4 text-zinc-700 dark:text-zinc-300">{{ __($moduleNames[$module] ?? Str::headline($module)) }}</td>
                            @foreach ($actions as $action)
                                <td class="px-2 py-2 text-center">
                                    @if (in_array($action, $moduleActions, true))
                                        <input type="checkbox" value="{{ $action }}-{{ $module }}" wire:model="permissions" @disabled($readOnly)
                                            aria-label="{{ __(Str::headline($action)) }} · {{ __($moduleNames[$module] ?? Str::headline($module)) }}"
                                            class="size-4 rounded border-zinc-300 accent-[var(--color-accent)] disabled:opacity-60 dark:border-zinc-600">
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <flux:error name="permissions" />
    </x-modal.form>

    @can('delete-roles')
        <x-modal.confirm name="confirm-role-delete" :title="__('Delete this role?')" :text="__('Only roles nobody holds can be deleted.')" confirm="delete" icon="trash" :confirm-label="__('Delete')" />
    @endcan
</section>
