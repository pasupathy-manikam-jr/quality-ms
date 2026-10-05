@props(['title', 'description' => null, 'icon' => null, 'variant' => 'primary'])

{{-- Modal title block: a tinted icon, the title and an optional one-line explanation. --}}
<div class="flex items-start gap-4 pe-8">
    @if ($icon)
        <div @class([
            'flex size-10 shrink-0 items-center justify-center rounded-full',
            'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400' => $variant === 'danger',
            'bg-accent/10 text-accent-content dark:bg-accent/20' => $variant !== 'danger',
        ])>
            <flux:icon :name="$icon" class="size-5" />
        </div>
    @endif
    <div class="min-w-0 space-y-1 pt-0.5">
        <flux:heading size="lg">{{ $title }}</flux:heading>
        @if ($description)
            <flux:text>{{ $description }}</flux:text>
        @endif
    </div>
</div>
