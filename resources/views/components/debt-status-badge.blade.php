@props(['status'])

@php
    $isSettled = $status === \App\Enums\DebtStatus::Settled;
@endphp

<span @class([
    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-emerald-50 text-emerald-700' => $isSettled,
    'bg-amber-50 text-amber-700' => ! $isSettled,
])>
    {{ $status->label() }}
</span>
