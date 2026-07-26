<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Debts</h2>
            <a href="{{ route('debts.create') }}"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                + Add Debt
            </a>
        </div>
    </x-slot>

    <x-flash />

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">You Owe (Borrowed)</p>
            <p class="mt-1 text-2xl font-bold text-red-500">₹{{ number_format((float) $borrowedBalance, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Owed to You (Lent)</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">₹{{ number_format((float) $lentBalance, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @forelse ($debts as $debt)
            <a href="{{ route('debts.show', $debt) }}"
                class="block rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $debt->person_name }}</h3>
                        <p class="text-xs text-gray-500">
                            <span @class([
                                'font-medium',
                                'text-red-500' => $debt->debt_type === \App\Enums\DebtType::Borrowed,
                                'text-emerald-600' => $debt->debt_type === \App\Enums\DebtType::Lent,
                            ])>{{ $debt->debt_type->label() }}</span>
                            · {{ $debt->user->name }} · {{ $debt->date->format('d M Y') }}
                        </p>
                    </div>
                    <x-debt-status-badge :status="$debt->status" />
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex justify-between text-xs text-gray-500">
                        <span>Paid ₹{{ number_format((float) $debt->paid_amount, 2) }}</span>
                        <span>{{ $debt->progressPercentage() }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $debt->progressPercentage() }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-sm">
                        <span class="text-gray-500">of ₹{{ number_format((float) $debt->amount, 2) }}</span>
                        <span class="font-semibold text-amber-600">₹{{ number_format((float) $debt->balance_amount, 2) }} left</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm md:col-span-2">
                No debts yet. <a href="{{ route('debts.create') }}" class="text-emerald-700 underline">Add your first one</a>.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $debts->links() }}
    </div>
</x-app-layout>
