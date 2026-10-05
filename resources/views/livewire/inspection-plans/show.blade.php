@php
    $plan = $this->plan;
    $canEdit = $plan->isEditable() && auth()->user()->can('edit-inspection-plans');
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('inspection-plans.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $plan->title }}</flux:heading>
                    <x-status-badge :status="$plan->status" />
                </div>
                <flux:subheading>{{ $plan->subjectLabel() }} · {{ __(Str::headline($plan->stage)) }} · {{ __('Revision :n', ['n' => $plan->revision]) }}</flux:subheading>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($plan->status === 'draft')
                @can('approve-inspection-plans')
                    <flux:button variant="primary" icon="check-circle" wire:click="requestSignature('approve')" :disabled="$plan->items->isEmpty()">{{ __('Approve') }}</flux:button>
                @endcan
            @else
                @can('create-inspection-plans')
                    <flux:button icon="document-duplicate" wire:click="newRevision">{{ __('New revision') }}</flux:button>
                @endcan
                @if ($plan->status === 'approved')
                    @can('approve-inspection-plans')
                        <flux:modal.trigger name="confirm-obsolete">
                            <flux:button variant="danger" icon="archive-box">{{ __('Make obsolete') }}</flux:button>
                        </flux:modal.trigger>
                    @endcan
                @endif
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 self-start">
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Summary') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        __('Part or material') => $plan->part ? $plan->part->label().' · '.$plan->part->name : $plan->material?->code.' · '.$plan->material?->name,
                        __('Stage') => __(Str::headline($plan->stage)),
                        __('Created by') => $plan->creator?->name,
                        __('Approved') => $plan->approved_at ? __(':name on :date', ['name' => $plan->approver?->name ?? '—', 'date' => $plan->approved_at->format('Y-m-d')]) : null,
                        __('Inspections') => (string) $plan->inspections_count,
                    ] as $label => $value)
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="text-zinc-800 dark:text-white">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <x-signature.list :signatures="$plan->signatures" />
            </flux:card>

            @if ($this->revisions->isNotEmpty())
                <flux:card class="space-y-3">
                    <flux:heading>{{ __('Other revisions') }}</flux:heading>
                    <ul class="space-y-2 text-sm">
                        @foreach ($this->revisions as $revision)
                            <li wire:key="rev-{{ $revision->id }}" class="flex items-center justify-between gap-2">
                                <flux:link :href="route('inspection-plans.show', $revision)" wire:navigate>{{ __('Revision :n', ['n' => $revision->revision]) }}</flux:link>
                                <x-status-badge :status="$revision->status" />
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif

            @if (! $plan->isEditable())
                <flux:callout icon="lock-closed" variant="secondary">
                    <flux:callout.text>{{ __('This revision is locked so past inspections keep the exact plan they were done against. Use New revision to change it.') }}</flux:callout.text>
                </flux:callout>
            @endif
        </div>

        <div class="space-y-3 lg:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading>{{ __('Characteristics') }}</flux:heading>
                @if ($canEdit)
                    <flux:button size="sm" icon="plus" wire:click="create">{{ __('Add characteristic') }}</flux:button>
                @endif
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>#</flux:table.column>
                    <flux:table.column>{{ __('Characteristic') }}</flux:table.column>
                    <flux:table.column>{{ __('Specification') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Samples') }}</flux:table.column>
                    <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($plan->items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>{{ $item->position }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium text-zinc-800 dark:text-white">{{ $item->characteristic }}</span>
                                @if ($item->is_critical)
                                    <flux:badge size="sm" color="red" class="ms-1">{{ __('Critical') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->specLabel() }}</flux:table.cell>
                            <flux:table.cell>{{ $item->method ?: '—' }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $item->sample_size }}</flux:table.cell>
                            <flux:table.cell align="end">
                                @if ($canEdit)
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" inset="top bottom" wire:click="edit({{ $item->id }})" :aria-label="__('Edit')" />
                                    <flux:button variant="ghost" size="sm" icon="trash" inset="top bottom" wire:click="confirmDelete({{ $item->id }})" :aria-label="__('Remove')" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-8 text-center">{{ __('No characteristics yet. A plan needs at least one before it can be approved.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    @if ($canEdit)
    <x-modal.form name="item-form" :title="$editingId ? __('Edit characteristic') : __('Add characteristic')" submit="save" icon="list-bullet">
        <flux:input wire:model="characteristic" :label="__('Characteristic')" badge="*" :placeholder="__('e.g. Hole diameter Ø10')" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-select wire:model.live="kind" :label="__('Type')" badge="*">
                <x-select.option value="numeric">{{ __('Measured value') }}</x-select.option>
                <x-select.option value="attribute">{{ __('OK / not OK') }}</x-select.option>
            </x-select>
            <flux:input wire:model="method" :label="__('Method')" :placeholder="__('e.g. Caliper, Visual')" />
        </div>

        @if ($kind === 'numeric')
            <div class="grid gap-4 sm:grid-cols-4">
                <flux:input wire:model="nominal" :label="__('Nominal')" inputmode="decimal" />
                <flux:input wire:model="min" :label="__('Min')" inputmode="decimal" />
                <flux:input wire:model="max" :label="__('Max')" inputmode="decimal" />
                <flux:input wire:model="unit" :label="__('Unit')" :placeholder="__('mm')" />
            </div>
            <flux:text class="text-xs">{{ __('Enter the limits themselves (e.g. 9.95 and 10.05 for 10 ± 0.05). Values on a limit pass.') }}</flux:text>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="sample_size" :label="__('Samples per inspection')" badge="*" inputmode="numeric" />
            <div class="pt-7">
                <flux:switch wire:model="is_critical" :label="__('Critical characteristic')" />
            </div>
        </div>
    </x-modal.form>

    <x-modal.confirm name="confirm-item-delete" :title="__('Remove this characteristic?')" confirm="delete" icon="trash" :confirm-label="__('Remove')" />
    @endif

    @can('approve-inspection-plans')
    <x-modal.confirm name="confirm-obsolete" :title="__('Make this plan obsolete?')" :text="__('No new inspections can be started against it. Past inspections are kept.')" confirm="makeObsolete" icon="archive-box-x-mark" :confirm-label="__('Make obsolete')" />
    @endcan
    @can('approve-inspection-plans')
        <x-signature.dialog />
    @endcan

    <x-audit-history :record="$plan" />
</section>
