@props([
    'name',
    'title',
    'submit',
    'description' => null,
    'icon' => null,
    'submitLabel' => null,
    'variant' => 'primary',
    'width' => 'lg',
    'busy' => null,
])

{{--
    Every form dialog: header, fields, and a footer bar with Cancel and the submit button.
    Validation is Laravel's (novalidate); errors show under each field.
    busy: extra wire targets (e.g. a file upload) that keep the submit button disabled while running.
--}}
<flux:modal :name="$name" :attributes="$attributes->class(['w-full', match ($width) { 'md' => 'max-w-md', 'xl' => 'max-w-2xl', default => 'max-w-lg' }])">
    <form wire:submit="{{ $submit }}" novalidate>
        <x-modal.header :title="$title" :description="$description" :icon="$icon" :variant="$variant" />

        <div class="mt-6 space-y-5">
            {{ $slot }}
        </div>

        <div class="-mx-6 -mb-6 mt-8 flex flex-wrap items-center justify-end gap-2 rounded-b-xl border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" :variant="$variant" wire:loading.attr="disabled" wire:target="{{ collect([$submit, $busy])->filter()->join(',') }}">
                {{ $submitLabel ?? __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
