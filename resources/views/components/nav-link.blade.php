@props([
    'href',
    'active' => false,
    'icon' => null,
])

@php
    $icons = [
        'dashboard' => 'M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6',
        'expenses' => 'M3 6h18M3 12h18M3 18h12',
        'income' => 'M12 3v14m0 0l-5-5m5 5l5-5M5 21h14',
        'loans' => 'M3 10l9-6 9 6M4 10v9h16v-9M9 19v-5h6v5',
        'family' => 'M17 20h5v-1a4 4 0 0 0-3-3.87M9 20H4v-1a4 4 0 0 1 3-3.87m6-1a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'import' => 'M12 15V3m0 12l-4-4m4 4l4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2',
        'debts' => 'M17 9V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2m2-6h10a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2zm5 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z',
        'savings' => 'M4 7h16M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2M4 7v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7M9 12h6',
        'settings' => 'M10.3 4.3a1 1 0 0 1 3.4 0l.2.9a1 1 0 0 0 1.4.8l.8-.4a1 1 0 0 1 1.3 1.4l-.4.8a1 1 0 0 0 .8 1.4l.9.2a1 1 0 0 1 0 3.4l-.9.2a1 1 0 0 0-.8 1.4l.4.8a1 1 0 0 1-1.3 1.3l-.8-.4a1 1 0 0 0-1.4.8l-.2.9a1 1 0 0 1-3.4 0l-.2-.9a1 1 0 0 0-1.4-.8l-.8.4a1 1 0 0 1-1.3-1.3l.4-.8a1 1 0 0 0-.8-1.4l-.9-.2a1 1 0 0 1 0-3.4l.9-.2a1 1 0 0 0 .8-1.4l-.4-.8a1 1 0 0 1 1.3-1.4l.8.4a1 1 0 0 0 1.4-.8zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'reports' => 'M9 17v-6m4 6V7m4 10v-3M4 4v16h16',
        'savings-rate' => 'M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6z M13.5 3a7.5 7.5 0 0 1 7.5 7.5h-7.5V3z',
    ];
    $path = $icon ? ($icons[$icon] ?? null) : null;
@endphp

<a href="{{ $href }}"
    {{ $attributes->class([
        'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
        'bg-emerald-600 text-white shadow-sm' => $active,
        'text-gray-600 hover:bg-emerald-50 hover:text-emerald-700' => ! $active,
    ]) }}>
    @if ($path)
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
        </svg>
    @endif
    <span>{{ $slot }}</span>
</a>
