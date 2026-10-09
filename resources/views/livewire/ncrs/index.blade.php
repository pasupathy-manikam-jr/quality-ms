<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Non-conformances') }}</flux:heading>
            <flux:subheading>{{ __('Failed inspections, rejected certificates and failed calibrations open here automatically. Add complaints and other findings by hand.') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-down-tray" wire:click="export">{{ __('Export') }}</flux:button>
            @can('create-ncrs')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add NCR') }}</flux:button>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->statusCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\Ncr::STATUSES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\Ncr::STATUSES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$status === $value ? 'true' : 'false'"
                :variant="$status === $value ? 'primary' : 'ghost'" wire:click="$set('status', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" @class(['ms-1', '!bg-white/25 !text-white' => $status === $value])>{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number, title, customer, supplier or part')" clearable />
        </div>

        <div class="w-full sm:w-48">
            <x-select wire:model.live="source">
                <x-select.option value="">{{ __('All sources') }}</x-select.option>
                @foreach (\App\Models\Ncr::SOURCES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                @endforeach
            </x-select>
        </div>

        <div class="w-full sm:w-40">
            <x-select wire:model.live="severity">
                <x-select.option value="">{{ __('All severities') }}</x-select.option>
                @foreach (\App\Models\Ncr::SEVERITIES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
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

    <flux:table :paginate="$this->ncrs">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'number'" :direction="$sortDirection" wire:click="sort('number')">{{ __('Number') }}</flux:table.column>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Source') }}</flux:table.column>
            <flux:table.column>{{ __('Severity') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'created_at' || $sortField === ''" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('Raised') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->ncrs as $ncr)
                <flux:table.row :key="$ncr->id">
                    <flux:table.cell><flux:link :href="route('ncrs.show', $ncr)" wire:navigate class="font-medium">{{ $ncr->number }}</flux:link></flux:table.cell>
                    <flux:table.cell class="max-w-md truncate">
                        {{ $ncr->title }}
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$ncr->part?->label(), $ncr->supplier?->name, $ncr->customer])->filter()->join(' · ') }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ __(Str::headline($ncr->source)) }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$ncr->severity" /></flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $ncr->created_at?->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$ncr->status" /></flux:table.cell>
                    <flux:table.cell align="end"><flux:button size="sm" variant="ghost" icon="arrow-right" icon:variant="micro" inset="top bottom" :href="route('ncrs.show', $ncr)" wire:navigate>{{ __('ui_verbs.open') }}</flux:button></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No non-conformances found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-ncrs')
        @include('livewire.ncrs.form-modal', ['title' => __('Add NCR')])
    @endcan
</section>
