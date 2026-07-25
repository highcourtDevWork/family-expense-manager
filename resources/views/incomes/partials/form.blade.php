{{--
    Shared income form. Expects:
      $action   - the form action URL
      $method   - 'POST' (create) or 'PUT' (update)
      $income   - Income|null (null when creating)
      $sources  - Collection of IncomeSource
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="income_date" value="Date" />
            <x-text-input id="income_date" name="income_date" type="date" class="mt-1"
                value="{{ old('income_date', $income?->income_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                required />
            <x-input-error class="mt-2" :messages="$errors->get('income_date')" />
        </div>

        <div>
            <x-input-label for="amount" value="Amount (₹)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('amount', $income?->amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="income_source_id" value="Income Source" />
            <x-select id="income_source_id" name="income_source_id" class="mt-1" required>
                <option value="">— Select source —</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->id }}"
                        @selected((int) old('income_source_id', $income?->income_source_id) === $source->id)>
                        {{ $source->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('income_source_id')" />
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Notes (optional)" />
        <x-text-input id="notes" name="notes" type="text" class="mt-1"
            value="{{ old('notes', $income?->notes) }}" maxlength="1000" />
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('incomes.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $income ? 'Update Income' : 'Save Income' }}</x-primary-button>
    </div>
</form>
