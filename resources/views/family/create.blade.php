<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Add Family Member</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        <p class="mb-6 text-sm text-gray-600">
            This creates a login account for the member. Share the email and password with
            them — they can change the password from their profile after signing in.
        </p>

        <form method="POST" action="{{ route('family.store') }}" class="space-y-6">
            @csrf

            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" type="text" class="mt-1" value="{{ old('name') }}" required autofocus />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1" value="{{ old('email') }}" required />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="relationship" value="Relationship" />
                    <x-select id="relationship" name="relationship" class="mt-1" required>
                        @foreach ($relationships as $value => $label)
                            <option value="{{ $value }}" @selected(old('relationship') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('relationship')" />
                </div>

                <div>
                    <x-input-label for="role" value="Role" />
                    <x-select id="role" name="role" class="mt-1" required>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'member') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('role')" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="password" value="Initial Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1" required autocomplete="new-password" />
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" />
                    <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('family.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                <x-primary-button>Add Member</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
