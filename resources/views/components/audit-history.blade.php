<flux:card class="space-y-3">
    <div class="flex items-center justify-between">
        <flux:heading>{{ __('History') }}</flux:heading>
        <flux:text class="text-xs">{{ __('Every change is recorded and cannot be edited.') }}</flux:text>
    </div>

    @forelse ($logs as $log)
        <div wire:key="audit-{{ $log->id }}" class="border-b border-zinc-100 pb-3 text-sm last:border-0 last:pb-0 dark:border-zinc-700">
            <div class="flex flex-wrap items-center gap-x-2 text-zinc-500 dark:text-zinc-400">
                <span class="font-medium text-zinc-800 dark:text-white">{{ $log->user?->name ?? __('System') }}</span>
                <span>· {{ __(Str::headline($log->event)) }}</span>
                <span class="ms-auto whitespace-nowrap text-xs">{{ $log->created_at->format('Y-m-d H:i') }}</span>
            </div>
            @if (($diff = $changes($log)) !== [] && $log->event !== 'created')
                <dl class="mt-1 grid gap-x-3 gap-y-0.5 sm:grid-cols-[auto_1fr]">
                    @foreach ($diff as $field => [$before, $after])
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ $field }}</dt>
                        <dd class="min-w-0 break-words text-zinc-700 dark:text-zinc-300">
                            @if ($log->event !== 'deleted')
                                <span class="text-zinc-400 line-through">{{ $before }}</span> → {{ $after }}
                            @else
                                {{ $before }}
                            @endif
                        </dd>
                    @endforeach
                </dl>
            @endif
        </div>
    @empty
        <flux:text class="text-sm">{{ __('No changes recorded yet.') }}</flux:text>
    @endforelse
</flux:card>
