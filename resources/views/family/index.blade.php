<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $family->name }}</h2>
                <p class="text-sm text-gray-500">{{ $members->count() }} {{ Str::plural('member', $members->count()) }}</p>
            </div>
            @can('create', App\Models\FamilyMember::class)
                <a href="{{ route('family.create') }}"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    + Add Member
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash />

    <x-input-error class="mb-4" :messages="$errors->get('member')" />

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Relationship</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($members as $member)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">
                                {{ $member->user->name }}
                                @if ($member->user_id === auth()->id())
                                    <span class="ml-1 text-xs text-gray-400">(you)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $member->user->email }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $member->relationship?->label() ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700' => $member->role === App\Enums\FamilyRole::Admin,
                                    'bg-gray-100 text-gray-600' => $member->role !== App\Enums\FamilyRole::Admin,
                                ])>
                                    {{ $member->role->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @can('update', $member)
                                    <a href="{{ route('family.edit', $member) }}"
                                        class="text-emerald-700 hover:underline">Edit</a>
                                @endcan

                                @can('delete', $member)
                                    {{-- The creator and yourself cannot be removed; the service enforces
                                         this too, but hiding the button avoids a pointless error. --}}
                                    @if ($member->user_id !== $family->created_by && $member->user_id !== auth()->id())
                                        <form method="POST" action="{{ route('family.destroy', $member) }}"
                                            class="ml-3 inline"
                                            onsubmit="return confirm('Remove {{ $member->user->name }} from the family?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
