{{--
    Shared saving form. Expects:
      $action      - the form action URL
      $method      - 'POST' (create) or 'PUT' (update)
      $saving      - Saving|null (null when creating)
      $savingTypes - array<string, string> value => label
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="saving_type" value="Type" />
            <x-select id="saving_type" name="saving_type" class="mt-1" required>
                @foreach ($savingTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('saving_type', $saving?->saving_type?->value) === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('saving_type')" />
        </div>

        <div>
            <x-input-label for="amount" value="Amount (₹)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('amount', $saving?->amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>

        <div>
            <x-input-label for="date" value="Date" />
            <x-text-input id="date" name="date" type="date" class="mt-1"
                value="{{ old('date', $saving?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('date')" />
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Notes (optional)" />
        <x-text-input id="notes" name="notes" type="text" class="mt-1" value="{{ old('notes', $saving?->notes) }}" maxlength="1000" />
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('savings.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $saving ? 'Update Saving' : 'Add Saving' }}</x-primary-button>
    </div>
</form>
