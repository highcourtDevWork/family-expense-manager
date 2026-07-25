{{--
    Shared loan form. Expects:
      $action     - the form action URL
      $method     - 'POST' (create) or 'PUT' (update)
      $loan       - Loan|null (null when creating)
      $loanTypes  - array<string, string> value => label
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
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
            <x-input-label for="total_amount" value="Total Loan Amount (₹)" />
            <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('total_amount', $loan?->total_amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('total_amount')" />
            @if ($loan && (float) $loan->paid_amount > 0)
                <p class="mt-1 text-xs text-gray-500">
                    ₹{{ number_format((float) $loan->paid_amount, 2) }} already repaid — balances recalculate on save.
                </p>
            @endif
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

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $loan ? route('loans.show', $loan) : route('loans.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $loan ? 'Update Loan' : 'Create Loan' }}</x-primary-button>
    </div>
</form>
