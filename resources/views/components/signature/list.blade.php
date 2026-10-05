@props(['signatures'])

{{-- Electronic signatures on a record, oldest first. --}}
@if ($signatures->isNotEmpty())
    <div class="space-y-2">
        <flux:heading size="sm">{{ __('Signatures') }}</flux:heading>
        <ul class="space-y-1.5 text-sm">
            @foreach ($signatures as $signature)
                <li class="flex items-start gap-2" wire:key="signature-{{ $signature->id }}">
                    <flux:icon name="finger-print" variant="mini" class="mt-0.5 shrink-0 text-zinc-400" />
                    <span>
                        <span class="font-medium text-zinc-800 dark:text-white">{{ $signature->signer_name }}</span>
                        · {{ __(Str::headline($signature->meaning)) }}
                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $signature->signed_at->format('Y-m-d H:i') }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
