<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Settings</a>
            <h2 class="text-xl font-semibold text-gray-800">API Access</h2>
        </div>
    </x-slot>

    <x-flash />

    <div class="mx-auto max-w-2xl space-y-6">
        {{-- One-time secret reveal --}}
        @if (session('new_api_secret'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                <h3 class="text-sm font-semibold text-amber-800">Copy your secret now — it won't be shown again</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-amber-700">API Key</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800">{{ session('new_api_key') }}</dd>
                    </div>
                    <div>
                        <dt class="text-amber-700">Security Key (secret)</dt>
                        <dd class="mt-1 break-all rounded bg-white px-3 py-2 font-mono text-gray-800">{{ session('new_api_secret') }}</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-amber-700">
                    Send them as <code>X-Api-Key</code> and <code>X-Api-Secret</code> headers on every request.
                </p>
            </div>
        @endif

        {{-- Create --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">Create API Key</h3>
            <form method="POST" action="{{ route('api-keys.store') }}" class="flex items-start gap-3">
                @csrf
                <div class="flex-1">
                    <x-text-input name="name" type="text" class="w-full" placeholder="e.g. Mobile app, Zapier" value="{{ old('name') }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <x-primary-button>Generate</x-primary-button>
            </form>
        </div>

        {{-- List --}}
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Key</th>
                        <th class="px-5 py-3">Last Used</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($credentials as $credential)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $credential->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $credential->key }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $credential->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('api-keys.destroy', $credential) }}"
                                    onsubmit="return confirm('Delete this key? Apps using it will stop working.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">No API keys yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-gray-50 p-4 text-xs text-gray-600">
            <p class="font-semibold text-gray-700">Quick start</p>
            <pre class="mt-2 overflow-x-auto rounded bg-gray-900 p-3 text-gray-100">curl {{ url('/api/v1/dashboard') }} \
  -H "X-Api-Key: YOUR_KEY" \
  -H "X-Api-Secret: YOUR_SECRET" \
  -H "Accept: application/json"</pre>
        </div>
    </div>
</x-app-layout>
