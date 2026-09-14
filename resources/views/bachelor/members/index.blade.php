<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Roommates</h2>
    </x-slot>

    <x-flash />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Member list --}}
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($members as $member)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-800">
                                    {{ $member->name }}
                                    @if ($member->id === auth()->id())
                                        <span class="ml-1 text-xs text-gray-400">(you)</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $member->email }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                        {{ ucfirst($member->pivot->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if ($member->id !== auth()->id())
                                        <form method="POST" action="{{ route('bachelor.members.destroy', $member) }}"
                                            onsubmit="return confirm('Remove this roommate from the account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add member --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-gray-800">Add a roommate</h3>
            <p class="mt-1 text-xs text-gray-500">They get their own login and can record and settle shared expenses.</p>

            <form method="POST" action="{{ route('bachelor.members.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 w-full" value="{{ old('name') }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 w-full" value="{{ old('email') }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                </div>
                <div>
                    <x-input-label for="password" value="Temporary Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 w-full" required autocomplete="new-password" />
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 w-full" required autocomplete="new-password" />
                </div>
                <x-primary-button class="w-full justify-center">Add roommate</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
