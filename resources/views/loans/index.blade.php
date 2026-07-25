<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Loans</h2>
            <a href="{{ route('loans.create') }}"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                + Add Loan
            </a>
        </div>
    </x-slot>

    <x-flash />

    <div class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Total Outstanding Balance</p>
        <p class="mt-1 text-2xl font-bold text-amber-600">₹{{ number_format((float) $totalBalance, 2) }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @forelse ($loans as $loan)
            <a href="{{ route('loans.show', $loan) }}"
                class="block rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $loan->loan_name }}</h3>
                        <p class="text-xs text-gray-500">{{ $loan->loan_type->label() }} · {{ $loan->user->name }}</p>
                    </div>
                    <x-loan-status-badge :status="$loan->status" />
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex justify-between text-xs text-gray-500">
                        <span>Paid ₹{{ number_format((float) $loan->paid_amount, 2) }}</span>
                        <span>{{ $loan->progressPercentage() }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $loan->progressPercentage() }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <span class="text-gray-500">of ₹{{ number_format((float) $loan->total_amount, 2) }}</span>
                        <span class="font-semibold text-amber-600">₹{{ number_format((float) $loan->remaining_amount, 2) }} left</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm md:col-span-2">
                No loans yet. <a href="{{ route('loans.create') }}" class="text-emerald-700 underline">Add your first loan</a>.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $loans->links() }}
    </div>
</x-app-layout>
