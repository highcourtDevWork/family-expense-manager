<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Notifications</h2>
            @if ($notifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.destroy') }}"
                    onsubmit="return confirm('Clear all notifications?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-gray-500 hover:text-red-600">Clear all</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $notification->data['title'] ?? 'Notification' }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $notification->data['message'] ?? '' }}</p>
                    </div>
                    <span class="whitespace-nowrap text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-lg bg-white p-10 text-center text-gray-500 shadow-sm">
                You have no notifications.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</x-app-layout>
