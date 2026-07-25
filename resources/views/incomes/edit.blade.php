<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Income</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('incomes.partials.form', [
            'action' => route('incomes.update', $income),
            'method' => 'PUT',
            'income' => $income,
        ])
    </div>
</x-app-layout>
