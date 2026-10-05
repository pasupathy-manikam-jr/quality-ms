<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="mx-auto flex min-h-svh max-w-5xl flex-col px-6 py-6">
            <header class="flex items-center justify-between">
                <x-app-logo />

                @if (Route::has('login'))
                    <nav class="flex flex-wrap items-center gap-2">
                        <x-language-menu compact />
                        @auth
                            <flux:button :href="route('dashboard')" variant="primary" size="sm" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:button>
                        @else
                            <flux:button :href="route('login')" variant="ghost" size="sm" wire:navigate>
                                {{ __('Log in') }}
                            </flux:button>
                            @if (Route::has('register'))
                                <flux:button :href="route('register')" variant="primary" size="sm" wire:navigate>
                                    {{ __('Register') }}
                                </flux:button>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="flex flex-1 flex-col justify-center gap-10 py-16">
                <div class="max-w-2xl">
                    <flux:heading size="xl" level="1">{{ __('Quality management for manufacturers') }}</flux:heading>
                    <flux:text class="mt-3 text-base">
                        {{ __('Trace every lot from supplier certificate to inspection, non-conformance, corrective action and controlled document, in one open-source system.') }}
                    </flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['document-check', __('Material certificates'), __('Check mill test certificates, CoAs and CoCs against spec limits, lot by lot.')],
                        ['clipboard-document-list', __('Inspection plans'), __('Define characteristics and tolerances; readings pass or fail automatically.')],
                        ['exclamation-triangle', __('Non-conformance'), __('Failed checks raise NCRs with disposition and approval.')],
                        ['wrench-screwdriver', __('CAPA (8D)'), __('Root cause, actions and effectiveness checks before closure.')],
                        ['scale', __('Calibration'), __('Gauge register with due dates; overdue gauges are blocked from use.')],
                        ['folder-open', __('Document control'), __('ISO 9001 revisions, approvals and read acknowledgements.')],
                    ] as [$icon, $title, $text])
                        <flux:card class="space-y-2">
                            <flux:icon :name="$icon" class="size-5 text-zinc-500 dark:text-zinc-400" />
                            <flux:heading>{{ $title }}</flux:heading>
                            <flux:text>{{ $text }}</flux:text>
                        </flux:card>
                    @endforeach
                </div>
            </main>
        </div>

        @fluxScripts
    </body>
</html>
