@php
    $inspection = $this->inspection;
    $readings = $inspection->readings->keyBy(fn ($r) => $r->inspection_plan_item_id.'-'.$r->sample);
    $canEdit = $inspection->isEditable() && auth()->user()->can('edit-inspections');
    $lotLabel = \App\Models\Lot::label();
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('inspections.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $inspection->number }}</flux:heading>
                    <x-status-badge :status="$inspection->status" />
                </div>
                <flux:subheading>{{ $inspection->plan->title }} · {{ $inspection->plan->subjectLabel() }}</flux:subheading>
            </div>
        </div>

        @if ($canEdit)
            <div class="flex gap-2">
                <flux:button icon="check" wire:click="save">{{ __('Save readings') }}</flux:button>
                <flux:modal.trigger name="confirm-complete">
                    <flux:button variant="primary" icon="flag">{{ __('Complete inspection') }}</flux:button>
                </flux:modal.trigger>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 self-start">
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Summary') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        __('Plan') => $inspection->plan->title.' · '.__('rev :n', ['n' => $inspection->plan->revision]),
                        __('Stage') => __(Str::headline($inspection->plan->stage)),
                        $lotLabel => $inspection->lot ? $inspection->lot->lot_number.' · '.$inspection->lot->certificate->number : null,
                        __('Reference') => $inspection->reference,
                        __('Quantity') => $inspection->quantity !== null ? \App\Support\Decimal::format($inspection->quantity) : null,
                        __('Inspection date') => $inspection->inspected_on->format('Y-m-d'),
                        __('Inspector') => $inspection->creator?->name,
                        __('Completed') => $inspection->completed_at?->format('Y-m-d H:i'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="text-zinc-800 dark:text-white">
                                @if ($label === $lotLabel && $inspection->lot)
                                    <flux:link :href="route('certificates.show', $inspection->lot->certificate_id)" wire:navigate>{{ $value }}</flux:link>
                                @else
                                    {{ $value ?: '—' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </flux:card>

            @if ($inspection->status === 'failed')
                <flux:callout variant="danger" icon="x-circle">
                    <flux:callout.heading>{{ __('Failed') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('At least one reading is outside its specification. The parts must not be released.') }}</flux:callout.text>
                </flux:callout>
            @elseif ($inspection->status === 'passed')
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.text>{{ __('Every reading is within specification.') }}</flux:callout.text>
                </flux:callout>
            @endif
        </div>

        <div class="space-y-4 lg:col-span-2">
            <flux:heading>{{ __('Readings') }}</flux:heading>
            <flux:error name="status" />

            @foreach ($inspection->plan->items as $item)
                <flux:card wire:key="item-{{ $item->id }}" class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading>{{ $item->position }}. {{ $item->characteristic }}</flux:heading>
                                @if ($item->is_critical)
                                    <flux:badge size="sm" color="red">{{ __('Critical') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="text-xs">{{ $item->specLabel() }}{{ $item->method ? ' · '.$item->method : '' }}</flux:text>
                        </div>

                        @if ($item->isNumeric())
                            <div class="w-full sm:w-64">
                                @if ($canEdit)
                                    <x-select wire:model="gauges.{{ $item->id }}" :aria-label="__('Gauge')" :placeholder="__('Gauge used')">
                                        @foreach ($this->usableGauges as $gauge)
                                            <x-select.option :value="$gauge->id">{{ $gauge->code }} · {{ $gauge->description }}</x-select.option>
                                        @endforeach
                                    </x-select>
                                    <flux:error name="gauges.{{ $item->id }}" />
                                @else
                                    @php($used = $inspection->readings->firstWhere('inspection_plan_item_id', $item->id)?->gauge)
                                    <flux:text class="text-sm">{{ __('Gauge') }}: {{ $used ? $used->code.' · '.$used->description : '—' }}</flux:text>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        @for ($sample = 1; $sample <= $item->sample_size; $sample++)
                            @php($key = $item->id.'-'.$sample)
                            @php($reading = $readings->get($key))
                            <div wire:key="reading-{{ $key }}">
                                @if ($canEdit)
                                    @if ($item->isNumeric())
                                        <flux:input wire:model="values.{{ $key }}" inputmode="decimal" :label="$item->sample_size > 1 ? __('Sample :n', ['n' => $sample]) : __('Value')" />
                                    @else
                                        <x-select wire:model="values.{{ $key }}" :label="$item->sample_size > 1 ? __('Sample :n', ['n' => $sample]) : __('Result')">
                                            <x-select.option value="">—</x-select.option>
                                            <x-select.option value="ok">{{ __('OK') }}</x-select.option>
                                            <x-select.option value="nok">{{ __('Not OK') }}</x-select.option>
                                        </x-select>
                                    @endif
                                    <flux:error name="values.{{ $key }}" />
                                @endif
                                @if ($reading)
                                    <div class="mt-1 flex items-center gap-2 text-sm">
                                        @unless ($canEdit)
                                            <span class="text-zinc-800 dark:text-white">{{ $reading->value !== null ? \App\Support\Decimal::format($reading->value).' '.$item->unit : ($reading->is_ok ? __('OK') : __('Not OK')) }}</span>
                                        @endunless
                                        <x-status-badge :status="$reading->passed ? 'pass' : 'fail'" />
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>

    @if ($canEdit)
    <x-modal.confirm name="confirm-complete" :title="__('Complete this inspection?')" :text="__('Your readings are saved first. Every reading must be entered, and the result is final.')" confirm="complete" variant="primary" icon="flag" :confirm-label="__('Complete')" />
    @endif
</section>
