@php
    $ncr = $this->ncr;
    $sourceUrl = $ncr->sourceUrl();
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('ncrs.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $ncr->number }}</flux:heading>
                    <x-status-badge :status="$ncr->status" />
                    <x-status-badge :status="$ncr->severity" />
                </div>
                <flux:subheading>{{ $ncr->title }}</flux:subheading>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="printer" :href="route('ncrs.print', $ncr)" target="_blank">{{ __('Print') }}</flux:button>
            @if ($ncr->status === 'draft')
                @can('edit-ncrs')
                    <flux:button variant="primary" icon="play" wire:click="open">{{ __('Open NCR') }}</flux:button>
                @endcan
            @endif
            @if ($ncr->status === 'disposition-approved')
                @can('approve-ncrs')
                    <flux:button variant="primary" icon="check-circle" wire:click="confirmStatus('closed')">{{ __('Close NCR') }}</flux:button>
                @endcan
            @endif
            @if ($ncr->isEditable())
                @can('edit-ncrs')
                    <flux:dropdown position="bottom" align="end">
                        <flux:button icon="ellipsis-horizontal" :aria-label="__('More actions')" />
                        <flux:menu>
                            <flux:menu.item icon="pencil-square" wire:click="edit">{{ __('Edit details') }}</flux:menu.item>
                            @if ($ncr->status === 'draft')
                                <flux:menu.item icon="x-circle" variant="danger" wire:click="confirmStatus('cancelled')">{{ __('Cancel NCR') }}</flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                @endcan
            @endif
        </div>
    </div>

    <flux:error name="status" />

    <div class="grid gap-6 lg:grid-cols-3">
        <flux:card class="space-y-4 self-start">
            <flux:heading>{{ __('Summary') }}</flux:heading>
            <dl class="space-y-3 text-sm">
                @foreach ([
                    __('Source') => __(Str::headline($ncr->source)),
                    __('Part') => $ncr->part?->label(),
                    \App\Models\Lot::label() => $ncr->lot?->lot_number,
                    __('Supplier') => $ncr->supplier?->name,
                    __('Customer') => $ncr->customer,
                    __('Quantity affected') => $ncr->quantity_affected !== null ? \App\Support\Decimal::format($ncr->quantity_affected) : null,
                    __('Raised by') => ($ncr->creator?->name ?? __('System')).' · '.$ncr->created_at?->format('Y-m-d'),
                    __('Closed') => $ncr->closed_at?->format('Y-m-d'),
                ] as $label => $value)
                    @if ($value !== null && $value !== '')
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="text-zinc-800 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
                @if ($sourceUrl)
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Raised from') }}</dt>
                        <dd><flux:link :href="$sourceUrl" wire:navigate>{{ __('Open the :source record', ['source' => __($ncr->source)]) }}</flux:link></dd>
                    </div>
                @endif
            </dl>
        </flux:card>

        <div class="space-y-6 lg:col-span-2">
            <flux:card class="space-y-2">
                <flux:heading>{{ __('Description') }}</flux:heading>
                <flux:text class="whitespace-pre-line">{{ $ncr->description ?: __('Not described yet. Describe it before opening the NCR.') }}</flux:text>
                @if ($ncr->closure_notes)
                    <flux:separator class="my-3" />
                    <flux:heading size="sm">{{ $ncr->status === 'cancelled' ? __('Reason for cancelling') : __('Closure notes') }}</flux:heading>
                    <flux:text class="whitespace-pre-line">{{ $ncr->closure_notes }}</flux:text>
                @endif
            </flux:card>

            @if ($ncr->status !== 'draft' && $ncr->status !== 'cancelled')
                <flux:card class="space-y-4">
                    <flux:heading>{{ __('Disposition') }}</flux:heading>

                    @if ($ncr->status === 'open' && auth()->user()->can('edit-ncrs'))
                        <form wire:submit="saveDisposition" class="space-y-4" novalidate>
                            <x-select wire:model.live="disposition" :label="__('What happens to the affected material')" :badge="__('Required')" :placeholder="__('Choose a disposition')">
                                @foreach (\App\Models\Ncr::DISPOSITIONS as $value)
                                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                                @endforeach
                            </x-select>
                            <flux:textarea wire:model="disposition_notes" :label="__('Notes')" rows="2" :badge="$disposition === 'use-as-is' ? __('Required') : null" />
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:button type="submit">{{ __('Save disposition') }}</flux:button>
                                @can('approve-ncrs')
                                    <flux:button variant="primary" icon="check" wire:click="approveDisposition" :disabled="$ncr->disposition === null">{{ __('Approve disposition') }}</flux:button>
                                @endcan
                            </div>
                        </form>
                    @else
                        <dl class="space-y-2 text-sm">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Disposition') }}</dt>
                                <dd class="text-zinc-800 dark:text-white">{{ $ncr->disposition ? __(Str::headline($ncr->disposition)) : '—' }}</dd>
                            </div>
                            @if ($ncr->disposition_notes)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</dt>
                                    <dd class="whitespace-pre-line text-zinc-800 dark:text-white">{{ $ncr->disposition_notes }}</dd>
                                </div>
                            @endif
                            @if ($ncr->disposition_approved_at)
                                <div>
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Approved') }}</dt>
                                    <dd class="text-zinc-800 dark:text-white">{{ __(':name on :date', ['name' => $ncr->dispositionApprover?->name ?? '—', 'date' => $ncr->disposition_approved_at->format('Y-m-d')]) }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </flux:card>

                <flux:card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading>{{ __('Corrective action') }}</flux:heading>
                        @if (in_array($ncr->status, ['open', 'disposition-approved'], true))
                            @can('create-capas')
                                <flux:button size="sm" icon="plus" wire:click="startCapa">{{ __('Start CAPA') }}</flux:button>
                            @endcan
                        @endif
                    </div>
                    @forelse ($ncr->capas as $capa)
                        <div wire:key="capa-{{ $capa->id }}" class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <div>
                                <flux:link :href="route('capas.show', $capa)" wire:navigate>{{ $capa->number }}</flux:link>
                                <span class="text-zinc-500 dark:text-zinc-400">· {{ $capa->title }} · {{ $capa->owner?->name }}</span>
                            </div>
                            <x-status-badge :status="$capa->status" />
                        </div>
                    @empty
                        <flux:text class="text-sm">{{ __('No CAPA linked. Start one when the cause needs fixing, not just this material.') }}</flux:text>
                    @endforelse
                </flux:card>
            @endif
        </div>
    </div>

    @if ($ncr->isEditable() && auth()->user()->can('edit-ncrs'))
        @include('livewire.ncrs.form-modal', ['title' => __('Edit NCR')])
    @endif

    @canany(['edit-ncrs', 'approve-ncrs'])

        <x-modal.form name="confirm-ncr-status" :title="$pendingStatus === 'closed' ? __('Close this NCR?') : __('Cancel this NCR?')" submit="changeStatus" :icon="$pendingStatus === 'closed' ? 'check-circle' : 'x-circle'" :variant="$pendingStatus === 'closed' ? 'primary' : 'danger'" :submit-label="__('Confirm')" width="md">
            <flux:textarea wire:model="notes" rows="3"
                :label="$pendingStatus === 'closed' ? __('Closure notes') : __('Reason')"
                :badge="$pendingStatus === 'cancelled' ? __('Required') : null" />
            <flux:error name="status" />
        </x-modal.form>
    @endcanany
</section>
