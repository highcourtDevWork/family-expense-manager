<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <p class="text-gray-600">
            Welcome back, {{ auth()->user()->name }}. Your family's expense dashboard will appear here.
        </p>
    </div>
</x-app-layout>
