<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Reports</a>
                <h2 class="text-xl font-semibold text-gray-800">Daily Report</h2>
            </div>
        </div>
    </x-slot>

    <form method="GET" action="{{ route('reports.daily') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        <div>
            <x-input-label for="date" value="Date" />
            <x-text-input id="date" name="date" type="date" class="mt-1" value="{{ $report['date']->format('Y-m-d') }}" />
        </div>
        <x-primary-button>View</x-primary-button>
    </form>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Income" :value="$report['income']" tone="emerald" />
        <x-stat-card label="Expense" :value="$report['expense']" tone="red" />
        <x-stat-card label="Balance" :value="$report['balance']" :tone="(float) $report['balance'] >= 0 ? 'emerald' : 'red'" />
    </div>

    <h3 class="mb-2 text-lg font-semibold text-gray-800">{{ $report['date']->format('d M Y') }}</h3>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white shadow-sm">
            <h4 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">Expenses</h4>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['expenses'] as $e)
                        <tr>
                            <td class="px-5 py-2 text-gray-700">{{ $e->category->name }}</td>
                            <td class="px-5 py-2 text-gray-500">{{ $e->user->name }}</td>
                            <td class="px-5 py-2 text-right font-medium text-red-500">₹{{ number_format((float) $e->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-gray-500">No expenses.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-white shadow-sm">
            <h4 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-700">Income</h4>
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['incomes'] as $i)
                        <tr>
                            <td class="px-5 py-2 text-gray-700">{{ $i->source->name }}</td>
                            <td class="px-5 py-2 text-gray-500">{{ $i->user->name }}</td>
                            <td class="px-5 py-2 text-right font-medium text-emerald-600">₹{{ number_format((float) $i->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-gray-500">No income.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
