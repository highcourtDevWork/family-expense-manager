@props(['status'])

@php
    $isClosed = $status === \App\Enums\LoanStatus::Closed;
@endphp

<span @class([
    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-emerald-50 text-emerald-700' => $isClosed,
    'bg-amber-50 text-amber-700' => ! $isClosed,
])>
    {{ $status->label() }}
</span>
