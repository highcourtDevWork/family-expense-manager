@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700']) }}>
        {{ $status }}
    </div>
@endif
