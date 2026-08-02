{{--
    Shared loan form. Expects:
      $action     - the form action URL
      $method     - 'POST' (create) or 'PUT' (update)
      $loan       - Loan|null (null when creating)
      $loanTypes  - array<string, string> value => label
--}}
@php $emiLocked = $loan && $loan->is_emi && $loan->emis()->where('status', \App\Enums\EmiStatus::Paid)->exists(); @endphp

<form method="POST" action="{{ $action }}" class="space-y-6"
    x-data="{ isEmi: {{ old('is_emi', $loan?->is_emi) ? 'true' : 'false' }} }">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="loan_name" value="Loan Name" />
        <x-text-input id="loan_name" name="loan_name" type="text" class="mt-1"
            value="{{ old('loan_name', $loan?->loan_name) }}" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('loan_name')" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="loan_type" value="Loan Type" />
            <x-select id="loan_type" name="loan_type" class="mt-1" required>
                @foreach ($loanTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('loan_type', $loan?->loan_type?->value) === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('loan_type')" />
        </div>

        <div>
            <x-input-label for="total_amount" value="Total Loan Amount / Principal (₹)" />
            <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('total_amount', $loan?->total_amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('total_amount')" />
        </div>

        <div>
            <x-input-label for="start_date" value="Start Date" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1"
                value="{{ old('start_date', $loan?->start_date?->format('Y-m-d')) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
        </div>

        <div>
            <x-input-label for="end_date" value="End Date (optional)" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1"
                value="{{ old('end_date', $loan?->end_date?->format('Y-m-d')) }}" />
            <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
        </div>
    </div>

    {{-- EMI configuration --}}
    <div class="rounded-lg border border-gray-200 p-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_emi" value="1" x-model="isEmi" @disabled($emiLocked)
                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
            <span class="text-sm font-medium text-gray-700">EMI-based loan (fixed monthly installments)</span>
        </label>

        @if ($emiLocked)
            <p class="mt-2 text-xs text-amber-600">Repayment has started — the EMI schedule is locked and can't be changed.</p>
        @endif

        <div x-show="isEmi" x-cloak class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="tenure_months" value="Tenure (months)" />
                <x-text-input id="tenure_months" name="tenure_months" type="number" min="1" max="600" class="mt-1"
                    :disabled="$emiLocked" value="{{ old('tenure_months', $loan?->tenure_months) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('tenure_months')" />
            </div>

            <div>
                <x-input-label for="interest_rate" value="Interest Rate (% per year)" />
                <x-text-input id="interest_rate" name="interest_rate" type="number" step="0.01" min="0" max="100" class="mt-1"
                    :disabled="$emiLocked" value="{{ old('interest_rate', $loan?->interest_rate) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('interest_rate')" />
            </div>

            <div>
                <x-input-label for="emi_amount" value="Monthly EMI (₹) — leave blank to auto-calculate" />
                <x-text-input id="emi_amount" name="emi_amount" type="number" step="0.01" min="0.01" class="mt-1"
                    :disabled="$emiLocked" value="{{ old('emi_amount', $loan?->emi_amount) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('emi_amount')" />
            </div>

            <div>
                <x-input-label for="emi_due_day" value="EMI Due Day (1–28)" />
                <x-text-input id="emi_due_day" name="emi_due_day" type="number" min="1" max="28" class="mt-1"
                    :disabled="$emiLocked" value="{{ old('emi_due_day', $loan?->emi_due_day) }}" />
                <x-input-error class="mt-2" :messages="$errors->get('emi_due_day')" />
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $loan ? route('loans.show', $loan) : route('loans.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $loan ? 'Update Loan' : 'Create Loan' }}</x-primary-button>
    </div>
</form>
