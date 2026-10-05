@props([
    'label' => null,
    'badge' => null,
    'description' => null,
    'placeholder' => null,
    'disabled' => false,
])

@php
    // The bound property's name, so its Laravel validation message shows under the field.
    $model = $attributes->wire('model')->value();
@endphp

{{--
    A styled select built from Alpine (bundled with Livewire) instead of the browser's native <select>.
    Bind with wire:model / wire:model.live like any input; options are <x-select.option value="…">.
    A search box appears when there are more than 8 options.
--}}
<flux:field {{ $attributes->only('class') }}>
    @if ($label)
        <flux:label :badge="$badge">{{ $label }}</flux:label>
    @endif

    <div
        x-data="{
            open: false,
            value: null,
            selected: '',
            search: '',
            count: 0,
            init() {
                this.count = this.$refs.list.querySelectorAll('[data-option]').length;
                this.$watch('value', () => this.sync());
                this.$nextTick(() => this.sync());
            },
            sync() {
                const option = [...this.$refs.list.querySelectorAll('[data-option]')].find((o) => o.dataset.value === String(this.value ?? ''));
                this.selected = option ? option.dataset.label : '';
            },
            toggle() {
                if (this.open) { return this.close(); }
                this.open = true;
                this.$nextTick(() => (this.count > 8 ? this.$refs.search.focus() : this.focusSelected()));
            },
            close() {
                this.open = false;
                this.search = '';
            },
            focusSelected() {
                const options = [...this.$refs.list.querySelectorAll('[data-option]')].filter((o) => o.offsetParent !== null);
                (options.find((o) => o.dataset.value === String(this.value ?? '')) ?? options[0])?.focus();
            },
            pick(value) {
                this.value = value;
                this.close();
                this.$refs.button.focus();
            },
            isSelected(el) {
                return el.dataset.value === String(this.value ?? '');
            },
            matches(el) {
                return el.dataset.label.toLowerCase().includes(this.search.trim().toLowerCase());
            },
        }"
        x-modelable="value"
        {{ $attributes->whereStartsWith('wire:model') }}
        x-on:keydown.escape.prevent.stop="close(); $refs.button.focus()"
        x-on:click.outside="close()"
        class="relative"
    >
        <button
            type="button"
            x-ref="button"
            x-on:click="toggle()"
            x-on:keydown.down.prevent="open ? $focus.within($refs.list).first() : toggle()"
            :aria-expanded="open"
            aria-haspopup="listbox"
            @disabled($disabled)
            {{ $attributes->only(['aria-label', 'id']) }}
            @class([
                'flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-start text-sm shadow-xs',
                'border-zinc-200 border-b-zinc-300/80 dark:border-white/10 dark:bg-white/10',
                'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-0',
                'disabled:cursor-default disabled:opacity-75 disabled:shadow-none',
                'border-red-500 dark:border-red-400' => $model && $errors->has($model),
            ])
        >
            <span x-show="selected !== ''" x-text="selected" class="truncate text-zinc-800 dark:text-white"></span>
            <span x-show="selected === ''" class="truncate text-zinc-400 dark:text-zinc-500">{{ $placeholder ?? __('Select…') }}</span>
            <flux:icon name="chevron-up-down" variant="mini" class="shrink-0 text-zinc-400" />
        </button>

        <div
            x-show="open"
            x-transition.opacity.duration.100ms
            x-cloak
            class="absolute start-0 end-0 z-50 mt-1 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black/5 dark:bg-zinc-800 dark:ring-white/10"
        >
            <div x-show="count > 8" class="border-b border-zinc-100 p-2 dark:border-zinc-700">
                <input
                    type="text"
                    x-ref="search"
                    x-model="search"
                    x-on:keydown.down.prevent="$focus.within($refs.list).first()"
                    placeholder="{{ __('Search…') }}"
                    aria-label="{{ __('Search options') }}"
                    class="w-full rounded-md border-0 bg-zinc-50 px-2.5 py-1.5 text-sm text-zinc-800 outline-hidden placeholder:text-zinc-400 focus:ring-2 focus:ring-accent dark:bg-zinc-700 dark:text-white"
                >
            </div>
            <div
                x-ref="list"
                role="listbox"
                class="max-h-60 overflow-y-auto p-1"
                x-on:keydown.down.prevent="$focus.within($refs.list).wrap().next()"
                x-on:keydown.up.prevent="$focus.within($refs.list).wrap().previous()"
            >
                {{ $slot }}
            </div>
        </div>
    </div>

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    @if ($model)
        <flux:error :name="$model" />
    @endif
</flux:field>
