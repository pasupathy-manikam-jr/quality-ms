<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Materials') }}</flux:heading>
            <flux:subheading>{{ __('Materials you buy, and the specification limits their certificates are checked against.') }}</flux:subheading>
        </div>

        @can('create-materials')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add material') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search code, name or specification')" clearable />
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

    <flux:table :paginate="$this->materials">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'code'" :direction="$sortDirection" wire:click="sort('code')">{{ __('Code') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Specification') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Limits') }}</flux:table.column>
            <flux:table.column align="end">{{ \App\Models\Lot::label() }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->materials as $material)
                <flux:table.row :key="$material->id">
                    <flux:table.cell>
                        <a href="{{ route('materials.show', $material) }}" wire:navigate>
                            <flux:badge size="sm" color="blue" variant="outline">{{ $material->code }}</flux:badge>
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:link :href="route('materials.show', $material)" variant="ghost" wire:navigate class="font-medium">{{ $material->name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $material->specification }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($material->limits_count === 0)
                            <x-status-badge status="missing" />
                        @else
                            {{ $material->limits_count }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">{{ $material->lots_count }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" :aria-label="__('Actions')" />
                            <flux:menu>
                                <flux:menu.item icon="eye" :href="route('materials.show', $material)" wire:navigate>{{ __('View limits') }}</flux:menu.item>
                                @can('edit-materials')
                                    <flux:menu.item icon="pencil-square" wire:click="edit({{ $material->id }})">{{ __('Edit') }}</flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No materials found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @canany(['create-materials', 'edit-materials'])
    <x-modal.form name="material-form" :title="$editingId ? __('Edit material') : __('Add material')" submit="save" icon="cube">
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="code" :label="__('Code')" :badge="__('Required')" :placeholder="__('e.g. S355JR')" />
            <div class="sm:col-span-2">
                <flux:input wire:model="name" :label="__('Name')" :badge="__('Required')" />
            </div>
        </div>

        <flux:input wire:model="specification" :label="__('Specification')" :placeholder="__('e.g. EN 10025-2')" />
        <flux:input wire:model="size_label" :label="__('Size dimension')" :placeholder="__('e.g. Thickness (mm)')"
            :description="__('Fill this in when limits depend on size. Leave blank if they don\'t.')" />
    </x-modal.form>
    @endcanany
</section>
