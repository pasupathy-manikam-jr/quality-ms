@php
    $document = $this->document;
    $working = $this->working;
    $effective = $document->effectiveRevision;
@endphp

<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('documents.index')" icon="arrow-left" variant="ghost" size="sm" wire:navigate :aria-label="__('Back')" />
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl" level="1">{{ $document->number }}</flux:heading>
                    @if ($effective)
                        <flux:badge size="sm" color="green">{{ __('Rev :r effective', ['r' => $effective->revision]) }}</flux:badge>
                    @endif
                    @if ($document->isDueForReview())
                        <x-status-badge status="due" />
                    @endif
                </div>
                <flux:subheading>{{ $document->title }}</flux:subheading>
            </div>
        </div>

        @can('edit-documents')
            <div class="flex flex-wrap gap-2">
                @if ($effective && ! $working)
                    <flux:button icon="document-plus" wire:click="startRevision">{{ __('New revision') }}</flux:button>
                    <flux:button icon="check-badge" wire:click="confirmReview">{{ __('Confirm review') }}</flux:button>
                @endif
                <flux:button icon="pencil-square" wire:click="edit">{{ __('Edit details') }}</flux:button>
            </div>
        @endcan
    </div>

    <flux:error name="status" />
    <flux:error name="revision" />
    <flux:error name="review" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 self-start">
            <flux:card class="space-y-4">
                <flux:heading>{{ __('Summary') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        __('Type') => __(Str::headline($document->type)),
                        __('Owner') => $document->owner?->name,
                        __('Review interval') => trans_choice(':count month|:count months', $document->review_interval_months),
                        __('Next review') => $document->next_review_on?->format('Y-m-d'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                            <dd class="text-zinc-800 dark:text-white">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('ISO 9001 clauses') }}</dt>
                        <dd class="mt-1 flex flex-wrap gap-1">
                            @forelse ($document->clauses as $clause)
                                <flux:badge size="sm" :title="$clause->title">§{{ $clause->number }}</flux:badge>
                            @empty
                                <span class="text-zinc-800 dark:text-white">—</span>
                            @endforelse
                        </dd>
                    </div>
                </dl>
                @if ($effective)
                    <x-signature.list :signatures="$document->revisions->firstWhere('id', $effective->id)?->signatures ?? collect()" />
                @endif
            </flux:card>

            @if ($effective)
                <flux:card class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:heading>{{ __('Readers of rev :r', ['r' => $effective->revision]) }}</flux:heading>
                        @can('edit-documents')
                            <flux:button size="sm" variant="ghost" icon="user-plus" wire:click="openReaders" :aria-label="__('Choose readers')" />
                        @endcan
                    </div>
                    @forelse ($effective->readers as $reader)
                        <div wire:key="reader-{{ $reader->id }}" class="flex items-center justify-between gap-2 text-sm">
                            <span>{{ $reader->name }}</span>
                            @if ($reader->pivot->acknowledged_at)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Carbon\CarbonImmutable::parse($reader->pivot->acknowledged_at)->format('Y-m-d') }}</span>
                            @else
                                <x-status-badge status="due" />
                            @endif
                        </div>
                    @empty
                        <flux:text class="text-sm">{{ __('Nobody has been asked to read it.') }}</flux:text>
                    @endforelse
                </flux:card>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            @if ($working)
                <flux:card class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <flux:heading>{{ __('Revision :r', ['r' => $working->revision]) }}</flux:heading>
                            <x-status-badge :status="$working->status" />
                        </div>
                        @if ($working->capa)
                            <flux:text class="text-sm">{{ __('Requested by') }} <flux:link :href="route('capas.show', $working->capa)" wire:navigate>{{ $working->capa->number }}</flux:link></flux:text>
                        @endif
                    </div>

                    @if ($working->isEditable() && auth()->user()->can('edit-documents'))
                        <form wire:submit="saveDraft" class="space-y-4" novalidate>
                            <flux:textarea wire:model="change_summary" rows="3" :label="__('What changed')" :badge="__('Required to send for review')" />
                            <div>
                                <flux:input type="file" wire:model="file" :label="__('Document file')"
                                    :description="$working->file_name ? __('Current: :name. Upload again to replace it.', ['name' => $working->file_name]) : __('PDF, Word, Excel or a scan, up to 10 MB.')" />
                                <div wire:loading wire:target="file" class="mt-1 text-xs text-zinc-500">{{ __('Uploading…') }}</div>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:button type="submit" wire:loading.attr="disabled" wire:target="file">{{ __('Save draft') }}</flux:button>
                                <flux:button variant="primary" icon="paper-airplane" wire:click="submit" wire:loading.attr="disabled" wire:target="file">{{ __('Send for review') }}</flux:button>
                            </div>
                        </form>
                    @else
                        <flux:text class="whitespace-pre-line">{{ $working->change_summary ?: '—' }}</flux:text>
                        @if ($working->file_path)
                            <flux:link :href="route('document-revisions.file', $working)" icon="arrow-down-tray">{{ $working->file_name }}</flux:link>
                        @endif
                        <x-signature.list :signatures="$working->signatures" />
                        @if ($working->status === 'in-review')
                            @can('approve-documents')
                                <div class="flex flex-wrap justify-end gap-2">
                                    <flux:button wire:click="returnToDraft">{{ __('Return to draft') }}</flux:button>
                                    <flux:button variant="primary" icon="check-circle" wire:click="requestSignature('approve')">{{ __('Approve and make effective') }}</flux:button>
                                </div>
                            @endcan
                            <flux:text class="text-xs">{{ __('Written by :name. Someone else must approve it.', ['name' => $working->creator?->name ?? '—']) }}</flux:text>
                        @endif
                    @endif
                </flux:card>
            @endif

            <div class="space-y-3">
                <flux:heading>{{ __('Revision history') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Rev') }}</flux:table.column>
                        <flux:table.column>{{ __('What changed') }}</flux:table.column>
                        <flux:table.column>{{ __('Approved') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('File') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($document->revisions as $revision)
                            <flux:table.row :key="$revision->id">
                                <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $revision->revision }}</flux:table.cell>
                                <flux:table.cell class="max-w-sm whitespace-normal">{{ $revision->change_summary ?: '—' }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    @if ($revision->approved_at)
                                        {{ $revision->approved_at->format('Y-m-d') }}
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $revision->approver?->name }}</div>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell><x-status-badge :status="$revision->status" /></flux:table.cell>
                                <flux:table.cell>
                                    @if ($revision->file_path)
                                        <flux:link :href="route('document-revisions.file', $revision)" icon="arrow-down-tray">{{ __('Download') }}</flux:link>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>

    @can('edit-documents')
    <x-modal.form name="document-form" :title="__('Edit document')" submit="save" icon="document-text" width="xl">
        <flux:input wire:model="title" :label="__('Title')" badge="*" />
        <div class="grid gap-4 sm:grid-cols-3">
            <x-select wire:model="type" :label="__('Type')" badge="*">
                @foreach (\App\Models\Document::TYPES as $value)
                    <x-select.option :value="$value">{{ __(Str::headline($value)) }}</x-select.option>
                @endforeach
            </x-select>
            <x-select wire:model="owner_id" :label="__('Owner')" badge="*">
                @foreach ($this->users as $user)
                    <x-select.option :value="$user->id">{{ $user->name }}</x-select.option>
                @endforeach
            </x-select>
            <flux:input wire:model="review_interval_months" :label="__('Review every (months)')" badge="*" inputmode="numeric" />
        </div>
        <flux:checkbox.group wire:model="clauseIds" :label="__('ISO 9001 clauses this document covers')">
            <div class="grid max-h-64 gap-1 overflow-y-auto sm:grid-cols-2">
                @foreach ($this->isoClauses as $clause)
                    <flux:checkbox :value="(string) $clause->id" :label="'§'.$clause->number.' '.__($clause->title)" />
                @endforeach
            </div>
        </flux:checkbox.group>
    </x-modal.form>

    <x-modal.form name="readers-form" :title="__('Who must read it')" :description="__('They see it in their reading list and confirm once read.')" submit="saveReaders" icon="user-group" width="md">
        <flux:checkbox.group wire:model="readerIds">
            <div class="grid max-h-72 gap-1 overflow-y-auto">
                @foreach ($this->users as $user)
                    <flux:checkbox :value="(string) $user->id" :label="$user->name" />
                @endforeach
            </div>
        </flux:checkbox.group>
    </x-modal.form>
    @endcan
    @can('approve-documents')
        <x-signature.dialog />
    @endcan
</section>
