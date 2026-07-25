<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans antialiased">
        <nav class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="text-base" />
                    </a>

                    <div class="hidden items-center gap-1 sm:flex">
                        @php
                            // route name => [label, active-pattern]
                            $navLinks = [
                                'dashboard' => ['Dashboard', 'dashboard'],
                                'expenses.index' => ['Expenses', 'expenses.*'],
                                'incomes.index' => ['Income', 'incomes.*'],
                                'family.index' => ['Family', 'family.*'],
                            ];
                        @endphp
                        @foreach ($navLinks as $route => [$label, $pattern])
                            <a href="{{ route($route) }}"
                                @class([
                                    'rounded-lg px-3 py-2 text-sm font-medium',
                                    'bg-emerald-50 text-emerald-700' => request()->routeIs($pattern),
                                    'text-gray-600 hover:text-emerald-700' => ! request()->routeIs($pattern),
                                ])>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="hidden text-sm text-gray-600 sm:inline">{{ auth()->user()->name }}</span>

                    <a href="{{ route('profile.edit') }}" class="text-sm font-medium text-gray-600 hover:text-emerald-700">
                        Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-emerald-700">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        @if (isset($header))
            <header class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </body>
</html>
