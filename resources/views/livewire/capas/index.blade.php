<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Corrective and preventive actions') }}</flux:heading>
            <flux:subheading>{{ __('Root-cause fixes, worked through the eight disciplines (8D).') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-down-tray" wire:click="export">{{ __('Export') }}</flux:button>
            @can('create-capas')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add CAPA') }}</flux:button>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->statusCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\Capa::STATUSES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\Capa::STATUSES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$status === $value ? 'true' : 'false'"
                :variant="$status === $value ? 'primary' : 'ghost'" wire:click="$set('status', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" class="ms-1">{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number, title or owner')" clearable />
        </div>

        <flux:switch wire:model.live="overdue" :label="__('Overdue only')" />

        <flux:spacer />

        <div class="w-24">
            <x-select wire:model.live="perPage" :aria-label="__('Rows per page')">
                @foreach ($this->perPageOptions() as $option)
                    <x-select.option :value="$option">{{ $option }}</x-select.option>
                @endforeach
            </x-select>
        </div>
    </div>

    <flux:table :paginate="$this->capas">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'number'" :direction="$sortDirection" wire:click="sort('number')">{{ __('Number') }}</flux:table.column>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Owner') }}</flux:table.column>
            <flux:table.column align="end">{{ __('NCRs') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'due_on'" :direction="$sortDirection" wire:click="sort('due_on')">{{ __('Due') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->capas as $capa)
                <flux:table.row :key="$capa->id">
                    <flux:table.cell><flux:link :href="route('capas.show', $capa)" wire:navigate class="font-medium">{{ $capa->number }}</flux:link></flux:table.cell>
                    <flux:table.cell class="max-w-md truncate">{{ $capa->title }}</flux:table.cell>
                    <flux:table.cell>{{ $capa->owner?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $capa->ncrs_count }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <span @class(['font-medium text-red-600 dark:text-red-400' => $capa->isOverdue()])>{{ $capa->due_on?->format('Y-m-d') ?? '—' }}</span>
                    </flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$capa->status" /></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No CAPAs found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-capas')
    <x-modal.form name="capa-form" :title="__('Add CAPA')" submit="save" icon="wrench-screwdriver" :submit-label="__('Create')">
        <flux:input wire:model="title" :label="__('Title')" badge="*" />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-select wire:model="type" :label="__('Type')" badge="*">
                @foreach (\App\Models\Capa::TYPES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                @endforeach
            </x-select>
            <x-select wire:model="owner_id" :label="__('Owner')" badge="*">
                @foreach ($this->owners as $owner)
                    <x-select.option :value="$owner->id">{{ $owner->name }}</x-select.option>
                @endforeach
            </x-select>
            <flux:input wire:model="due_on" :label="__('Due')" type="date" badge="*" />
        </div>
    </x-modal.form>
    @endcan
</section>
