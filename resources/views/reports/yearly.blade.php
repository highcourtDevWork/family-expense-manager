<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Reports</a>
                <h2 class="text-xl font-semibold text-gray-800">Yearly Report</h2>
            </div>
            <a href="{{ route('reports.yearly.csv', ['year' => $report['year']]) }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Excel (CSV)</a>
        </div>
    </x-slot>

    <form method="GET" action="{{ route('reports.yearly') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        <div>
            <x-input-label for="year" value="Year" />
            <x-text-input id="year" name="year" type="number" min="2000" max="{{ (int) now()->format('Y') + 1 }}"
                class="mt-1" value="{{ $report['year'] }}" />
        </div>
        <x-primary-button>View</x-primary-button>
    </form>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Year Income" :value="$report['totalIncome']" tone="emerald" />
        <x-stat-card label="Year Expense" :value="$report['totalExpense']" tone="red" />
        <x-stat-card label="Year Balance" :value="$report['totalBalance']" :tone="(float) $report['totalBalance'] >= 0 ? 'emerald' : 'red'" />
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <h3 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">{{ $report['year'] }} — Month by Month</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Month</th>
                        <th class="px-5 py-3 text-right">Income</th>
                        <th class="px-5 py-3 text-right">Expense</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($report['months'] as $m)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-2 text-gray-700">{{ $m['label'] }}</td>
                            <td class="px-5 py-2 text-right text-emerald-600">₹{{ number_format($m['income'], 2) }}</td>
                            <td class="px-5 py-2 text-right text-red-500">₹{{ number_format($m['expense'], 2) }}</td>
                            <td class="px-5 py-2 text-right font-medium {{ $m['balance'] >= 0 ? 'text-gray-900' : 'text-red-500' }}">
                                ₹{{ number_format($m['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 font-semibold">
                        <td class="px-5 py-3 text-gray-800">Total</td>
                        <td class="px-5 py-3 text-right text-emerald-700">₹{{ number_format((float) $report['totalIncome'], 2) }}</td>
                        <td class="px-5 py-3 text-right text-red-600">₹{{ number_format((float) $report['totalExpense'], 2) }}</td>
                        <td class="px-5 py-3 text-right text-gray-900">₹{{ number_format((float) $report['totalBalance'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
