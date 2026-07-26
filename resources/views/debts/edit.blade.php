<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Debt</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('debts.partials.form', [
            'action' => route('debts.update', $debt),
            'method' => 'PUT',
            'debt' => $debt,
        ])
    </div>
</x-app-layout>
