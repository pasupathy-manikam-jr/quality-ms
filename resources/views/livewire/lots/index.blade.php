@php($lotLabel = \App\Models\Lot::label())

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $lotLabel }}</flux:heading>
            <flux:subheading>{{ __('Find any :lot and the certificate it arrived with.', ['lot' => strtolower($lotLabel)]) }}</flux:subheading>
        </div>
        <flux:button icon="arrow-down-tray" wire:click="export">{{ __('Export') }}</flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search :lot, certificate or supplier', ['lot' => strtolower($lotLabel)])" clearable autofocus />
        </div>

        <div class="w-full sm:w-56">
            <x-select wire:model.live="material">
                <x-select.option value="">{{ __('All materials') }}</x-select.option>
                @foreach ($this->materials as $option)
                    <x-select.option :value="$option->id">{{ $option->code }}</x-select.option>
                @endforeach
            </x-select>
        </div>

        <div class="w-full sm:w-48">
            <x-select wire:model.live="status">
                <x-select.option value="">{{ __('Any certificate status') }}</x-select.option>
                @foreach (\App\Models\Certificate::STATUSES as $value)
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

    <flux:table :paginate="$this->lots">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'lot_number'" :direction="$sortDirection" wire:click="sort('lot_number')">{{ $lotLabel }}</flux:table.column>
            <flux:table.column>{{ __('Material') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Quantity') }}</flux:table.column>
            <flux:table.column>{{ __('Certificate') }}</flux:table.column>
            <flux:table.column>{{ __('Supplier') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'expires_on'" :direction="$sortDirection" wire:click="sort('expires_on')">{{ __('Expires') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->lots as $lot)
                <flux:table.row :key="$lot->id">
                    <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $lot->lot_number }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="blue" variant="outline">{{ $lot->material->code }}</flux:badge>
                        @if ($lot->size !== null && $lot->material->size_label)
                            <span class="ms-1 text-xs text-zinc-500 dark:text-zinc-400">{{ \App\Support\Decimal::format($lot->size) }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">{{ $lot->quantity !== null ? \App\Support\Decimal::format($lot->quantity).' '.$lot->quantity_unit : '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:link :href="route('certificates.show', $lot->certificate_id)" wire:navigate>{{ $lot->certificate->number }}</flux:link>
                            <x-status-badge :status="$lot->certificate->status" />
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $lot->certificate->supplier->name }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        @if ($lot->expires_on)
                            <span @class(['text-red-600 dark:text-red-400 font-medium' => $lot->isExpired()])>{{ $lot->expires_on->format('Y-m-d') }}</span>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end"><flux:button size="sm" variant="ghost" icon="arrow-right" icon:variant="micro" inset="top bottom" :href="route('certificates.show', $lot->certificate_id)" wire:navigate>{{ __('ui_verbs.open') }}</flux:button></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-8 text-center">{{ __('No :lots found.', ['lots' => strtolower($lotLabel)]) }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
