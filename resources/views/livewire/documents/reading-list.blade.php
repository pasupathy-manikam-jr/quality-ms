<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('My reading list') }}</flux:heading>
        <flux:subheading>{{ __('Documents you have been asked to read. Confirm each once you have read and understood it.') }}</flux:subheading>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Document') }}</flux:table.column>
            <flux:table.column>{{ __('Revision') }}</flux:table.column>
            <flux:table.column>{{ __('Effective since') }}</flux:table.column>
            <flux:table.column align="end"><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->pending as $revision)
                <flux:table.row :key="$revision->id">
                    <flux:table.cell>
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $revision->document->number }}</span> · {{ $revision->document->title }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $revision->revision }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $revision->approved_at?->format('Y-m-d') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            @if ($revision->file_path)
                                <flux:button size="sm" icon="arrow-down-tray" :href="route('document-revisions.file', $revision)">{{ __('ui_verbs.open') }}</flux:button>
                            @endif
                            <flux:button size="sm" variant="primary" icon="check" wire:click="acknowledge({{ $revision->id }})">{{ __('I have read it') }}</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="py-8 text-center">{{ __('Nothing to read. You are up to date.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($this->done->isNotEmpty())
        <div class="space-y-2">
            <flux:heading>{{ __('Recently read') }}</flux:heading>
            <ul class="space-y-1 text-sm">
                @foreach ($this->done as $revision)
                    <li wire:key="done-{{ $revision->id }}" class="text-zinc-600 dark:text-zinc-300">
                        {{ $revision->document->number }} {{ __('rev :r', ['r' => $revision->revision]) }} · {{ \Carbon\CarbonImmutable::parse($revision->pivot->acknowledged_at)->format('Y-m-d') }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
