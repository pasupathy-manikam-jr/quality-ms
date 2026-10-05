<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('materials.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <flux:heading size="xl" level="1">{{ $material->code }}</flux:heading>
                <flux:subheading>{{ $material->name }}</flux:subheading>
            </div>
        </div>

        @can('edit-materials')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add limit') }}</flux:button>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <flux:card class="space-y-4 self-start">
            <flux:heading>{{ __('Summary') }}</flux:heading>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Specification') }}</dt>
                    <dd class="text-zinc-800 dark:text-white">{{ $material->specification ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Size dimension') }}</dt>
                    <dd class="text-zinc-800 dark:text-white">{{ $material->size_label ?: __('Limits do not depend on size') }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ \App\Models\Lot::label() }}</dt>
                    <dd class="text-zinc-800 dark:text-white">{{ $material->lots_count }}</dd>
                </div>
            </dl>
        </flux:card>

        <div class="space-y-3 lg:col-span-2">
            <flux:heading>{{ __('Specification limits') }}</flux:heading>
            <flux:text>{{ __('Every certified result for this material is checked against these. Values on the limit itself pass.') }}</flux:text>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Property') }}</flux:table.column>
                    <flux:table.column>{{ __('Accepted range') }}</flux:table.column>
                    <flux:table.column>{{ __('Unit') }}</flux:table.column>
                    @if ($material->size_label)
                        <flux:table.column>{{ $material->size_label }}</flux:table.column>
                    @endif
                    <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($material->limits as $limit)
                        <flux:table.row :key="$limit->id">
                            <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $limit->property }}</flux:table.cell>
                            <flux:table.cell>{{ $limit->rangeLabel() }}</flux:table.cell>
                            <flux:table.cell>{{ $limit->unit }}</flux:table.cell>
                            @if ($material->size_label)
                                <flux:table.cell>{{ $limit->sizeLabel() }}</flux:table.cell>
                            @endif
                            <flux:table.cell align="end">
                                @can('edit-materials')
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" inset="top bottom" wire:click="edit({{ $limit->id }})" :aria-label="__('Edit')" />
                                    <flux:button variant="ghost" size="sm" icon="trash" inset="top bottom" wire:click="confirmDelete({{ $limit->id }})" :aria-label="__('Remove')" />
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center">{{ __('No limits yet. Certificates for this material cannot be verified until it has some.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    @can('edit-materials')
    <x-modal.form name="limit-form" :title="$editingId ? __('Edit limit') : __('Add limit')" submit="save" icon="adjustments-horizontal">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <flux:input wire:model="property" :label="__('Property')" badge="*" :placeholder="__('e.g. C, Yield, Moisture')" />
            </div>
            <flux:input wire:model="unit" :label="__('Unit')" :placeholder="__('e.g. %, MPa')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="min" :label="__('Minimum')" inputmode="decimal" />
            <flux:input wire:model="max" :label="__('Maximum')" inputmode="decimal" />
        </div>

        @if ($material->size_label)
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="size_from" :label="__('From :size', ['size' => $material->size_label])" inputmode="decimal" />
                <flux:input wire:model="size_to" :label="__('To :size', ['size' => $material->size_label])" inputmode="decimal" />
            </div>
            <flux:text class="text-xs">{{ __('Leave both blank when the limit applies to every size. Both ends are inclusive.') }}</flux:text>
        @endif
    </x-modal.form>

    <x-modal.confirm name="confirm-limit-delete" :title="__('Remove this limit?')" :text="__('Certificates still waiting for verification will be checked without it.')" confirm="delete" icon="trash" :confirm-label="__('Remove')" />
    @endcan

    <x-audit-history :record="$material" />
</section>
