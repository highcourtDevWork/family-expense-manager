<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Review Import</h2>
            <a href="{{ route('imports.create') }}" class="text-sm text-gray-500 hover:text-gray-700">← Choose another file</a>
        </div>
    </x-slot>

    {{-- Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">New Expenses</p>
            <p class="mt-1 text-2xl font-bold text-red-500">₹{{ number_format($expenseTotal, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">New Income</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">₹{{ number_format($incomeTotal, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Already Imported (skipped)</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ count($duplicates) }}</p>
        </div>
    </div>

    @if (count($new) === 0)
        <div class="mt-6 rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm">
            Everything in this statement has already been imported. Nothing new to add.
        </div>
    @else
        <div class="mt-6 flex items-center justify-between">
            <p class="text-sm text-gray-600">{{ count($new) }} new transaction(s) will be imported.</p>
            <form method="POST" action="{{ route('imports.confirm') }}">
                @csrf
                <x-primary-button>Confirm & Import {{ count($new) }}</x-primary-button>
            </form>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Counterparty</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($new as $t)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ \Illuminate\Support\Carbon::parse($t->date)->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-red-50 text-red-600' => $t->isExpense(),
                                        'bg-emerald-50 text-emerald-700' => ! $t->isExpense(),
                                    ])>{{ $t->isExpense() ? 'Expense' : 'Income' }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $t->counterparty }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900">₹{{ number_format($t->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
