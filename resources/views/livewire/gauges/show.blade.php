@php
    $gauge = $this->gauge;
    $state = $gauge->state();
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('gauges.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $gauge->code }}</flux:heading>
                    <x-status-badge :status="$state" />
                </div>
                <flux:subheading>{{ $gauge->description }}</flux:subheading>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($gauge->status !== 'retired')
                @can('calibrate-gauges')
                    <flux:button variant="primary" icon="wrench-screwdriver" wire:click="openCalibration">{{ __('Record calibration') }}</flux:button>
                @endcan
            @endif
            @can('edit-gauges')
                @if (\App\Models\Gauge::TRANSITIONS[$gauge->status] ?? [])
                    <flux:dropdown position="bottom" align="end">
                        <flux:button icon="ellipsis-horizontal" :aria-label="__('More actions')" />
                        <flux:menu>
                            @foreach (\App\Models\Gauge::TRANSITIONS[$gauge->status] as $next)
                                <flux:menu.item :icon="$next === 'retired' ? 'archive-box' : 'no-symbol'" :variant="$next === 'retired' ? 'danger' : null"
                                    wire:click="confirmStatus('{{ $next }}')">
                                    {{ $next === 'retired' ? __('Retire gauge') : __('Take out of service') }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 self-start">
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Summary') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        __('Type') => $gauge->type,
                        __('Range') => $gauge->measuring_range,
                        __('Resolution') => $gauge->resolution,
                        __('Location') => $gauge->location,
                        __('Owner') => $gauge->owner?->name,
                        __('Interval') => trans_choice(':count day|:count days', $gauge->interval_days),
                        __('Next due') => $gauge->next_due_on?->format('Y-m-d') ?? __('Never calibrated'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="text-zinc-800 dark:text-white">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </flux:card>

            @php
                [$variant, $icon, $message] = match ($state) {
                    'calibrated' => ['success', 'check-circle', __('In calibration. It can be used for inspection.')],
                    'due' => ['warning', 'clock', __('Due for calibration soon. It can still be used until :date.', ['date' => $gauge->next_due_on?->format('Y-m-d')])],
                    'overdue' => ['danger', 'exclamation-triangle', $gauge->next_due_on ? __('Overdue since :date. It cannot be used until it is calibrated.', ['date' => $gauge->next_due_on->format('Y-m-d')]) : __('Never calibrated. It cannot be used until it is.')],
                    'out-of-service' => ['danger', 'no-symbol', __('Out of service. It can only return to use by passing a calibration.')],
                    default => ['secondary', 'archive-box', __('Retired. It can no longer be used or calibrated.')],
                };
            @endphp
            <flux:callout :variant="$variant" :icon="$icon">
                <flux:callout.text>{{ $message }}</flux:callout.text>
            </flux:callout>
        </div>

        <div class="space-y-6 lg:col-span-2">
            @if ($this->suspectInspections->isNotEmpty())
                <div class="space-y-3">
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        <flux:callout.heading>{{ __('Suspect inspections') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('This gauge failed its latest calibration. These inspections used it since its last good calibration, so their results may be wrong. Review them.') }}</flux:callout.text>
                    </flux:callout>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Inspection') }}</flux:table.column>
                            <flux:table.column>{{ __('Plan') }}</flux:table.column>
                            <flux:table.column>{{ __('Reference') }}</flux:table.column>
                            <flux:table.column>{{ __('Date') }}</flux:table.column>
                            <flux:table.column>{{ __('Result') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->suspectInspections as $inspection)
                                <flux:table.row :key="'suspect-'.$inspection->id">
                                    <flux:table.cell><flux:link :href="route('inspections.show', $inspection)" wire:navigate>{{ $inspection->number }}</flux:link></flux:table.cell>
                                    <flux:table.cell>{{ $inspection->plan->subjectLabel() }}</flux:table.cell>
                                    <flux:table.cell>{{ $inspection->lot?->lot_number ?? $inspection->reference ?? '—' }}</flux:table.cell>
                                    <flux:table.cell class="whitespace-nowrap">{{ $inspection->inspected_on->format('Y-m-d') }}</flux:table.cell>
                                    <flux:table.cell><x-status-badge :status="$inspection->status" /></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif

            <div class="space-y-3">
            <flux:heading>{{ __('Calibration history') }}</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('By') }}</flux:table.column>
                    <flux:table.column>{{ __('Result') }}</flux:table.column>
                    <flux:table.column>{{ __('Findings') }}</flux:table.column>
                    <flux:table.column>{{ __('Next due') }}</flux:table.column>
                    <flux:table.column>{{ __('Certificate') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($gauge->calibrations as $calibration)
                        <flux:table.row :key="$calibration->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $calibration->performed_on->format('Y-m-d') }}</flux:table.cell>
                            <flux:table.cell>
                                {{ $calibration->performed_by }}
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Recorded by :name', ['name' => $calibration->creator?->name ?? '—']) }}</div>
                            </flux:table.cell>
                            <flux:table.cell><x-status-badge :status="$calibration->result" /></flux:table.cell>
                            <flux:table.cell class="whitespace-normal text-sm">
                                @if ($calibration->as_found)
                                    <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('As found') }}:</span> {{ $calibration->as_found }}</div>
                                @endif
                                @if ($calibration->as_left)
                                    <div><span class="text-zinc-500 dark:text-zinc-400">{{ __('As left') }}:</span> {{ $calibration->as_left }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $calibration->next_due_on?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($calibration->file_path)
                                    <flux:link :href="route('calibrations.file', $calibration)" icon="arrow-down-tray">{{ __('Download') }}</flux:link>
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No calibrations recorded yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            <flux:text class="text-xs">{{ __('Calibration records cannot be changed. To correct one, record a new calibration.') }}</flux:text>
        </div>
        </div>
    </div>

    @can('calibrate-gauges')
    <x-modal.form name="calibration-form" :title="__('Record calibration')" submit="saveCalibration" icon="wrench-screwdriver" busy="file">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="performed_on" :label="__('Calibrated on')" type="date" badge="*" />
            <x-select wire:model.live="result" :label="__('Result')" badge="*">
                <x-select.option value="pass">{{ __('Pass') }}</x-select.option>
                <x-select.option value="adjusted">{{ __('Pass after adjustment') }}</x-select.option>
                <x-select.option value="fail">{{ __('Fail') }}</x-select.option>
            </x-select>
        </div>

        <flux:input wire:model="performed_by" :label="__('Calibrated by')" badge="*" :placeholder="__('Your name or the external lab')" />

        <flux:textarea wire:model="as_found" :label="__('As found')" rows="2" :badge="in_array($result, ['adjusted', 'fail'], true) ? '*' : null" />
        @if ($result === 'adjusted')
            <flux:textarea wire:model="as_left" :label="__('As left')" rows="2" badge="*" />
        @endif

        @if ($result === 'fail')
            <flux:callout variant="warning" icon="exclamation-triangle" :text="__('The gauge will be taken out of service.')" />
        @endif

        <div>
            <flux:input type="file" wire:model="file" :label="__('Calibration certificate')" accept=".pdf,.jpg,.jpeg,.png" :description="__('PDF or scan, up to 10 MB.')" />
            <div wire:loading wire:target="file" class="mt-1 text-xs text-zinc-500">{{ __('Uploading…') }}</div>
        </div>
    </x-modal.form>
    @endcan

    @can('edit-gauges')
    <x-modal.confirm name="confirm-gauge-status" :title="$pendingStatus === 'retired' ? __('Retire this gauge?') : __('Take this gauge out of service?')" :text="$pendingStatus === 'retired' ? __('It can no longer be used or calibrated. This cannot be undone.') : __('It cannot be used until it passes a calibration.')" confirm="changeStatus" icon="no-symbol" :confirm-label="__('Confirm')" />
    @endcan

    <x-audit-history :record="$gauge" />
</section>
