<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Monthly Income Report</h2>
            <a href="{{ route('incomes.index') }}"
                class="text-sm font-medium text-gray-600 hover:text-gray-900">← Back to income</a>
        </div>
    </x-slot>

    {{-- Month picker --}}
    <form method="GET" action="{{ route('incomes.report') }}"
        class="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        <div>
            <x-input-label for="month" value="Month" />
            <x-text-input id="month" name="month" type="month" class="mt-1"
                value="{{ $report['month']->format('Y-m') }}" />
        </div>
        <x-primary-button>View</x-primary-button>
    </form>

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">
            {{ $report['month']->format('F Y') }}
        </h3>

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="py-2">Source</th>
                    <th class="py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="py-3 text-gray-700">{{ $row->source->name }}</td>
                        <td class="py-3 text-right font-medium text-gray-900">
                            ₹{{ number_format((float) $row->total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="py-8 text-center text-gray-500">
                            No income recorded for this month.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($report['rows']->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-gray-200">
                        <td class="py-3 font-semibold text-gray-800">Total</td>
                        <td class="py-3 text-right text-lg font-bold text-emerald-700">
                            ₹{{ number_format((float) $report['total'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-app-layout>
