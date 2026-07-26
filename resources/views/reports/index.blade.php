<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Reports</h2>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('reports.daily', ['date' => $today->format('Y-m-d')]) }}"
            class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Daily Report</h3>
            <p class="mt-1 text-sm text-gray-500">Every expense and income for a single day.</p>
        </a>

        <a href="{{ route('reports.monthly', ['month' => $thisMonth->format('Y-m')]) }}"
            class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Monthly Report</h3>
            <p class="mt-1 text-sm text-gray-500">Income, expense, savings, loans and debt combined. Export to PDF or Excel.</p>
        </a>

        <a href="{{ route('reports.yearly', ['year' => $thisYear]) }}"
            class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Yearly Report</h3>
            <p class="mt-1 text-sm text-gray-500">Month-by-month totals for the whole year. Export to Excel.</p>
        </a>
    </div>
</x-app-layout>
