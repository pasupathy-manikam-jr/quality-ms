@php
    $audit = $this->audit;
    $canEdit = $audit->status !== 'completed' && auth()->user()->can('edit-audits');
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('audits.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $audit->number }}</flux:heading>
                    <x-status-badge :status="$audit->status" />
                </div>
                <flux:subheading>{{ $audit->title }}</flux:subheading>
            </div>
        </div>

        @if ($canEdit && $audit->status === 'planned')
            <flux:button variant="primary" icon="play" wire:click="start">{{ __('Start audit') }}</flux:button>
        @endif
    </div>

    <flux:error name="status" />

    <div class="grid gap-6 lg:grid-cols-3">
        <flux:card class="space-y-4 self-start">
            <flux:heading>{{ __('Summary') }}</flux:heading>
            <dl class="space-y-3 text-sm">
                @foreach ([
                    __('Lead auditor') => $audit->leadAuditor?->name,
                    __('Planned for') => $audit->planned_on->format('Y-m-d'),
                    __('Completed') => $audit->completed_at?->format('Y-m-d'),
                    __('Scope') => $audit->scope,
                ] as $label => $value)
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                        <dd class="whitespace-pre-line text-zinc-800 dark:text-white">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('ISO 9001 clauses') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($audit->clauses as $clause)
                            <flux:badge size="sm" :title="$clause->title">§{{ $clause->number }}</flux:badge>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
            </dl>
        </flux:card>

        <div class="space-y-6 lg:col-span-2">
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Findings') }}</flux:heading>
                @forelse ($audit->findings as $finding)
                    <div wire:key="finding-{{ $finding->id }}" class="space-y-1 border-b border-zinc-100 pb-3 last:border-0 dark:border-zinc-700">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$finding->type" />
                            @if ($finding->clause)
                                <flux:badge size="sm">§{{ $finding->clause->number }}</flux:badge>
                            @endif
                            @if ($finding->ncr)
                                <flux:link :href="route('ncrs.show', $finding->ncr)" wire:navigate class="text-sm">{{ $finding->ncr->number }}</flux:link>
                            @endif
                        </div>
                        <flux:text class="whitespace-pre-line">{{ $finding->description }}</flux:text>
                    </div>
                @empty
                    <flux:text>{{ $audit->status === 'planned' ? __('Start the audit to record findings.') : __('No findings recorded.') }}</flux:text>
                @endforelse

                @if ($canEdit && $audit->status === 'in-progress')
                    <form wire:submit="addFinding" class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-700" novalidate>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-select wire:model="type" :label="__('Type')" badge="*">
                                @foreach (\App\Models\AuditFinding::TYPES as $value)
                                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                                @endforeach
                            </x-select>
                            <x-select wire:model="iso_clause_id" :label="__('Clause')">
                                <x-select.option value="">{{ __('None') }}</x-select.option>
                                @foreach ($audit->clauses->isEmpty() ? \App\Models\IsoClause::query()->orderBy('id')->get() : $audit->clauses as $clause)
                                    <x-select.option :value="$clause->id">§{{ $clause->number }} {{ __($clause->title) }}</x-select.option>
                                @endforeach
                            </x-select>
                        </div>
                        <flux:textarea wire:model="description" :label="__('What was found, with the evidence')" rows="3" badge="*" />
                        <flux:text class="text-xs">{{ __('Nonconformities raise an NCR straight away. Findings cannot be changed afterwards.') }}</flux:text>
                        <div class="flex justify-end">
                            <flux:button type="submit" icon="plus">{{ __('Add finding') }}</flux:button>
                        </div>
                    </form>
                @endif
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading>{{ __('Audit summary') }}</flux:heading>
                @if ($canEdit && $audit->status === 'in-progress')
                    <form wire:submit="saveSummary" class="space-y-3" novalidate>
                        <flux:textarea wire:model="summary" rows="4" :badge="__('Required to complete')" :aria-label="__('Audit summary')" />
                        <div class="flex justify-end gap-2">
                            <flux:button type="submit">{{ __('Save') }}</flux:button>
                            <flux:button variant="primary" icon="flag" wire:click="complete">{{ __('Complete audit') }}</flux:button>
                        </div>
                    </form>
                @else
                    <flux:text class="whitespace-pre-line">{{ $audit->summary ?: '—' }}</flux:text>
                @endif
            </flux:card>
        </div>
    </div>
</section>
