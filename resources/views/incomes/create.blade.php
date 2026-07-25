<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Add Income</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('incomes.partials.form', [
            'action' => route('incomes.store'),
            'method' => 'POST',
            'income' => null,
        ])
    </div>
</x-app-layout>
