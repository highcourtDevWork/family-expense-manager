{{--
    Shared expense form. Expects:
      $action         - the form action URL
      $method         - 'POST' (create) or 'PUT' (update)
      $expense        - Expense|null (null when creating)
      $categories     - Collection of ExpenseCategory
      $paymentMethods - array<string, string> value => label
--}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="expense_date" value="Date" />
            <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1"
                value="{{ old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                required />
            <x-input-error class="mt-2" :messages="$errors->get('expense_date')" />
        </div>

        <div>
            <x-input-label for="amount" value="Amount (₹)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('amount', $expense?->amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>

        <div>
            <x-input-label for="expense_category_id" value="Category" />
            <x-select id="expense_category_id" name="expense_category_id" class="mt-1" required>
                <option value="">— Select category —</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        @selected((int) old('expense_category_id', $expense?->expense_category_id) === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('expense_category_id')" />
        </div>

        <div>
            <x-input-label for="payment_method" value="Payment Method" />
            <x-select id="payment_method" name="payment_method" class="mt-1" required>
                @foreach ($paymentMethods as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('payment_method', $expense?->payment_method?->value) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Description (optional)" />
        <x-text-input id="description" name="description" type="text" class="mt-1"
            value="{{ old('description', $expense?->description) }}" maxlength="1000" />
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div>
        <x-input-label for="attachment" value="Receipt (optional — JPG, PNG or PDF, max 2MB)" />
        <input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf"
            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
        <x-input-error class="mt-2" :messages="$errors->get('attachment')" />

        @if ($expense?->attachment_path)
            <p class="mt-2 text-sm text-gray-600">
                Current:
                <a href="{{ Storage::url($expense->attachment_path) }}" target="_blank"
                    class="text-emerald-700 underline">view receipt</a>
                — uploading a new file replaces it.
            </p>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('expenses.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $expense ? 'Update Expense' : 'Save Expense' }}</x-primary-button>
    </div>
</form>
