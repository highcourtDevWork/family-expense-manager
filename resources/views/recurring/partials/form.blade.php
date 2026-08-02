{{--
    Shared recurring-expense template form. Expects:
      $action, $method, $template (nullable), $categories, $paymentMethods
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="Expense Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1" placeholder="e.g. House Rent"
            value="{{ old('name', $template?->name) }}" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="amount" value="Fixed Amount (₹)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('amount', $template?->amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>

        <div>
            <x-input-label for="expense_category_id" value="Category (optional)" />
            <x-select id="expense_category_id" name="expense_category_id" class="mt-1">
                <option value="">— Others —</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        @selected((int) old('expense_category_id', $template?->expense_category_id) === $category->id)>
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
                    <option value="{{ $value }}" @selected(old('payment_method', $template?->payment_method?->value ?? 'cash') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
        </div>
    </div>

    <div class="space-y-2">
        <label class="flex items-center gap-2">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1"
                @checked(old('active', $template?->active ?? true))
                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
            <span class="text-sm text-gray-700">Active</span>
        </label>
        <label class="flex items-center gap-2">
            <input type="hidden" name="auto_post" value="0">
            <input type="checkbox" name="auto_post" value="1"
                @checked(old('auto_post', $template?->auto_post ?? false))
                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
            <span class="text-sm text-gray-700">Auto-create on the 1st of every month</span>
        </label>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('recurring.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $template ? 'Update Template' : 'Save Template' }}</x-primary-button>
    </div>
</form>
