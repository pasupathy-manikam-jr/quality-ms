@php
    $capa = $this->capa;
    $canEdit = $capa->status !== 'closed' && auth()->user()->can('edit-capas');
    $next = $capa->nextStatus();
    $blockers = $capa->blockers();
    $actionsOpen = in_array($capa->status, ['open', 'investigating', 'implementing'], true);
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('capas.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $capa->number }}</flux:heading>
                    <x-status-badge :status="$capa->status" />
                    @if ($capa->isOverdue())
                        <x-status-badge status="overdue" />
                    @endif
                </div>
                <flux:subheading>{{ $capa->title }}</flux:subheading>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
        <flux:button icon="printer" :href="route('capas.print', $capa)" target="_blank">{{ __('8D report') }}</flux:button>
        @if ($canEdit && $next && ($next !== 'closed' || auth()->user()->can('verify-capas')))
            <flux:button variant="primary" icon="arrow-right" :wire:click="$next === 'closed' ? 'requestSignature(\'advance\')' : 'advance'" :disabled="$blockers !== []">
                {{ $next === 'closed' ? __('Close CAPA') : __('Move to :status', ['status' => __(Str::headline($next))]) }}
            </flux:button>
        @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 self-start">
            {{-- Stage progress --}}
            <flux:card class="space-y-3">
                <flux:heading>{{ __('Progress') }}</flux:heading>
                <ol class="space-y-2 text-sm">
                    @foreach (\App\Models\Capa::STATUSES as $stage)
                        @php($reached = array_search($stage, \App\Models\Capa::STATUSES, true) <= array_search($capa->status, \App\Models\Capa::STATUSES, true))
                        <li class="flex items-center gap-2">
                            <flux:icon :name="$reached ? 'check-circle' : 'ellipsis-horizontal-circle'" variant="mini" @class(['text-green-600 dark:text-green-400' => $reached, 'text-zinc-400' => ! $reached]) />
                            <span @class(['font-medium text-zinc-800 dark:text-white' => $stage === $capa->status])>{{ __(Str::headline($stage)) }}</span>
                        </li>
                    @endforeach
                </ol>
                @if ($next && $blockers !== [])
                    <flux:callout variant="warning" icon="information-circle">
                        <flux:callout.heading>{{ __('Before :status', ['status' => __(Str::headline($next))]) }}</flux:callout.heading>
                        <flux:callout.text>
                            <ul class="list-disc space-y-1 ps-4">
                                @foreach ($blockers as $blocker)
                                    <li>{{ $blocker }}</li>
                                @endforeach
                            </ul>
                        </flux:callout.text>
                    </flux:callout>
                @endif
                <flux:error name="status" />
            </flux:card>

            <x-signature.list :signatures="$capa->signatures" />

            {{-- Linked NCRs --}}
            <flux:card class="space-y-3">
                <flux:heading>{{ __('Non-conformances') }}</flux:heading>
                @forelse ($capa->ncrs as $ncr)
                    <div wire:key="ncr-{{ $ncr->id }}" class="flex items-center justify-between gap-2 text-sm">
                        <flux:link :href="route('ncrs.show', $ncr)" wire:navigate>{{ $ncr->number }}</flux:link>
                        <x-status-badge :status="$ncr->status" />
                    </div>
                @empty
                    <flux:text class="text-sm">{{ __('None linked.') }}</flux:text>
                @endforelse
                @if ($canEdit && $this->linkableNcrs->isNotEmpty())
                    <form wire:submit="linkNcr" class="flex items-end gap-2" novalidate>
                        <div class="flex-1">
                            <x-select wire:model="ncrToLink" :aria-label="__('NCR to link')" :placeholder="__('Link another NCR')">
                                @foreach ($this->linkableNcrs as $option)
                                    <x-select.option :value="$option->id">{{ $option->number }} · {{ Str::limit($option->title, 40) }}</x-select.option>
                                @endforeach
                            </x-select>
                        </div>
                        <flux:button type="submit" icon="link" :aria-label="__('Link')" />
                    </form>
                    <flux:error name="ncrToLink" />
                @endif
            </flux:card>

            {{-- Effectiveness --}}
            <flux:card class="space-y-3">
                <flux:heading>{{ __('Effectiveness check') }}</flux:heading>
                @if ($capa->effectiveness_verified_at)
                    <flux:text class="text-sm">{{ __('Verified by :name on :date.', ['name' => $capa->verifier?->name ?? '—', 'date' => $capa->effectiveness_check_on?->format('Y-m-d')]) }}</flux:text>
                    <flux:text class="whitespace-pre-line text-sm">{{ $capa->effectiveness_notes }}</flux:text>
                @elseif ($capa->status === 'verifying' && auth()->user()->can('verify-capas'))
                    <form wire:submit="verifyEffectiveness" class="space-y-3" novalidate>
                        <flux:input wire:model="effectiveness_check_on" type="date" :label="__('Checked on')" badge="*" />
                        <flux:textarea wire:model="effectiveness_notes" rows="3" :label="__('Evidence the problem has not come back')" badge="*" />
                        <x-signature.password />
                        <flux:button type="submit" variant="primary" icon="check-badge">{{ __('Verify effectiveness') }}</flux:button>
                    </form>
                @else
                    <flux:text class="text-sm">{{ __('Checked once the actions are implemented, before the CAPA closes.') }}</flux:text>
                @endif
            </flux:card>
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Details and 8D --}}
            <form wire:submit="save" class="space-y-6" novalidate>
                <flux:card class="space-y-4">
                    <flux:heading>{{ __('Details') }}</flux:heading>
                    <flux:input wire:model="title" :label="__('Title')" badge="*" :disabled="! $canEdit" />
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-select wire:model="type" :label="__('Type')" :disabled="! $canEdit">
                            @foreach (\App\Models\Capa::TYPES as $value)
                                <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                            @endforeach
                        </x-select>
                        <x-select wire:model="owner_id" :label="__('Owner')" :disabled="! $canEdit">
                            @foreach ($this->owners as $owner)
                                <x-select.option :value="$owner->id">{{ $owner->name }}</x-select.option>
                            @endforeach
                        </x-select>
                        <flux:input wire:model="due_on" type="date" :label="__('Due')" :disabled="! $canEdit" />
                    </div>
                </flux:card>

                <flux:card class="space-y-4">
                    <flux:heading>{{ __('Eight disciplines') }}</flux:heading>
                    @foreach (\App\Models\Capa::DISCIPLINES as $field => $label)
                        <flux:textarea wire:model="disciplines.{{ $field }}" wire:key="d-{{ $field }}" rows="2" :label="__($label)" :disabled="! $canEdit" />
                    @endforeach
                    @if ($canEdit)
                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                        </div>
                    @endif
                </flux:card>
            </form>

            @if ($canEdit)
                @can('edit-documents')
                    <flux:card class="space-y-3">
                        <flux:heading>{{ __('D7: change a controlled document') }}</flux:heading>
                        <flux:text class="text-sm">{{ __('Start a draft revision of the procedure or work instruction that must change so the problem cannot recur.') }}</flux:text>
                        <form wire:submit="requestDocumentChange" class="flex flex-wrap items-end gap-2" novalidate>
                            <div class="min-w-64 flex-1">
                                <x-select wire:model="documentToRevise" :aria-label="__('Document')" :placeholder="__('Choose a document')">
                                    @foreach ($this->documents as $doc)
                                        <x-select.option :value="$doc->id">{{ $doc->number }} · {{ $doc->title }}</x-select.option>
                                    @endforeach
                                </x-select>
                            </div>
                            <flux:button type="submit" icon="document-plus">{{ __('Start revision') }}</flux:button>
                        </form>
                        <flux:error name="documentToRevise" />
                        <flux:error name="revision" />
                    </flux:card>
                @endcan
            @endif

            {{-- Actions --}}
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Actions') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Done') }}</flux:table.column>
                        <flux:table.column>{{ __('Action') }}</flux:table.column>
                        <flux:table.column>{{ __('Owner') }}</flux:table.column>
                        <flux:table.column>{{ __('Due') }}</flux:table.column>
                        <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($capa->actions as $action)
                            <flux:table.row :key="$action->id">
                                <flux:table.cell>
                                    <flux:checkbox :checked="$action->done_at !== null" :disabled="! $canEdit" wire:click="toggleAction({{ $action->id }})" :aria-label="__('Done')" />
                                </flux:table.cell>
                                <flux:table.cell @class(['whitespace-normal', 'line-through text-zinc-500' => $action->done_at])>{{ $action->description }}</flux:table.cell>
                                <flux:table.cell>{{ $action->owner?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $action->due_on?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    @if ($canEdit && $actionsOpen)
                                        <flux:button variant="ghost" size="sm" icon="trash" inset="top bottom" wire:click="removeAction({{ $action->id }})" :aria-label="__('Remove')" />
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="py-6 text-center">{{ __('No actions yet.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                @if ($canEdit && $actionsOpen)
                    <form wire:submit="addAction" class="grid gap-3 sm:grid-cols-6 sm:items-end" novalidate>
                        <div class="sm:col-span-3">
                            <flux:input wire:model="actionDescription" :label="__('New action')" />
                        </div>
                        <div class="sm:col-span-1">
                            <x-select wire:model="actionOwnerId" :label="__('Owner')">
                                <x-select.option value="">—</x-select.option>
                                @foreach ($this->owners as $owner)
                                    <x-select.option :value="$owner->id">{{ $owner->name }}</x-select.option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input wire:model="actionDueOn" type="date" :label="__('Due')" />
                        </div>
                        <flux:button type="submit" icon="plus">{{ __('Add') }}</flux:button>
                    </form>
                @endif
            </flux:card>
        </div>
    </div>

    @can('verify-capas')
        <x-signature.dialog />
    @endcan

    <x-audit-history :record="$capa" />
</section>
