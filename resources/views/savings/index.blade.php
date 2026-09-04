<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Savings</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('recurring-savings.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Recurring
                </a>
                <a href="{{ route('savings.create') }}"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    + Add Saving
                </a>
            </div>
        </div>
    </x-slot>

    <x-flash />

    <div class="mb-6 rounded-lg bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Total Savings</p>
        <p class="mt-1 text-2xl font-bold text-emerald-600">₹{{ number_format((float) $total, 2) }}</p>

        @if ($byType->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($byType as $type => $amount)
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                        {{ \App\Enums\SavingType::from($type)->label() }}:
                        <span class="font-semibold text-gray-900">₹{{ number_format((float) $amount, 2) }}</span>
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Notes</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($savings as $saving)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $saving->date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                    {{ $saving->saving_type->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $saving->user->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $saving->notes ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900">
                                ₹{{ number_format((float) $saving->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @can('update', $saving)
                                    <a href="{{ route('savings.edit', $saving) }}" class="text-emerald-700 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $saving)
                                    <form method="POST" action="{{ route('savings.destroy', $saving) }}"
                                        class="ml-3 inline" onsubmit="return confirm('Delete this saving?');">
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
                                No savings recorded. <a href="{{ route('savings.create') }}" class="text-emerald-700 underline">Add your first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $savings->links() }}
    </div>
</x-app-layout>
