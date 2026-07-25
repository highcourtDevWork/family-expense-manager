<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit {{ $member->user->name }}</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        <p class="mb-6 text-sm text-gray-600">
            Editing family membership for <span class="font-medium text-gray-900">{{ $member->user->email }}</span>.
            Their name and password are theirs to change from their own profile.
        </p>

        <form method="POST" action="{{ route('family.update', $member) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="relationship" value="Relationship" />
                    <x-select id="relationship" name="relationship" class="mt-1" required>
                        @foreach ($relationships as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('relationship', $member->relationship?->value) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('relationship')" />
                </div>

                <div>
                    <x-input-label for="role" value="Role" />
                    <x-select id="role" name="role" class="mt-1" required>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $member->role->value) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('role')" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('family.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
