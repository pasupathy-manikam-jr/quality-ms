<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-paper text-ink antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{-- The blue signal band with a safety-yellow edge, like the header of a factory sign. --}}
        <div class="dark signal-band border-b-8 border-b-safety">
            <div class="mx-auto max-w-5xl px-6 pt-6 pb-16">
                <header class="flex flex-wrap items-center justify-between gap-4">
                    <x-app-logo />

                    @if (Route::has('login'))
                        <nav class="flex flex-wrap items-center gap-2">
                            <x-language-menu compact />
                            @auth
                                <flux:button :href="route('dashboard')" size="sm" wire:navigate class="!bg-safety !text-ink !border-safety">
                                    {{ __('Dashboard') }}
                                </flux:button>
                            @else
                                <flux:button :href="route('login')" size="sm" wire:navigate class="!bg-safety !text-ink !border-safety">
                                    {{ __('Log in') }}
                                </flux:button>
                            @endauth
                        </nav>
                    @endif
                </header>

                <div class="mt-16 max-w-3xl">
                    <h1 class="font-display text-5xl leading-[1.02] font-bold text-white sm:text-6xl">
                        {{ __('Quality management for manufacturers') }}
                    </h1>
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-white/80">
                        {{ __('Trace every lot from supplier certificate to inspection, non-conformance, corrective action and controlled document, in one open-source system.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- The trace chain is a real sequence: each step feeds the next. --}}
        <main class="mx-auto max-w-5xl px-6 py-14">
            <ol class="grid gap-x-6 gap-y-10 md:grid-cols-5">
                @foreach ([
                    [__('Material certificates'), __('Check mill test certificates, CoAs and CoCs against spec limits, lot by lot.')],
                    [__('Inspection plans'), __('Define characteristics and tolerances; readings pass or fail automatically.')],
                    [__('Non-conformance'), __('Failed checks raise NCRs with disposition and approval.')],
                    [__('CAPA (8D)'), __('Root cause, actions and effectiveness checks before closure.')],
                    [__('Document control'), __('ISO 9001 revisions, approvals and read acknowledgements.')],
                ] as $i => [$title, $text])
                    <li class="relative">
                        <div class="flex items-center gap-3">
                            <span class="font-display flex size-9 shrink-0 items-center justify-center rounded-full bg-signal text-lg font-bold text-white">{{ $i + 1 }}</span>
                            @unless ($loop->last)
                                <span class="hidden h-0.5 flex-1 bg-signal/25 md:block" aria-hidden="true"></span>
                            @endunless
                        </div>
                        <h2 class="font-display mt-4 text-xl font-bold text-ink dark:text-white">{{ $title }}</h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-12 flex max-w-2xl items-start gap-4 rounded-lg border border-zinc-200 border-s-4 border-s-safety bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon name="scale" class="mt-0.5 size-6 shrink-0 text-signal dark:text-accent-content" />
                <div>
                    <h2 class="font-display text-lg font-bold">{{ __('Calibration') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Gauge register with due dates; overdue gauges are blocked from use.') }}</p>
                </div>
            </div>
        </main>

        @fluxScripts
    </body>
</html>
