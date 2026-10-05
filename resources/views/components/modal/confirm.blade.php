@props([
    'name',
    'title',
    'confirm',
    'text' => null,
    'icon' => 'exclamation-triangle',
    'confirmLabel' => null,
    'variant' => 'danger',
])

{{-- Confirmation dialog for an action that changes or deletes data. --}}
<flux:modal :name="$name" :attributes="$attributes->class('w-full max-w-md')">
    <x-modal.header :title="$title" :description="$text" :icon="$icon" :variant="$variant" />

    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif

    <div class="-mx-6 -mb-6 mt-8 flex flex-wrap items-center justify-end gap-2 rounded-b-xl border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <flux:modal.close>
            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
        </flux:modal.close>
        <flux:button :variant="$variant" wire:click="{{ $confirm }}" wire:loading.attr="disabled" wire:target="{{ $confirm }}">
            {{ $confirmLabel ?? __('Confirm') }}
        </flux:button>
    </div>
</flux:modal>
