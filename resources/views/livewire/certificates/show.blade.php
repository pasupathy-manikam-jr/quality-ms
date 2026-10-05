@php
    $certificate = $this->certificate;
    $issues = $certificate->isEditable() ? $certificate->issues() : [];
    $lotLabel = \App\Models\Lot::label();
    $canEdit = $certificate->isEditable() && auth()->user()->can('edit-certificates');
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('certificates.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $certificate->number }}</flux:heading>
                    <x-status-badge :status="$certificate->status" />
                </div>
                <flux:subheading>{{ $certificate->supplier->name }} · {{ $certificate->typeLabel() }}</flux:subheading>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($certificate->isEditable())
                @can('verify-certificates')
                    <flux:button variant="danger" icon="x-circle" wire:click="openReject">{{ __('Reject') }}</flux:button>
                    <flux:button variant="primary" icon="check-circle" wire:click="verify" :disabled="$issues !== []">{{ __('Verify') }}</flux:button>
                @endcan
            @endif
            @if ($canEdit || ($certificate->isEditable() && auth()->user()->can('delete-certificates')))
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="ellipsis-horizontal" :aria-label="__('More actions')" />
                    <flux:menu>
                        @if ($canEdit)
                            <flux:menu.item icon="pencil-square" wire:click="edit">{{ __('Edit details') }}</flux:menu.item>
                        @endif
                        @can('delete-certificates')
                            <flux:modal.trigger name="confirm-certificate-delete">
                                <flux:menu.item icon="trash" variant="danger">{{ __('Delete certificate') }}</flux:menu.item>
                            </flux:modal.trigger>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 self-start">
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Summary') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        __('Supplier') => $certificate->supplier->name.($certificate->supplier->is_approved ? '' : ' ('.__('not approved').')'),
                        __('Type') => $certificate->typeLabel(),
                        __('Issued on') => $certificate->issued_on->format('Y-m-d'),
                        __('Purchase order') => $certificate->po_number ?: '—',
                        __('Third-party inspector') => $certificate->type === 'en10204-3.2' ? ($certificate->third_party_inspector ?: '—') : null,
                        __('Added by') => $certificate->creator?->name,
                    ] as $label => $value)
                        @if ($value !== null)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                                <dd class="text-zinc-800 dark:text-white">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('File') }}</dt>
                        <dd>
                            @if ($certificate->file_path)
                                <flux:link :href="route('certificates.file', $certificate)" icon="arrow-down-tray">{{ $certificate->file_name }}</flux:link>
                                <div class="mt-1 break-all font-mono text-xs text-zinc-500 dark:text-zinc-400" title="{{ __('SHA-256 of the stored file') }}">{{ $certificate->file_sha256 }}</div>
                            @else
                                <span class="text-zinc-800 dark:text-white">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </flux:card>

            @if ($certificate->isEditable())
                <flux:callout :variant="$issues === [] ? 'success' : 'warning'" :icon="$issues === [] ? 'check-circle' : 'exclamation-triangle'">
                    <flux:callout.heading>{{ $issues === [] ? __('Ready to verify') : __('Not ready to verify') }}</flux:callout.heading>
                    <flux:callout.text>
                        @if ($issues === [])
                            {{ __('Every result is within its specification limit.') }}
                        @else
                            <ul class="list-disc space-y-1 ps-4">
                                @foreach ($issues as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout :variant="$certificate->status === 'verified' ? 'success' : 'danger'" :icon="$certificate->status === 'verified' ? 'check-circle' : 'x-circle'">
                    <flux:callout.heading>
                        {{ $certificate->status === 'verified' ? __('Verified') : __('Rejected') }}
                        {{ __('by :name on :date', ['name' => $certificate->decider?->name ?? '—', 'date' => $certificate->decided_at?->format('Y-m-d H:i')]) }}
                    </flux:callout.heading>
                    @if ($certificate->rejection_reason)
                        <flux:callout.text>{{ $certificate->rejection_reason }}</flux:callout.text>
                    @endif
                </flux:callout>
            @endif
        </div>

        <div class="space-y-4 lg:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading>{{ $lotLabel }}</flux:heading>
                @if ($canEdit)
                    <flux:button size="sm" icon="plus" wire:click="addLot">{{ __('Add :lot', ['lot' => strtolower($lotLabel)]) }}</flux:button>
                @endif
            </div>

            @forelse ($certificate->lots as $lot)
                <flux:card wire:key="lot-{{ $lot->id }}" class="space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading>{{ $lot->lot_number }}</flux:heading>
                                @if ($lot->isExpired())
                                    <flux:badge size="sm" color="red">{{ __('Expired') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="text-xs">
                                {{ $lot->material->code }} · {{ $lot->material->name }}
                                @if ($lot->size !== null && $lot->material->size_label)
                                    · {{ $lot->material->size_label }} {{ \App\Support\Decimal::format($lot->size) }}
                                @endif
                                @if ($lot->quantity !== null)
                                    · {{ \App\Support\Decimal::format($lot->quantity) }} {{ $lot->quantity_unit }}
                                @endif
                                @if ($lot->expires_on)
                                    · {{ __('Expires :date', ['date' => $lot->expires_on->format('Y-m-d')]) }}
                                @endif
                            </flux:text>
                        </div>

                        @if ($canEdit)
                            <div class="flex gap-1">
                                @if ($certificate->reportsResults())
                                    <flux:button size="sm" icon="beaker" wire:click="enterResults({{ $lot->id }})">{{ __('Enter results') }}</flux:button>
                                @endif
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editLot({{ $lot->id }})" :aria-label="__('Edit')" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDeleteLot({{ $lot->id }})" :aria-label="__('Remove')" />
                            </div>
                        @endif
                    </div>

                    @if ($certificate->reportsResults())
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Property') }}</flux:table.column>
                                <flux:table.column align="end">{{ __('Result') }}</flux:table.column>
                                <flux:table.column>{{ __('Specification') }}</flux:table.column>
                                <flux:table.column>{{ __('Outcome') }}</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @forelse ($lot->checks() as $check)
                                    <flux:table.row :key="$lot->id.'-'.$check['property']">
                                        <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $check['property'] }}</flux:table.cell>
                                        <flux:table.cell align="end" @class(['font-semibold text-red-600 dark:text-red-400' => $check['outcome'] === 'fail'])>
                                            {{ $check['value'] ?? '—' }}
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $check['range'] ? $check['range'].' '.$check['unit'] : '—' }}</flux:table.cell>
                                        <flux:table.cell><x-status-badge :status="$check['outcome']" /></flux:table.cell>
                                    </flux:table.row>
                                @empty
                                    <flux:table.row>
                                        <flux:table.cell colspan="4" class="py-6 text-center">
                                            {{ __(':material has no specification limits yet.', ['material' => $lot->material->code]) }}
                                            <flux:link :href="route('materials.show', $lot->material)" wire:navigate>{{ __('Add limits') }}</flux:link>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforelse
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <flux:text class="text-sm">{{ __('This certificate type declares conformity without test results.') }}</flux:text>
                    @endif
                </flux:card>
            @empty
                <flux:card class="py-8 text-center">
                    <flux:text>{{ __('No :lots on this certificate yet.', ['lots' => strtolower($lotLabel)]) }}</flux:text>
                </flux:card>
            @endforelse
        </div>
    </div>

    @if ($canEdit)
    @include('livewire.certificates.form-modal', ['title' => __('Edit certificate')])

    <x-modal.form name="lot-form" :title="$lotId ? __('Edit :lot', ['lot' => strtolower($lotLabel)]) : __('Add :lot', ['lot' => strtolower($lotLabel)])" submit="saveLot" icon="archive-box">
        <x-select wire:model.live="material_id" :label="__('Material')" :badge="__('Required')" :placeholder="__('Choose a material')">
            @foreach ($this->materials as $material)
                <x-select.option :value="$material->id">{{ $material->code }} · {{ $material->name }}</x-select.option>
            @endforeach
        </x-select>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="lot_number" :label="$lotLabel" :badge="__('Required')" />
            @if ($this->selectedMaterial()?->size_label)
                <flux:input wire:model="size" :label="$this->selectedMaterial()->size_label" inputmode="decimal"
                    :description="__('Limits that depend on size use this.')" />
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="quantity" :label="__('Quantity')" inputmode="decimal" />
            <flux:input wire:model="quantity_unit" :label="__('Unit')" :placeholder="__('e.g. kg, pcs')" />
            <flux:input wire:model="expires_on" :label="__('Expires on')" type="date" />
        </div>
    </x-modal.form>

    <x-modal.form name="results-form" :title="__('Enter results')" :description="__('Copy each value from the certificate. Leave a field blank if it is not reported.')" submit="saveResults" icon="beaker" :submit-label="__('Save results')">
        <div class="space-y-3">
            @forelse ($this->resultProperties() as $i => $row)
                <flux:input wire:model="resultValues.{{ $i }}" wire:key="result-{{ $resultsLotId }}-{{ $i }}" inputmode="decimal"
                    :label="$row['property'].($row['unit'] ? ' ('.$row['unit'].')' : '')"
                    :description="$row['range'] ? __('Specification: :range', ['range' => $row['range']]) : __('No limit at this size')" />
            @empty
                <flux:text>{{ __('This material has no specification limits yet, so there is nothing to enter.') }}</flux:text>
            @endforelse
        </div>
    </x-modal.form>

    @endif

    @can('verify-certificates')
    <x-modal.form name="reject-form" :title="__('Reject certificate')" :description="__('The :lots on it must not be used. This cannot be undone.', ['lots' => strtolower($lotLabel)])" submit="reject" icon="x-circle" variant="danger" :submit-label="__('Reject')">
        <flux:textarea wire:model="reason" :label="__('Reason')" :badge="__('Required')" rows="3" />
    </x-modal.form>

    @endcan

    @if ($canEdit)
    <x-modal.confirm name="confirm-lot-delete" :title="__('Remove this :lot?', ['lot' => strtolower($lotLabel)])" :text="__('Its results are removed with it.')" confirm="deleteLot" icon="trash" :confirm-label="__('Remove')" />

    @endif

    @can('delete-certificates')
    <x-modal.confirm name="confirm-certificate-delete" :title="__('Delete this certificate?')" :text="__('Its :lots, results and file are deleted too. Only certificates still waiting for a decision can be deleted.', ['lots' => strtolower($lotLabel)])" confirm="delete" icon="trash" :confirm-label="__('Delete')" />
    @endcan
</section>
