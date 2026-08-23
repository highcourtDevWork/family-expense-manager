<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Recurring Income</h2>
            <a href="{{ route('recurring-incomes.create') }}"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                + New Template
            </a>
        </div>
    </x-slot>

    <x-flash />

    <p class="mb-4 text-sm text-gray-600">
        Set up salaries once — then one-click add them to <strong>{{ $month->format('F Y') }}</strong> instead of re-entering them.
    </p>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Earned by</th>
                        <th class="px-5 py-3">Source</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-center">Auto</th>
                        <th class="px-5 py-3 text-right">This Month</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($templates as $template)
                        @php $postedThisMonth = $postedRefs->has($template->referenceFor($month)); @endphp
                        <tr class="{{ $template->active ? '' : 'opacity-50' }}">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $template->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $template->earner->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $template->source?->name ?? 'Salary' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-emerald-600">₹{{ number_format((float) $template->amount, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                @if ($template->auto_post)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Auto</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($postedThisMonth)
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Added ✓</span>
                                @elseif ($template->active)
                                    <form method="POST" action="{{ route('recurring-incomes.post', $template) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                            Add to this month
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">Inactive</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <a href="{{ route('recurring-incomes.edit', $template) }}" class="text-emerald-700 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('recurring-incomes.destroy', $template) }}"
                                    class="ml-3 inline" onsubmit="return confirm('Delete this template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">
                                No recurring income yet. <a href="{{ route('recurring-incomes.create') }}" class="text-emerald-700 underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
