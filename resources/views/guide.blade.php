<x-layouts::app :title="__('User guide')">
    {{-- Rendered from resources/guide/<locale>.md, which ships with the app (not user input). --}}
    <article class="guide mx-auto w-full max-w-3xl">
        {!! $html !!}
    </article>
</x-layouts::app>
