<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Certificates') }}</flux:heading>
            <flux:subheading>{{ __('Supplier material certificates, checked against specification limits before the material is used.') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-down-tray" wire:click="export">{{ __('Export') }}</flux:button>
            @can('create-certificates')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add certificate') }}</flux:button>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->statusCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\Certificate::STATUSES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\Certificate::STATUSES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$status === $value ? 'true' : 'false'"
                :variant="$status === $value ? 'primary' : 'ghost'" wire:click="$set('status', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" class="ms-1">{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number, PO, supplier or :lot', ['lot' => strtolower(\App\Models\Lot::label())])" clearable />
        </div>

        <div class="w-full sm:w-56">
            <x-select wire:model.live="supplier">
                <x-select.option value="">{{ __('All suppliers') }}</x-select.option>
                @foreach ($this->suppliers as $option)
                    <x-select.option :value="$option->id">{{ $option->name }}</x-select.option>
                @endforeach
            </x-select>
        </div>

        <div class="w-full sm:w-56">
            <x-select wire:model.live="type">
                <x-select.option value="">{{ __('All types') }}</x-select.option>
                @foreach (\App\Models\Certificate::TYPES as $value => $label)
                    <x-select.option :value="$value">{{ __($label) }}</x-select.option>
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

    <flux:table :paginate="$this->certificates">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'number'" :direction="$sortDirection" wire:click="sort('number')">{{ __('Number') }}</flux:table.column>
            <flux:table.column>{{ __('Supplier') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'issued_on' || $sortField === ''" :direction="$sortDirection" wire:click="sort('issued_on')">{{ __('Issued') }}</flux:table.column>
            <flux:table.column align="end">{{ \App\Models\Lot::label() }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->certificates as $certificate)
                <flux:table.row :key="$certificate->id">
                    <flux:table.cell>
                        <flux:link :href="route('certificates.show', $certificate)" wire:navigate class="font-medium">{{ $certificate->number }}</flux:link>
                        @if ($certificate->po_number)
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $certificate->po_number }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $certificate->supplier->name }}</flux:table.cell>
                    <flux:table.cell>{{ $certificate->typeLabel() }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $certificate->issued_on->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $certificate->lots_count }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$certificate->status" /></flux:table.cell>
                    <flux:table.cell align="end"><flux:button size="sm" variant="ghost" icon="arrow-right" icon:variant="micro" inset="top bottom" :href="route('certificates.show', $certificate)" wire:navigate>{{ __('ui_verbs.open') }}</flux:button></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No certificates found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-certificates')
        @include('livewire.certificates.form-modal', ['title' => __('Add certificate')])
    @endcan
</section>
