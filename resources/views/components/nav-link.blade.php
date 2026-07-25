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
