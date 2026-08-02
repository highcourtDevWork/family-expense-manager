<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">New Recurring Expense</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('recurring.partials.form', [
            'action' => route('recurring.store'),
            'method' => 'POST',
            'template' => null,
        ])
    </div>
</x-app-layout>
