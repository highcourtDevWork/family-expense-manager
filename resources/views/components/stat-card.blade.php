@props([
    'label',
    'value',
    'tone' => 'emerald',
    'icon' => null,
    'href' => null,
])

@php
    $tones = [
        'emerald' => ['text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'iconText' => 'text-emerald-600'],
        'red' => ['text' => 'text-red-500', 'bg' => 'bg-red-50', 'iconText' => 'text-red-500'],
        'amber' => ['text' => 'text-amber-600', 'bg' => 'bg-amber-50', 'iconText' => 'text-amber-600'],
        'gray' => ['text' => 'text-gray-700', 'bg' => 'bg-gray-100', 'iconText' => 'text-gray-600'],
    ];
    $t = $tones[$tone] ?? $tones['emerald'];

    // Minimal inline icon set (stroke-based), keyed by name.
    $icons = [
        'arrow-down' => 'M12 5v14M5 12l7 7 7-7',
        'arrow-up' => 'M12 19V5M5 12l7-7 7 7',
        'scale' => 'M12 3v18M5 7h14M7 7l-3 6a3 3 0 0 0 6 0L7 7zm10 0l-3 6a3 3 0 0 0 6 0l-3-6z',
        'bank' => 'M3 10l9-6 9 6M4 10v9h16v-9M9 19v-5h6v5',
    ];
    $path = $icon ? ($icons[$icon] ?? null) : null;
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm'.($href ? ' transition hover:shadow-md' : '')]) }}>
    @if ($path)
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $t['bg'] }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $t['iconText'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
            </svg>
        </span>
    @endif
    <div class="min-w-0">
        <p class="truncate text-sm font-medium text-gray-500">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold {{ $t['text'] }}">₹{{ number_format((float) $value, 2) }}</p>
    </div>
</{{ $tag }}>
