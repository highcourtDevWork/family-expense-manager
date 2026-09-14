<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Shared Expenses</h2>
            <a href="{{ route('bachelor.splits.create') }}"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                + Add Shared Expense
            </a>
        </div>
    </x-slot>

    <x-flash />

    {{-- Month switcher --}}
    <form method="GET" action="{{ route('bachelor.splits.index') }}" class="mb-6 flex items-end gap-3">
        <div>
            <x-input-label for="month" value="Month" />
            <x-text-input id="month" name="month" type="month" class="mt-1"
                value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" />
        </div>
    </form>

    {{-- Summary --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total shared · {{ $month->format('F Y') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">₹{{ number_format($total, 2) }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Roommates</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $members->count() }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Equal share</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">
                ₹{{ $members->count() > 0 ? number_format($total / $members->count(), 2) : '0.00' }}
            </p>
        </div>
    </div>

    {{-- Who owes / is owed --}}
    <div class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-gray-700">Settlement this month</h3>
        <div class="flex flex-wrap gap-3">
            @foreach ($balances as $b)
                <div class="min-w-[10rem] flex-1 rounded-lg border border-gray-100 p-3">
                    <p class="font-medium text-gray-800">{{ $b['member']->name }}</p>
                    @if ($b['net'] > 0.001)
                        <p class="text-sm text-emerald-600">is owed ₹{{ number_format($b['net'], 2) }}</p>
                    @elseif ($b['net'] < -0.001)
                        <p class="text-sm text-red-600">owes ₹{{ number_format(abs($b['net']), 2) }}</p>
                    @else
                        <p class="text-sm text-gray-400">settled up</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Expenses + per-member shares --}}
    <div class="space-y-4">
        @forelse ($expenses as $expense)
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $expense->title }}</p>
                        <p class="text-xs text-gray-500">
                            Paid by <span class="font-medium text-gray-700">{{ $expense->payer->name }}</span>
                            @if ($expense->category) · {{ $expense->category->name }} @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">₹{{ number_format((float) $expense->total_amount, 2) }}</p>
                        <p class="text-xs text-gray-500">Outstanding: ₹{{ number_format((float) $expense->outstandingAmount(), 2) }}</p>
                    </div>
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($expense->shares as $share)
                            <tr>
                                <td class="px-5 py-2.5 text-gray-700">{{ $share->member->name }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-gray-900">₹{{ number_format((float) $share->amount, 2) }}</td>
                                <td class="px-5 py-2.5 text-right">
                                    @if ($share->user_id === $expense->paid_by)
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">payer</span>
                                    @elseif ($share->settled_at)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">settled ✓</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">owes</span>
                                    @endif
                                </td>
                                <td class="px-5 py-2.5 text-right">
                                    @if ($share->user_id !== $expense->paid_by)
                                        @if ($share->settled_at)
                                            <form method="POST" action="{{ route('bachelor.splits.unsettle', $share) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-500 hover:underline">Undo</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('bachelor.splits.settle', $share) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                    Mark settled
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="rounded-lg bg-white px-5 py-10 text-center text-gray-500 shadow-sm">
                No shared expenses for {{ $month->format('F Y') }}.
                <a href="{{ route('bachelor.splits.create') }}" class="text-emerald-700 underline">Add one</a>.
            </div>
        @endforelse
    </div>
</x-app-layout>
