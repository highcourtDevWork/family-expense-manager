<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center gap-8 bg-gray-100 font-sans antialiased">
        <x-application-logo class="text-2xl" />

        <p class="max-w-md text-center text-gray-600">
            Track expenses, income, loans, debts, and savings for your whole family in one place.
        </p>

        <div class="flex gap-4">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Go to Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Register
                </a>
            @endauth
        </div>
    </body>
</html>
