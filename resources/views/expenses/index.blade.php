<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Expenses</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('recurring.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Recurring
                </a>
                <a href="{{ route('expenses.create') }}"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    + Add Expense
                </a>
            </div>
        </div>
    </x-slot>

    <x-flash />

    {{-- Filter / search bar --}}
    <form method="GET" action="{{ route('expenses.index') }}"
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
            <x-input-label for="filter_category" value="Category" />
            <x-select id="filter_category" name="category_id" class="mt-1">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>
                        {{ $category->name }}
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
            <x-input-label for="search" value="Search description" />
            <x-text-input id="search" name="search" type="search" class="mt-1"
                value="{{ $filters['search'] ?? '' }}" placeholder="e.g. lunch" />
        </div>

        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
            <x-primary-button>Apply filters</x-primary-button>
            <a href="{{ route('expenses.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Clear
            </a>
            <span class="ml-auto self-center text-sm text-gray-600">
                Total: <span class="font-semibold text-gray-900">₹{{ number_format((float) $total, 2) }}</span>
            </span>
        </div>
    </form>

    {{-- Expense table --}}
    @php $deletableIds = $expenses->filter(fn ($e) => auth()->user()->can('delete', $e))->pluck('id')->values(); @endphp
    <div x-data="{ selected: [], allIds: @js($deletableIds) }">
        {{-- Bulk action bar (only appears once rows are selected) --}}
        <div x-show="selected.length > 0" x-cloak
            class="mb-3 flex items-center justify-between rounded-lg border border-red-100 bg-red-50 px-4 py-2">
            <span class="text-sm font-medium text-red-800"><span x-text="selected.length"></span> selected</span>
            <div class="flex items-center gap-3">
                <button type="button" @click="selected = []" class="text-sm text-gray-600 hover:text-gray-800">Clear</button>
                <form method="POST" action="{{ route('expenses.bulk-delete') }}"
                    onsubmit="return confirm('Delete the selected expenses?');">
                    @csrf
                    @method('DELETE')
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700">
                        Delete selected
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" aria-label="Select all"
                                    :disabled="allIds.length === 0"
                                    :checked="allIds.length > 0 && selected.length === allIds.length"
                                    @change="selected = $event.target.checked ? [...allIds] : []"
                                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                            </th>
                            <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @can('delete', $expense)
                                    <input type="checkbox" value="{{ $expense->id }}" x-model.number="selected"
                                        aria-label="Select expense"
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                @endcan
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $expense->expense_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                    {{ $expense->category->name }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $expense->user->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $expense->payment_method->label() }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $expense->description ?? '—' }}
                                @if ($expense->attachment_path)
                                    <a href="{{ Storage::url($expense->attachment_path) }}" target="_blank"
                                        class="ml-1 text-emerald-700 underline">📎</a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900">
                                ₹{{ number_format((float) $expense->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @can('update', $expense)
                                    <a href="{{ route('expenses.edit', $expense) }}"
                                        class="text-emerald-700 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $expense)
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                        class="ml-3 inline"
                                        onsubmit="return confirm('Delete this expense?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                No expenses found. <a href="{{ route('expenses.create') }}"
                                    class="text-emerald-700 underline">Add your first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</x-app-layout>
