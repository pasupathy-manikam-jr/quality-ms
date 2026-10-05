<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Suppliers') }}</flux:heading>
            <flux:subheading>{{ __('Approved suppliers, and how their deliveries have gone: certificates accepted and NCRs in the last 12 months.') }}</flux:subheading>
        </div>

        @can('create-suppliers')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add supplier') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search code, name or contact')" clearable />
        </div>

        <div class="w-full sm:w-48">
            <x-select wire:model.live="approval">
                <x-select.option value="">{{ __('All suppliers') }}</x-select.option>
                <x-select.option value="approved">{{ __('Approved') }}</x-select.option>
                <x-select.option value="not-approved">{{ __('Not approved') }}</x-select.option>
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

    <flux:table :paginate="$this->suppliers">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'code'" :direction="$sortDirection" wire:click="sort('code')">{{ __('Code') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Contact') }}</flux:table.column>
            <flux:table.column>{{ __('Approval') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Certificates') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Accepted') }}</flux:table.column>
            <flux:table.column align="end">{{ __('NCRs (12 months)') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->suppliers as $supplier)
                <flux:table.row :key="$supplier->id">
                    <flux:table.cell><flux:badge size="sm" color="blue" variant="outline">{{ $supplier->code }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $supplier->name }}</flux:table.cell>
                    <flux:table.cell>
                        <div>{{ $supplier->contact_name }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $supplier->email }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @if ($supplier->is_approved)
                            <x-status-badge status="approved" />
                            <span class="ms-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $supplier->approved_on?->format('Y-m-d') }}</span>
                        @else
                            <x-status-badge status="not-approved" />
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">{{ $supplier->certificates_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @php($rate = $supplier->acceptanceRate())
                        <span @class(['font-medium text-red-600 dark:text-red-400' => $rate !== null && $rate < 90])>{{ $rate === null ? '—' : $rate.'%' }}</span>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($supplier->recent_ncrs_count)
                            <flux:link :href="route('ncrs.index', ['search' => $supplier->name])" wire:navigate>{{ $supplier->recent_ncrs_count }}</flux:link>
                        @else
                            0
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @canany(['edit-suppliers', 'delete-suppliers'])
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" :aria-label="__('Actions')" />
                                <flux:menu>
                                    @can('edit-suppliers')
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $supplier->id }})">{{ __('Edit') }}</flux:menu.item>
                                    @endcan
                                    @can('delete-suppliers')
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $supplier->id }})">{{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        @endcanany
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="py-8 text-center">{{ __('No suppliers found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @canany(['create-suppliers', 'edit-suppliers'])
    <x-modal.form name="supplier-form" :title="$editingId ? __('Edit supplier') : __('Add supplier')" submit="save" icon="truck">
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="code" :label="__('Code')" :badge="__('Required')" />
            <div class="sm:col-span-2">
                <flux:input wire:model="name" :label="__('Name')" :badge="__('Required')" />
            </div>
        </div>

        <flux:input wire:model="contact_name" :label="__('Contact person')" />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="email" :label="__('Email')" type="email" />
            <flux:input wire:model="phone" :label="__('Phone')" />
        </div>

        <flux:switch wire:model.live="is_approved" :label="__('Approved supplier')" />

        @if ($is_approved)
            <flux:input wire:model="approved_on" :label="__('Approved on')" type="date" :badge="__('Required')" />
        @endif
    </x-modal.form>
    @endcanany

    @can('delete-suppliers')
    <x-modal.confirm name="confirm-supplier-delete" :title="__('Delete this supplier?')" :text="__('Suppliers with certificates cannot be deleted.')" confirm="delete" icon="trash" :confirm-label="__('Delete')" />
    @endcan
</section>
