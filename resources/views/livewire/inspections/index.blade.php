@php($lotLabel = \App\Models\Lot::label())

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Inspections') }}</flux:heading>
            <flux:subheading>{{ __('Inspections done against approved plans, with every reading and the gauge it was taken with.') }}</flux:subheading>
        </div>

        @can('create-inspections')
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Start inspection') }}</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-2" role="tablist">
        @php($counts = $this->statusCounts)
        @foreach (['' => __('All'), ...array_combine(\App\Models\Inspection::STATUSES, array_map(fn ($s) => __(Str::headline($s)), \App\Models\Inspection::STATUSES))] as $value => $label)
            <flux:button size="sm" role="tab" :aria-selected="$status === $value ? 'true' : 'false'"
                :variant="$status === $value ? 'primary' : 'ghost'" wire:click="$set('status', '{{ $value }}')">
                {{ $label }}
                <flux:badge size="sm" class="ms-1">{{ $value === '' ? array_sum($counts) : ($counts[$value] ?? 0) }}</flux:badge>
            </flux:button>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number, reference, plan or :lot', ['lot' => strtolower($lotLabel)])" clearable />
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

    <flux:table :paginate="$this->inspections">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortField === 'number'" :direction="$sortDirection" wire:click="sort('number')">{{ __('Number') }}</flux:table.column>
            <flux:table.column>{{ __('Plan') }}</flux:table.column>
            <flux:table.column>{{ __('Reference') }}</flux:table.column>
            <flux:table.column sortable :sorted="$sortField === 'inspected_on' || $sortField === ''" :direction="$sortDirection" wire:click="sort('inspected_on')">{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Inspector') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->inspections as $inspection)
                <flux:table.row :key="$inspection->id">
                    <flux:table.cell><flux:link :href="route('inspections.show', $inspection)" wire:navigate class="font-medium">{{ $inspection->number }}</flux:link></flux:table.cell>
                    <flux:table.cell>
                        {{ $inspection->plan->title }}
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $inspection->plan->subjectLabel() }} · {{ __(Str::headline($inspection->plan->stage)) }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $inspection->lot?->lot_number ?? $inspection->reference ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $inspection->inspected_on->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell>{{ $inspection->creator?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$inspection->status" /></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No inspections found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @can('create-inspections')
    <x-modal.form name="inspection-form" :title="__('Start inspection')" submit="save" icon="clipboard-document-check" :submit-label="__('Start')">
        <x-select wire:model.live="plan_id" :label="__('Plan')" :badge="__('Required')" :placeholder="__('Choose an approved plan')">
            @foreach ($this->plans as $plan)
                <x-select.option :value="$plan->id">{{ $plan->title }} ({{ $plan->subjectLabel() }}, {{ __('rev :n', ['n' => $plan->revision]) }})</x-select.option>
            @endforeach
        </x-select>

        @if ($plan_id !== '')
            <x-select wire:model="lot_id" :label="$lotLabel" :placeholder="__('None')"
                :badge="$this->plans->firstWhere('id', (int) $plan_id)?->stage === 'receiving' ? __('Required') : null"
                :description="__('Only :lots from verified certificates that have not expired are listed.', ['lots' => strtolower($lotLabel)])">
                <x-select.option value="">{{ __('None') }}</x-select.option>
                @foreach ($this->lots as $lot)
                    <x-select.option :value="$lot->id">{{ $lot->lot_number }} · {{ $lot->certificate->number }}</x-select.option>
                @endforeach
            </x-select>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <flux:input wire:model="reference" :label="__('Reference')" :placeholder="__('Work order, batch or serials')" />
            </div>
            <flux:input wire:model="quantity" :label="__('Quantity')" inputmode="decimal" />
        </div>

        <flux:input wire:model="inspected_on" :label="__('Inspection date')" type="date" :badge="__('Required')" />
    </x-modal.form>
    @endcan
</section>
