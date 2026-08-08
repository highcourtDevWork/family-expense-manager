<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Settings</h2>
    </x-slot>

    <x-flash />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('profile.edit') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Profile & Password</h3>
            <p class="mt-1 text-sm text-gray-500">Update your name, email, and password.</p>
        </a>

        <a href="{{ route('categories.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Expense Categories</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $categoryCount }} categories. Add, rename, or remove.</p>
        </a>

        <a href="{{ route('sources.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Income Sources</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $sourceCount }} sources. Add, rename, or remove.</p>
        </a>

        <a href="{{ route('recurring.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Recurring Expenses</h3>
            <p class="mt-1 text-sm text-gray-500">Rent, internet, bills — add them monthly with one click.</p>
        </a>

        <a href="{{ route('api-keys.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">API Access</h3>
            <p class="mt-1 text-sm text-gray-500">Generate an API key & secret for programmatic access.</p>
        </a>

        <a href="{{ route('family.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Family Members</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $family?->name }} — manage members and roles.</p>
        </a>
    </div>
</x-app-layout>
