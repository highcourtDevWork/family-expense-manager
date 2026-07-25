<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        @php
            // route name => [label, icon, active-pattern]
            $navLinks = [
                'dashboard' => ['Dashboard', 'dashboard', 'dashboard'],
                'expenses.index' => ['Expenses', 'expenses', 'expenses.*'],
                'incomes.index' => ['Income', 'income', 'incomes.*'],
                'loans.index' => ['Loans', 'loans', 'loans.*'],
                'family.index' => ['Family', 'family', 'family.*'],
            ];
        @endphp

        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
            {{-- Mobile overlay --}}
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden" style="display: none;"></div>

            {{-- Sidebar --}}
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform lg:static lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
                <div class="flex h-16 items-center justify-between border-b border-gray-200 px-5">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="text-base" />
                    </a>
                    <button type="button" @click="sidebarOpen = false" class="text-gray-400 hover:text-gray-600 lg:hidden" aria-label="Close menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                    @foreach ($navLinks as $route => [$label, $icon, $pattern])
                        <x-nav-link :href="route($route)" :icon="$icon" :active="request()->routeIs($pattern)">
                            {{ $label }}
                        </x-nav-link>
                    @endforeach
                </nav>

                <div class="border-t border-gray-200 p-4">
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
                {{-- Mobile top bar --}}
                <div class="flex h-16 items-center gap-3 border-b border-gray-200 bg-white px-4 lg:hidden">
                    <button type="button" @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700" aria-label="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <x-application-logo class="text-sm" />
                </div>

                @if (isset($header))
                    <header class="border-b border-gray-200 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
