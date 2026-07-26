<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Settings</a>
            <h2 class="text-xl font-semibold text-gray-800">Income Sources</h2>
        </div>
    </x-slot>

    <x-flash />
    <x-input-error class="mb-4" :messages="$errors->get('source')" />

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Add Income Source</h3>
            <form method="POST" action="{{ route('sources.store') }}" class="flex items-start gap-3">
                @csrf
                <div class="flex-1">
                    <x-text-input name="name" type="text" class="w-full" placeholder="e.g. Freelance" value="{{ old('name') }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <x-primary-button>Add</x-primary-button>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Used by</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sources as $source)
                        <tr>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('sources.update', $source) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <x-text-input name="name" type="text" value="{{ $source->name }}" class="w-48" required />
                                    <button type="submit" class="text-xs font-medium text-emerald-700 hover:underline">Save</button>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $source->incomes_count }} entr(ies)</td>
                            <td class="px-5 py-3 text-right">
                                @if ($source->incomes_count === 0)
                                    <form method="POST" action="{{ route('sources.destroy', $source) }}"
                                        onsubmit="return confirm('Delete this source?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400" title="In use">In use</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-gray-500">No income sources yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
