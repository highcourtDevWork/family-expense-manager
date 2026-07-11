<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Forgot your password? No problem. Enter your email address and we'll send you a link to reset it.
    </div>

    <x-auth-session-status class="mb-4" :status="$status" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" value="{{ old('email') }}" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-4 flex justify-end">
            <x-primary-button>Email Password Reset Link</x-primary-button>
        </div>
    </form>
</x-guest-layout>
