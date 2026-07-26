<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('debts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Debts</a>
                <h2 class="text-xl font-semibold text-gray-800">{{ $debt->person_name }}</h2>
                <x-debt-status-badge :status="$debt->status" />
            </div>
            @can('update', $debt)
                <div class="flex items-center gap-2">
                    <a href="{{ route('debts.edit', $debt) }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('debts.destroy', $debt) }}"
                        onsubmit="return confirm('Delete this debt and its payment history?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <x-flash />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">{{ $debt->debt_type->personLabel() }}</p>
            <p class="mt-1 text-xl font-bold text-gray-800">₹{{ number_format((float) $debt->amount, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Paid</p>
            <p class="mt-1 text-xl font-bold text-emerald-600">₹{{ number_format((float) $debt->paid_amount, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Balance</p>
            <p class="mt-1 text-xl font-bold text-amber-600">₹{{ number_format((float) $debt->balance_amount, 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-lg bg-white p-5 shadow-sm">
        <div class="mb-1 flex justify-between text-sm text-gray-500">
            <span>Settlement progress</span>
            <span>{{ $debt->progressPercentage() }}%</span>
        </div>
        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $debt->progressPercentage() }}%"></div>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-gray-500">Type</dt><dd class="font-medium text-gray-800">{{ $debt->debt_type->label() }}</dd></div>
            <div><dt class="text-gray-500">Recorded by</dt><dd class="font-medium text-gray-800">{{ $debt->user->name }}</dd></div>
            <div><dt class="text-gray-500">Date</dt><dd class="font-medium text-gray-800">{{ $debt->date->format('d M Y') }}</dd></div>
            <div><dt class="text-gray-500">Notes</dt><dd class="font-medium text-gray-800">{{ $debt->notes ?? '—' }}</dd></div>
        </dl>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        @can('update', $debt)
            <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-1">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">Record a Payment</h3>
                @if ($debt->status === \App\Enums\DebtStatus::Settled)
                    <p class="text-sm text-gray-500">This debt is fully settled. 🎉</p>
                @else
                    <form method="POST" action="{{ route('debts.payments.store', $debt) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="payment_date" value="Date" />
                            <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1"
                                value="{{ old('payment_date', now()->format('Y-m-d')) }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('payment_date')" />
                        </div>
                        <div>
                            <x-input-label for="amount" value="Amount (₹)" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01"
                                max="{{ $debt->balance_amount }}" class="mt-1" value="{{ old('amount') }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                        </div>
                        <div>
                            <x-input-label for="notes" value="Notes (optional)" />
                            <x-text-input id="notes" name="notes" type="text" class="mt-1" value="{{ old('notes') }}" maxlength="1000" />
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>
                        <x-primary-button class="w-full justify-center">Add Payment</x-primary-button>
                    </form>
                @endif
            </div>
        @endcan

        <div class="rounded-lg bg-white shadow-sm lg:col-span-2">
            <h3 class="border-b border-gray-100 px-5 py-4 text-sm font-semibold text-gray-700">Payment History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Notes</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            @can('update', $debt)<th class="px-5 py-3"></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-700">{{ $payment->payment_date->format('d M Y') }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $payment->notes ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-emerald-600">
                                    ₹{{ number_format((float) $payment->amount, 2) }}
                                </td>
                                @can('update', $debt)
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('debts.payments.destroy', [$debt, $payment]) }}"
                                            onsubmit="return confirm('Remove this payment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">No payments recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
