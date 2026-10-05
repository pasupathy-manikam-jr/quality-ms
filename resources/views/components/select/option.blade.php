@props(['value' => ''])

@php($text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $slot))))

<button
    type="button"
    role="option"
    data-option
    data-value="{{ $value }}"
    data-label="{{ $text }}"
    x-show="matches($el)"
    x-on:click="pick(@js($value))"
    :aria-selected="isSelected($el)"
    class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-zinc-800 hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-hidden dark:text-white dark:hover:bg-zinc-700 dark:focus:bg-zinc-700"
>
    <flux:icon name="check" variant="micro" class="shrink-0 text-accent-content" x-bind:class="isSelected($el) ? 'visible' : 'invisible'" />
    <span class="truncate">{{ $slot }}</span>
</button>
