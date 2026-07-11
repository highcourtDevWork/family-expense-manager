<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="$status" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" name="password" type="password" class="mt-1" required autocomplete="current-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div class="mt-4 flex items-center">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
            <label for="remember_me" class="ms-2 text-sm text-gray-600">Remember me</label>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <a href="{{ route('password.request') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
                Forgot your password?
            </a>

            <x-primary-button>Log in</x-primary-button>
        </div>

        <p class="mt-6 text-center text-sm text-gray-600">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-emerald-700 underline hover:text-emerald-900">Register</a>
        </p>
    </form>
</x-guest-layout>
