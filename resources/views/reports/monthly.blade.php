<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Reports</a>
                <h2 class="text-xl font-semibold text-gray-800">Monthly Report</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.monthly.pdf', ['month' => $report['month']->format('Y-m')]) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">PDF</a>
                <a href="{{ route('reports.monthly.csv', ['month' => $report['month']->format('Y-m')]) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Excel (CSV)</a>
            </div>
        </div>
    </x-slot>

    <form method="GET" action="{{ route('reports.monthly') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        <div>
            <x-input-label for="month" value="Month" />
            <x-text-input id="month" name="month" type="month" class="mt-1" value="{{ $report['month']->format('Y-m') }}" />
        </div>
        <x-primary-button>View</x-primary-button>
    </form>

    <h3 class="mb-3 text-lg font-semibold text-gray-800">{{ $report['month']->format('F Y') }}</h3>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="Income" :value="$report['income']" tone="emerald" icon="arrow-down" />
        <x-stat-card label="Expense" :value="$report['expense']" tone="red" icon="arrow-up" />
        <x-stat-card label="Balance" :value="$report['balance']" :tone="(float) $report['balance'] >= 0 ? 'emerald' : 'red'" icon="scale" />
        <x-stat-card label="Savings (this month)" :value="$report['savings']" tone="emerald" icon="bank" />
        <x-stat-card label="Loan Balance" :value="$report['loanBalance']" tone="amber" icon="bank" />
        <x-stat-card label="Net Debt (owed)" :value="$report['debtOwed']" :tone="(float) $report['debtOwed'] > 0 ? 'red' : 'emerald'" icon="scale" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white shadow-sm">
            <h4 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">Expenses by Category</h4>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['expensesByCategory'] as $row)
                        <tr>
                            <td class="px-5 py-2 text-gray-700">{{ $row['name'] }}</td>
                            <td class="px-5 py-2 text-right font-medium text-gray-900">₹{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-gray-500">No expenses.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-white shadow-sm">
            <h4 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">Income by Source</h4>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['incomeBySource'] as $row)
                        <tr>
                            <td class="px-5 py-2 text-gray-700">{{ $row['name'] }}</td>
                            <td class="px-5 py-2 text-right font-medium text-gray-900">₹{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-gray-500">No income.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
