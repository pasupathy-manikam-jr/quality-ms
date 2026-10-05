<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Gauges') }}</flux:heading>
            <flux:subheading>{{ __('Measuring equipment and when each one is next due for calibration.') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-down-tray" wire:click="export">{{ __('Export') }}</flux:button>
            @can('create-gauges')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add gauge') }}</flux:button>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->stateCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\Gauge::STATES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\Gauge::STATES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$state === $value ? 'true' : 'false'"
                :variant="$state === $value ? 'primary' : 'ghost'" wire:click="$set('state', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" class="ms-1">{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search code, description, type or location')" clearable />
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

    <flux:table :paginate="$this->gauges">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'code'" :direction="$sortDirection" wire:click="sort('code')">{{ __('Code') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'description'" :direction="$sortDirection" wire:click="sort('description')">{{ __('Description') }}</flux:table.column>
            <flux:table.column>{{ __('Location') }}</flux:table.column>
            <flux:table.column>{{ __('Owner') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'next_due_on' || $sortField === ''" :direction="$sortDirection" wire:click="sort('next_due_on')">{{ __('Next due') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->gauges as $gauge)
                <flux:table.row :key="$gauge->id">
                    <flux:table.cell>
                        <a href="{{ route('gauges.show', $gauge) }}" wire:navigate><flux:badge size="sm" color="blue" variant="outline">{{ $gauge->code }}</flux:badge></a>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:link :href="route('gauges.show', $gauge)" variant="ghost" wire:navigate class="font-medium">{{ $gauge->description }}</flux:link>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$gauge->measuring_range, $gauge->resolution])->filter()->join(' · ') }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $gauge->location }}</flux:table.cell>
                    <flux:table.cell>{{ $gauge->owner?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $gauge->next_due_on?->format('Y-m-d') ?? __('Never calibrated') }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$gauge->state()" /></flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="arrow-right" icon:variant="micro" inset="top bottom" :href="route('gauges.show', $gauge)" wire:navigate>{{ __('ui_verbs.open') }}</flux:button>
                        @canany(['edit-gauges', 'delete-gauges'])
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" :aria-label="__('Actions')" />
                                <flux:menu>
                                    @can('edit-gauges')
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $gauge->id }})">{{ __('Edit') }}</flux:menu.item>
                                    @endcan
                                    @can('delete-gauges')
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $gauge->id }})">{{ __('Delete') }}</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        @endcanany
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No gauges found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @canany(['create-gauges', 'edit-gauges'])
    <x-modal.form name="gauge-form" :title="$editingId ? __('Edit gauge') : __('Add gauge')" submit="save" icon="scale">
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="code" :label="__('Code')" badge="*" :placeholder="__('Tag number')" />
            <div class="sm:col-span-2">
                <flux:input wire:model="description" :label="__('Description')" badge="*" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="type" :label="__('Type')" :placeholder="__('e.g. Caliper')" />
            <flux:input wire:model="measuring_range" :label="__('Range')" :placeholder="__('e.g. 0–150 mm')" />
            <flux:input wire:model="resolution" :label="__('Resolution')" :placeholder="__('e.g. 0.01 mm')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="location" :label="__('Location')" />
            <x-select wire:model="owner_id" :label="__('Owner')" :description="__('Gets the reminder email.')">
                <x-select.option value="">{{ __('No owner') }}</x-select.option>
                @foreach ($this->owners as $owner)
                    <x-select.option :value="$owner->id">{{ $owner->name }}</x-select.option>
                @endforeach
            </x-select>
        </div>

        <flux:input wire:model="interval_days" :label="__('Calibration interval (days)')" badge="*" inputmode="numeric" class="max-w-40" />
    </x-modal.form>
    @endcanany

    @can('delete-gauges')
    <x-modal.confirm name="confirm-gauge-delete" :title="__('Delete this gauge?')" :text="__('Only gauges with no calibration records can be deleted. Retire the others.')" confirm="delete" icon="trash" :confirm-label="__('Delete')" />
    @endcan
</section>
