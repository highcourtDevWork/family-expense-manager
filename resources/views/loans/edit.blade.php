<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Loan</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('loans.partials.form', [
            'action' => route('loans.update', $loan),
            'method' => 'PUT',
            'loan' => $loan,
        ])
    </div>
</x-app-layout>
