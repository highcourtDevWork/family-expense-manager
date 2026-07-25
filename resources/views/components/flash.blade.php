{{--
    Central flash-message banner. Controllers redirect with ->with('status', 'code');
    this component maps the code to a friendly message so every module stays
    consistent and the messages live in one place. Alpine lets it be dismissed.
--}}
@php
    $messages = [
        'expense-created' => 'Expense added successfully.',
        'expense-updated' => 'Expense updated successfully.',
        'expense-deleted' => 'Expense deleted successfully.',
        'income-created' => 'Income added successfully.',
        'income-updated' => 'Income updated successfully.',
        'income-deleted' => 'Income deleted successfully.',
        'member-added' => 'Family member added successfully.',
        'member-updated' => 'Family member updated successfully.',
        'member-removed' => 'Family member removed.',
        'loan-created' => 'Loan created successfully.',
        'loan-updated' => 'Loan updated successfully.',
        'loan-deleted' => 'Loan deleted successfully.',
        'payment-added' => 'Payment recorded successfully.',
        'payment-deleted' => 'Payment removed.',
        'profile-updated' => 'Profile updated.',
        'password-updated' => 'Password updated.',
    ];
    $code = session('status');
    $message = $code ? ($messages[$code] ?? $code) : null;
@endphp

@if ($message)
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-start justify-between gap-3 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-800">
        <span>{{ $message }}</span>
        <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700" aria-label="Dismiss">&times;</button>
    </div>
@endif
