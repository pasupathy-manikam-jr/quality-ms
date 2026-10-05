<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" :href="route('reading-list')" :current="request()->routeIs('reading-list')" wire:navigate
                        :badge="auth()->user()->readings()->where('document_revisions.status', 'effective')->wherePivotNull('acknowledged_at')->count() ?: null">
                        {{ __('My reading list') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            @canany(['manage-certificates', 'manage-suppliers', 'manage-materials'])
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Incoming')" class="grid">
                        @can('manage-certificates')
                            <flux:sidebar.item icon="document-check" :href="route('certificates.index')" :current="request()->routeIs('certificates.*')" wire:navigate>
                                {{ __('Certificates') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="magnifying-glass" :href="route('lots.index')" :current="request()->routeIs('lots.*')" wire:navigate>
                                {{ \App\Models\Lot::label() }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-suppliers')
                            <flux:sidebar.item icon="truck" :href="route('suppliers.index')" :current="request()->routeIs('suppliers.*')" wire:navigate>
                                {{ __('Suppliers') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-materials')
                            <flux:sidebar.item icon="cube" :href="route('materials.index')" :current="request()->routeIs('materials.*')" wire:navigate>
                                {{ __('Materials') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            @canany(['manage-ncrs', 'manage-capas', 'manage-documents', 'manage-audits'])
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Improvement')" class="grid">
                        @can('manage-ncrs')
                            <flux:sidebar.item icon="exclamation-triangle" :href="route('ncrs.index')" :current="request()->routeIs('ncrs.*')" wire:navigate>
                                {{ __('Non-conformances') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-audits')
                            <flux:sidebar.item icon="magnifying-glass-circle" :href="route('audits.index')" :current="request()->routeIs('audits.*')" wire:navigate>
                                {{ __('Internal audits') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-documents')
                            <flux:sidebar.item icon="document-text" :href="route('documents.index')" :current="request()->routeIs('documents.*')" wire:navigate>
                                {{ __('Documents') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-capas')
                            <flux:sidebar.item icon="wrench-screwdriver" :href="route('capas.index')" :current="request()->routeIs('capas.*')" wire:navigate>
                                {{ __('CAPA') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            @canany(['manage-inspections', 'manage-inspection-plans', 'manage-parts', 'manage-gauges'])
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Quality')" class="grid">
                        @can('manage-inspections')
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('inspections.index')" :current="request()->routeIs('inspections.*')" wire:navigate>
                                {{ __('Inspections') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-inspection-plans')
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('inspection-plans.index')" :current="request()->routeIs('inspection-plans.*')" wire:navigate>
                                {{ __('Inspection plans') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-parts')
                            <flux:sidebar.item icon="squares-2x2" :href="route('parts.index')" :current="request()->routeIs('parts.*')" wire:navigate>
                                {{ __('Parts') }}
                            </flux:sidebar.item>
                        @endcan
                        @can('manage-gauges')
                            <flux:sidebar.item icon="scale" :href="route('gauges.index')" :current="request()->routeIs('gauges.*')" wire:navigate>
                                {{ __('Gauges') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcanany

            @can('manage-users')
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcan

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <x-language-menu />

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
