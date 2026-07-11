<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Profile
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-8">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
