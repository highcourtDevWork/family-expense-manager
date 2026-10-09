<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- viewport-fit=cover exposes the safe-area insets; interactive-widget keeps
             the layout from jumping when the on-screen keyboard opens. --}}
        <meta name="viewport"
            content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
        <meta name="theme-color" content="#059669">
        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-gray-50 font-sans text-gray-900 antialiased">
        @php
            // route name => [label, icon, active-pattern] — desktop sidebar.
            $navLinks = [
                'dashboard' => ['Dashboard', 'dashboard', 'dashboard'],
                'savings-rate.index' => ['Savings Rate', 'savings-rate', 'savings-rate.*'],
                'expenses.index' => ['Expenses', 'expenses', 'expenses.*'],
                'incomes.index' => ['Income', 'income', 'incomes.*'],
                'loans.index' => ['Loans', 'loans', 'loans.*'],
                'debts.index' => ['Debts', 'debts', 'debts.*'],
                'savings.index' => ['Savings', 'savings', 'savings.*'],
                'family.index' => ['Family', 'family', 'family.*'],
                'reports.index' => ['Reports', 'reports', 'reports.*'],
                'imports.create' => ['Import', 'import', 'imports.*'],
                'settings.index' => ['Settings', 'settings', 'settings.*'],
            ];

            // Bachelor Split accounts get their shared-expense destinations,
            // inserted right after Dashboard.
            if (auth()->user()?->currentFamily()?->isBachelor()) {
                $bachelorLinks = [
                    'bachelor.splits.index' => ['Shared Expenses', 'expenses', 'bachelor.splits.*'],
                    'bachelor.members.index' => ['Roommates', 'family', 'bachelor.members.*'],
                ];
                $navLinks = array_slice($navLinks, 0, 1, true) + $bachelorLinks + array_slice($navLinks, 1, null, true);
            }

            $unread = auth()->user()->unreadNotifications()->count();
        @endphp

        <div class="min-h-[100dvh] lg:flex">
            {{-- Desktop sidebar (hidden on mobile/tablet, which use the bottom tab bar) --}}
            <aside class="hidden w-64 flex-col border-r border-gray-200 bg-white lg:flex">
                <div class="flex h-16 items-center border-b border-gray-200 px-5">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="text-base" />
                    </a>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                    @foreach ($navLinks as $route => [$label, $icon, $pattern])
                        <x-nav-link :href="route($route)" :icon="$icon" :active="request()->routeIs($pattern)">
                            {{ $label }}
                        </x-nav-link>
                    @endforeach
                </nav>

                <div class="border-t border-gray-200 p-4">
                    <a href="{{ route('notifications.index') }}"
                        class="mb-1 flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        <span class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                            </svg>
                            Notifications
                        </span>
                        @if ($unread > 0)
                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-semibold text-white">{{ $unread }}</span>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-gray-400">Settings</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7M13 16v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1" />
                            </svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main column --}}
            <div class="flex min-w-0 flex-1 flex-col">
                {{-- Mobile app bar (WhatsApp-style green header) --}}
                <header class="pt-safe sticky top-0 z-20 bg-emerald-600 text-white lg:hidden">
                    <div class="flex h-14 items-center justify-between px-4">
                        <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">
                            {{ config('app.name') }}
                        </a>
                        <a href="{{ route('notifications.index') }}" class="tap-active relative -mr-2 p-2" aria-label="Notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                            </svg>
                            @if ($unread > 0)
                                <span class="absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">{{ $unread }}</span>
                            @endif
                        </a>
                    </div>
                </header>

                {{-- Per-page header slot (kept for both breakpoints so page actions stay reachable) --}}
                @if (isset($header))
                    <div class="border-b border-gray-200 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 lg:py-5">
                            {{ $header }}
                        </div>
                    </div>
                @endif

                <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:py-8 lg:pb-8">
                    {{ $slot }}
                </main>
            </div>

            {{-- Mobile bottom tab bar + "More" sheet --}}
            <x-bottom-nav :unread="$unread" />
        </div>
    </body>
</html>
