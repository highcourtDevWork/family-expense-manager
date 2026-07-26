{{--
    Shared debt form. Expects:
      $action    - the form action URL
      $method    - 'POST' (create) or 'PUT' (update)
      $debt      - Debt|null (null when creating)
      $debtTypes - array<string, string> value => label
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="debt_type" value="Type" />
            <x-select id="debt_type" name="debt_type" class="mt-1" required>
                @foreach ($debtTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('debt_type', $debt?->debt_type?->value) === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('debt_type')" />
            <p class="mt-1 text-xs text-gray-500">"Borrowed" = you owe them. "Lent" = they owe you.</p>
        </div>

        <div>
            <x-input-label for="person_name" value="Person" />
            <x-text-input id="person_name" name="person_name" type="text" class="mt-1"
                value="{{ old('person_name', $debt?->person_name) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('person_name')" />
        </div>

        <div>
            <x-input-label for="amount" value="Amount (₹)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('amount', $debt?->amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
            @if ($debt && (float) $debt->paid_amount > 0)
                <p class="mt-1 text-xs text-gray-500">
                    ₹{{ number_format((float) $debt->paid_amount, 2) }} already settled — balances recalculate on save.
                </p>
            @endif
        </div>

        <div>
            <x-input-label for="date" value="Date" />
            <x-text-input id="date" name="date" type="date" class="mt-1"
                value="{{ old('date', $debt?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('date')" />
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Notes (optional)" />
        <x-text-input id="notes" name="notes" type="text" class="mt-1" value="{{ old('notes', $debt?->notes) }}" maxlength="1000" />
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $debt ? route('debts.show', $debt) : route('debts.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $debt ? 'Update Debt' : 'Add Debt' }}</x-primary-button>
    </div>
</form>
