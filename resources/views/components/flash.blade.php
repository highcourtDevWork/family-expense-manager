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
        'debt-created' => 'Debt added successfully.',
        'debt-updated' => 'Debt updated successfully.',
        'debt-deleted' => 'Debt deleted successfully.',
        'saving-created' => 'Saving added successfully.',
        'saving-updated' => 'Saving updated successfully.',
        'saving-deleted' => 'Saving deleted successfully.',
        'category-created' => 'Category added successfully.',
        'category-updated' => 'Category updated successfully.',
        'category-deleted' => 'Category deleted successfully.',
        'source-created' => 'Income source added successfully.',
        'source-updated' => 'Income source updated successfully.',
        'source-deleted' => 'Income source deleted successfully.',
        'payment-added' => 'Payment recorded successfully.',
        'payment-deleted' => 'Payment removed.',
        'emi-paid' => 'EMI installment marked as paid.',
        'emi-unpaid' => 'EMI installment reverted to pending.',
        'recurring-created' => 'Recurring expense saved.',
        'recurring-updated' => 'Recurring expense updated.',
        'recurring-deleted' => 'Recurring expense removed.',
        'recurring-posted' => 'Added to this month\'s expenses.',
        'recurring-already-posted' => 'This expense was already added this month.',
        'recurring-income-created' => 'Recurring income saved.',
        'recurring-income-updated' => 'Recurring income updated.',
        'recurring-income-deleted' => 'Recurring income removed.',
        'recurring-income-posted' => 'Added to this month\'s income.',
        'recurring-income-already-posted' => 'This income was already added this month.',
        'recurring-saving-created' => 'Recurring saving saved.',
        'recurring-saving-updated' => 'Recurring saving updated.',
        'recurring-saving-deleted' => 'Recurring saving removed.',
        'recurring-saving-posted' => 'Added to this month\'s savings.',
        'recurring-saving-already-posted' => 'This saving was already added this month.',
        'member-added' => 'Roommate added — they can now log in.',
        'member-removed' => 'Roommate removed from this account.',
        'split-recorded' => 'Shared expense recorded and split.',
        'share-settled' => 'Marked as settled.',
        'share-unsettled' => 'Marked as unsettled.',
        'api-key-deleted' => 'API key deleted.',
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
