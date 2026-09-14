<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1" value="{{ old('name') }}" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" value="{{ old('email') }}" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" name="password" type="password" class="mt-1" required autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="mt-6">
            <x-input-label value="What type of expenses do you want to manage?" />
            <div class="mt-2 space-y-2">
                @foreach (\App\Enums\AccountType::cases() as $type)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-300 p-3 transition hover:border-emerald-400 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50">
                        <input type="radio" name="account_type" value="{{ $type->value }}"
                            @checked(old('account_type', \App\Enums\AccountType::Family->value) === $type->value)
                            class="mt-0.5 border-gray-300 text-emerald-600 focus:ring-emerald-500" required />
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-800">{{ $type->label() }}</span>
                            <span class="block text-xs text-gray-500">{{ $type->tagline() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('account_type')" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
                Already registered?
            </a>

            <x-primary-button>Register</x-primary-button>
        </div>
    </form>
</x-guest-layout>
