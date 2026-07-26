<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">{{ $summary['month']->format('F Y') }}</span>
                <a href="{{ route('reports.monthly.pdf', ['month' => $summary['month']->format('Y-m')]) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Summary cards --}}
    @php
        $balance = (float) $summary['balance'];
        $debtOwed = (float) $summary['debtOwed'];
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="This Month Income" :value="$summary['income']" tone="emerald" icon="arrow-down" />
        <x-stat-card label="This Month Expense" :value="$summary['expense']" tone="red" icon="arrow-up" />
        <x-stat-card label="Balance" :value="$summary['balance']" :tone="$balance >= 0 ? 'emerald' : 'red'" icon="scale" />
        <x-stat-card label="Total Loan Balance" :value="$summary['loanBalance']" tone="amber" icon="bank" :href="route('loans.index')" />
        <x-stat-card label="Net Debt (owed)" :value="$summary['debtOwed']" :tone="$debtOwed > 0 ? 'red' : 'emerald'" icon="scale" :href="route('debts.index')" />
        <x-stat-card label="Total Savings" :value="$summary['savings']" tone="emerald" icon="bank" :href="route('savings.index')" />
    </div>

    {{-- Charts --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">Income vs Expense (last 6 months)</h3>
            <div class="h-80 w-full" data-chart="income-expense" data-series='@json($trendData)'></div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">Monthly Expense Trend</h3>
            <div class="h-80 w-full" data-chart="trend" data-series='@json($trendData)'></div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">Category-wise Spending ({{ $summary['month']->format('F') }})</h3>
            @if (count($categoryData) > 0)
                <div class="h-80 w-full" data-chart="category" data-series='@json($categoryData)'></div>
            @else
                <p class="py-10 text-center text-sm text-gray-500">No expenses recorded this month yet.</p>
            @endif
        </div>
    </div>
</x-app-layout>
