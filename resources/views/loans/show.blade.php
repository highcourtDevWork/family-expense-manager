<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('loans.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Loans</a>
                <h2 class="text-xl font-semibold text-gray-800">{{ $loan->loan_name }}</h2>
                <x-loan-status-badge :status="$loan->status" />
            </div>
            @can('update', $loan)
                <div class="flex items-center gap-2">
                    <a href="{{ route('loans.edit', $loan) }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('loans.destroy', $loan) }}"
                        onsubmit="return confirm('Delete this loan and its payment history?');">
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

    @if ($loan->is_emi && $emiStats)
        {{-- EMI overview --}}
        <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Interest Rate</p>
                <p class="mt-1 text-xl font-bold text-gray-800">{{ number_format((float) $loan->interest_rate, 2) }}%</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Remaining Tenure</p>
                <p class="mt-1 text-xl font-bold text-gray-800">{{ $emiStats['pendingCount'] }} / {{ $loan->tenure_months }} mo</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Total Payable</p>
                <p class="mt-1 text-xl font-bold text-gray-800">₹{{ number_format($emiStats['totalPayable'], 2) }}</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Total Paid</p>
                <p class="mt-1 text-xl font-bold text-emerald-600">₹{{ number_format($emiStats['totalPaid'], 2) }}</p>
            </div>
        </div>
    @endif

    {{-- Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total Loan</p>
            <p class="mt-1 text-xl font-bold text-gray-800">₹{{ number_format((float) $loan->total_amount, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Paid</p>
            <p class="mt-1 text-xl font-bold text-emerald-600">₹{{ number_format((float) $loan->paid_amount, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Remaining</p>
            <p class="mt-1 text-xl font-bold text-amber-600">₹{{ number_format((float) $loan->remaining_amount, 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-lg bg-white p-5 shadow-sm">
        <div class="mb-1 flex justify-between text-sm text-gray-500">
            <span>Repayment progress</span>
            <span>{{ $loan->progressPercentage() }}%</span>
        </div>
        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $loan->progressPercentage() }}%"></div>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-gray-500">Type</dt><dd class="font-medium text-gray-800">{{ $loan->loan_type->label() }}</dd></div>
            <div><dt class="text-gray-500">Holder</dt><dd class="font-medium text-gray-800">{{ $loan->user->name }}</dd></div>
            <div><dt class="text-gray-500">Start</dt><dd class="font-medium text-gray-800">{{ $loan->start_date->format('d M Y') }}</dd></div>
            <div><dt class="text-gray-500">End</dt><dd class="font-medium text-gray-800">{{ $loan->end_date?->format('d M Y') ?? '—' }}</dd></div>
        </dl>
    </div>

    @unless ($loan->is_emi)
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Add payment --}}
        @can('update', $loan)
            <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-1">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">Record a Payment</h3>
                @if ($loan->status === \App\Enums\LoanStatus::Closed)
                    <p class="text-sm text-gray-500">This loan is fully repaid. 🎉</p>
                @else
                    <form method="POST" action="{{ route('loans.payments.store', $loan) }}" class="space-y-4">
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
                                max="{{ $loan->remaining_amount }}" class="mt-1" value="{{ old('amount') }}" required />
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

        {{-- Payment history --}}
        <div class="rounded-lg bg-white shadow-sm lg:col-span-2">
            <h3 class="border-b border-gray-100 px-5 py-4 text-sm font-semibold text-gray-700">Payment History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Notes</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            @can('update', $loan)<th class="px-5 py-3"></th>@endcan
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
                                @can('update', $loan)
                                    <td class="px-5 py-3 text-right">
                                        <form method="POST" action="{{ route('loans.payments.destroy', [$loan, $payment]) }}"
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
    @else
        {{-- EMI amortization schedule --}}
        <div class="mt-6 rounded-lg bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-700">EMI Schedule</h3>
                <span class="text-xs text-gray-500">Installments auto-post on their due date.</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Due Date</th>
                            <th class="px-5 py-3 text-right">EMI</th>
                            <th class="px-5 py-3 text-right">Principal</th>
                            <th class="px-5 py-3 text-right">Interest</th>
                            <th class="px-5 py-3 text-right">Balance</th>
                            <th class="px-5 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($emis as $emi)
                            <tr class="{{ $emi->status === \App\Enums\EmiStatus::Paid ? '' : 'bg-amber-50/40' }}">
                                <td class="px-5 py-2 text-gray-500">{{ $emi->sequence }}</td>
                                <td class="whitespace-nowrap px-5 py-2 text-gray-700">{{ $emi->due_date->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-2 text-right font-medium text-gray-900">₹{{ number_format((float) $emi->emi_amount, 2) }}</td>
                                <td class="whitespace-nowrap px-5 py-2 text-right text-gray-600">₹{{ number_format((float) $emi->principal_component, 2) }}</td>
                                <td class="whitespace-nowrap px-5 py-2 text-right text-gray-600">₹{{ number_format((float) $emi->interest_component, 2) }}</td>
                                <td class="whitespace-nowrap px-5 py-2 text-right text-gray-600">₹{{ number_format((float) $emi->closing_balance, 2) }}</td>
                                <td class="px-5 py-2 text-center">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $emi->status === \App\Enums\EmiStatus::Paid,
                                        'bg-gray-100 text-gray-500' => $emi->status !== \App\Enums\EmiStatus::Paid,
                                    ])>{{ $emi->status->label() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endunless
</x-app-layout>
