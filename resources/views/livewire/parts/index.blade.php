<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Parts') }}</flux:heading>
            <flux:subheading>{{ __('Parts you make, by part number and drawing revision.') }}</flux:subheading>
        </div>

        @can('create-parts')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add part') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search part number or name')" clearable />
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

    <flux:table :paginate="$this->parts">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'part_number' || $sortField === ''" :direction="$sortDirection" wire:click="sort('part_number')">{{ __('Part number') }}</flux:table.column>
            <flux:table.column>{{ __('Revision') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Material') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Plans') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->parts as $part)
                <flux:table.row :key="$part->id">
                    <flux:table.cell><flux:badge size="sm" color="blue" variant="outline">{{ $part->part_number }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $part->revision }}</flux:table.cell>
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $part->name }}</flux:table.cell>
                    <flux:table.cell>{{ $part->material?->code ?? '—' }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $part->plans_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @canany(['edit-parts', 'delete-parts'])
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" :aria-label="__('Actions')" />
                                <flux:menu>
                                    @can('edit-parts')
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $part->id }})">{{ __('Edit') }}</flux:menu.item>
                                    @endcan
                                    @can('delete-parts')
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $part->id }})">{{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        @endcanany
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No parts found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @canany(['create-parts', 'edit-parts'])
    <x-modal.form name="part-form" :title="$editingId ? __('Edit part') : __('Add part')" submit="save" icon="squares-2x2">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <flux:input wire:model="part_number" :label="__('Part number')" badge="*" />
            </div>
            <flux:input wire:model="revision" :label="__('Revision')" badge="*" />
        </div>

        <flux:input wire:model="name" :label="__('Name')" badge="*" />

        <x-select wire:model="material_id" :label="__('Material')" :description="__('Lots used for this part must be of this material.')">
            <x-select.option value="">{{ __('Not set') }}</x-select.option>
            @foreach ($this->materials as $material)
                <x-select.option :value="$material->id">{{ $material->code }} · {{ $material->name }}</x-select.option>
            @endforeach
        </x-select>
    </x-modal.form>
    @endcanany

    @can('delete-parts')
    <x-modal.confirm name="confirm-part-delete" :title="__('Delete this part?')" :text="__('Parts with inspection plans cannot be deleted.')" confirm="delete" icon="trash" :confirm-label="__('Delete')" />
    @endcan
</section>
