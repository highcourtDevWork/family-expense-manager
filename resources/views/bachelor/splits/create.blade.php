<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Add Shared Expense</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow-sm sm:p-8">
        <p class="mb-6 text-sm text-gray-600">
            One roommate pays the full amount; it's split equally among all {{ $members->count() }} members.
        </p>

        <form method="POST" action="{{ route('bachelor.splits.store') }}" class="space-y-6">
            @csrf

            <div>
                <x-input-label for="title" value="What was it for?" />
                <x-text-input id="title" name="title" type="text" class="mt-1 w-full" placeholder="e.g. Room rent, Electricity"
                    value="{{ old('title') }}" required autofocus />
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="total_amount" value="Total Amount (₹)" />
                    <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" min="0.01" class="mt-1 w-full"
                        value="{{ old('total_amount') }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('total_amount')" />
                </div>

                <div>
                    <x-input-label for="period" value="Month" />
                    <x-text-input id="period" name="period" type="date" class="mt-1 w-full"
                        value="{{ old('period', now()->startOfMonth()->format('Y-m-d')) }}" required />
                    <x-input-error class="mt-2" :messages="$errors->get('period')" />
                </div>

                <div>
                    <x-input-label for="paid_by" value="Paid by" />
                    <x-select id="paid_by" name="paid_by" class="mt-1 w-full" required>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected((int) old('paid_by', auth()->id()) === $member->id)>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('paid_by')" />
                </div>

                <div>
                    <x-input-label for="expense_category_id" value="Category (optional)" />
                    <x-select id="expense_category_id" name="expense_category_id" class="mt-1 w-full">
                        <option value="">— None —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('expense_category_id') === $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('expense_category_id')" />
                </div>
            </div>

            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-text-input id="notes" name="notes" type="text" class="mt-1 w-full" value="{{ old('notes') }}" maxlength="1000" />
                <x-input-error class="mt-2" :messages="$errors->get('notes')" />
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('bachelor.splits.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                <x-primary-button>Record &amp; Split</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
