<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Expense</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('expenses.partials.form', [
            'action' => route('expenses.update', $expense),
            'method' => 'PUT',
            'expense' => $expense,
        ])
    </div>
</x-app-layout>
