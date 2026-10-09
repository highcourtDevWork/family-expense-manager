<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Savings Rate</h2>
    </x-slot>

    @php
        $d = $data;
        $income = (float) $d['income'];
        $hasIncome = $income > 0;
        $outflow = $d['expenses'] + $d['loans'] + $d['savingsDeposits'];
        $base = max($income, $outflow, 0.01);
        $w = fn ($x) => max(0, round(((float) $x) / $base * 100, 2));
        $money = fn ($x) => '₹' . number_format((float) $x, 2);
        $rate = (float) $d['savingsRate'];
        $overspent = $d['leftover'] < 0;

        // Allocation rows: [label, amount, percent-of-income, swatch colour].
        $rows = [
            ['Expenses', $d['expenses'], $d['expenseRate'], 'bg-rose-500', 'text-rose-600'],
            ['Loan / EMI payments', $d['loans'], $d['loanRate'], 'bg-amber-500', 'text-amber-600'],
            ['Savings deposits', $d['savingsDeposits'], $d['depositRate'], 'bg-sky-500', 'text-sky-600'],
            ['Leftover', $d['leftover'], $d['leftoverRate'], 'bg-emerald-500', 'text-emerald-600'],
        ];
    @endphp

    {{-- Period toggle --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (['month' => 'This month', 'year' => 'This year', 'all' => 'All time'] as $key => $label)
            <a href="{{ route('savings-rate.index', ['period' => $key]) }}"
                class="rounded-full px-4 py-1.5 text-sm font-semibold transition
                    {{ $period === $key ? 'bg-emerald-600 text-white shadow-sm' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
        <span class="ml-auto self-center text-sm font-medium text-gray-500">{{ $rangeLabel }}</span>
    </div>

    @unless ($hasIncome)
        <div class="rounded-lg bg-white p-10 text-center shadow-sm">
            <p class="text-gray-500">No income recorded for this period, so there's nothing to divide up yet.</p>
            <a href="{{ route('incomes.create') }}" class="mt-2 inline-block text-emerald-700 underline">Add income</a>
        </div>
    @else
        {{-- Headline savings rate --}}
        <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Savings rate</p>
            <p class="mt-1 text-4xl font-bold {{ $rate >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ number_format($rate, 1) }}%
            </p>
            <p class="mt-2 text-sm text-gray-600">
                You kept <span class="font-semibold text-gray-900">{{ $money($d['saved']) }}</span>
                of your <span class="font-semibold text-gray-900">{{ $money($income) }}</span> income {{ strtolower($rangeLabel) === 'all time' ? 'across all time' : 'in ' . $rangeLabel }}.
            </p>
            @if ($overspent)
                <p class="mt-2 rounded-md bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">
                    You spent {{ $money(abs($d['leftover'])) }} more than you earned this period.
                </p>
            @endif
        </div>

        {{-- Allocation bar --}}
        <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
            <p class="mb-3 text-sm font-medium text-gray-700">Where your income went</p>
            <div class="flex h-5 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="bg-rose-500" style="width: {{ $w($d['expenses']) }}%"></div>
                <div class="bg-amber-500" style="width: {{ $w($d['loans']) }}%"></div>
                <div class="bg-sky-500" style="width: {{ $w($d['savingsDeposits']) }}%"></div>
                <div class="bg-emerald-500" style="width: {{ $w(max(0, $d['leftover'])) }}%"></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-600">
                @foreach ($rows as [$label, $amount, $pct, $swatch, $textColor])
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm {{ $swatch }}"></span>
                        {{ $label }} — <span class="font-semibold {{ $textColor }}">{{ number_format((float) $pct, 1) }}%</span>
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Breakdown table --}}
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Bucket</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">% of income</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-emerald-50/40">
                            <td class="px-5 py-3 font-semibold text-gray-800">Income</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ $money($income) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-500">100%</td>
                        </tr>
                        @foreach ($rows as [$label, $amount, $pct, $swatch, $textColor])
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-2 text-gray-700">
                                        <span class="h-2.5 w-2.5 rounded-sm {{ $swatch }}"></span>{{ $label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium {{ $amount < 0 ? 'text-rose-600' : 'text-gray-900' }}">
                                    {{ $money($amount) }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ number_format((float) $pct, 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-500">
            Savings rate is the share of income you didn't spend on expenses or loan payments — your savings deposits plus anything left over.
        </p>
    @endunless
</x-app-layout>
