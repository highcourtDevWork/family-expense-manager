<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Income</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('incomes.report') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Monthly Report
                </a>
                <a href="{{ route('incomes.create') }}"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    + Add Income
                </a>
            </div>
        </div>
    </x-slot>

    <x-flash />

    {{-- Filter / search bar --}}
    <form method="GET" action="{{ route('incomes.index') }}"
        class="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        <div>
            <x-input-label for="period" value="Period" />
            <x-select id="period" name="period" class="mt-1">
                @foreach (['' => 'All time', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'custom' => 'Custom range'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['period'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>

        <div>
            <x-input-label for="date_from" value="From" />
            <x-text-input id="date_from" name="date_from" type="date" class="mt-1"
                value="{{ $filters['date_from'] ?? '' }}" />
        </div>

        <div>
            <x-input-label for="date_to" value="To" />
            <x-text-input id="date_to" name="date_to" type="date" class="mt-1"
                value="{{ $filters['date_to'] ?? '' }}" />
        </div>

        <div>
            <x-input-label for="filter_source" value="Source" />
            <x-select id="filter_source" name="income_source_id" class="mt-1">
                <option value="">All sources</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->id }}" @selected((int) ($filters['income_source_id'] ?? 0) === $source->id)>
                        {{ $source->name }}
                    </option>
                @endforeach
            </x-select>
        </div>

        <div>
            <x-input-label for="filter_member" value="Member" />
            <x-select id="filter_member" name="user_id" class="mt-1">
                <option value="">All members</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((int) ($filters['user_id'] ?? 0) === $member->id)>
                        {{ $member->name }}
                    </option>
                @endforeach
            </x-select>
        </div>

        <div>
            <x-input-label for="search" value="Search notes" />
            <x-text-input id="search" name="search" type="search" class="mt-1"
                value="{{ $filters['search'] ?? '' }}" placeholder="e.g. bonus" />
        </div>

        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
            <x-primary-button>Apply filters</x-primary-button>
            <a href="{{ route('incomes.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Clear
            </a>
            <span class="ml-auto self-center text-sm text-gray-600">
                Total: <span class="font-semibold text-emerald-700">₹{{ number_format((float) $total, 2) }}</span>
            </span>
        </div>
    </form>

    {{-- Income table --}}
    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Notes</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($incomes as $income)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $income->income_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                    {{ $income->source->name }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $income->user->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $income->notes ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900">
                                ₹{{ number_format((float) $income->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @can('update', $income)
                                    <a href="{{ route('incomes.edit', $income) }}"
                                        class="text-emerald-700 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $income)
                                    <form method="POST" action="{{ route('incomes.destroy', $income) }}"
                                        class="ml-3 inline"
                                        onsubmit="return confirm('Delete this income entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                No income found. <a href="{{ route('incomes.create') }}"
                                    class="text-emerald-700 underline">Add your first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $incomes->links() }}
    </div>
</x-app-layout>
