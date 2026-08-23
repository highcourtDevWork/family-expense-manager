<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Recurring Income</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        @include('recurring-incomes.partials.form', [
            'action' => route('recurring-incomes.update', $template),
            'method' => 'PUT',
            'template' => $template,
        ])
    </div>
</x-app-layout>
