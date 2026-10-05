@props(['compact' => false])

{{-- Language switcher: menu items inside a flux:menu, or a row of small buttons when compact. --}}
@foreach (config('app.locales') as $code => $label)
    <form method="POST" action="{{ route('locale.update') }}" @class(['w-full' => ! $compact, 'inline' => $compact])>
        @csrf
        <input type="hidden" name="locale" value="{{ $code }}">
        @if ($compact)
            <flux:button type="submit" size="sm" :variant="app()->getLocale() === $code ? 'filled' : 'ghost'" :aria-pressed="app()->getLocale() === $code ? 'true' : 'false'" lang="{{ $code }}">{{ $label }}</flux:button>
        @else
            <flux:menu.item as="button" type="submit" class="w-full cursor-pointer" lang="{{ $code }}"
                :icon="app()->getLocale() === $code ? 'check' : 'language'">{{ $label }}</flux:menu.item>
        @endif
    </form>
@endforeach
