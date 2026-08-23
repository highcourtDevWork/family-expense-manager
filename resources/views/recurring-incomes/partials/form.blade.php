{{--
    Shared recurring-income template form. Expects:
      $action, $method, $template (nullable), $members, $sources
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" value="Income Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1" placeholder="e.g. Kumar's Salary"
            value="{{ old('name', $template?->name) }}" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="user_id" value="Earned by" />
            <x-select id="user_id" name="user_id" class="mt-1" required>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}"
                        @selected((int) old('user_id', $template?->user_id ?? auth()->id()) === $member->id)>
                        {{ $member->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
        </div>

        <div>
            <x-input-label for="amount" value="Monthly Amount (₹)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1"
                value="{{ old('amount', $template?->amount) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="income_source_id" value="Source (optional)" />
            <x-select id="income_source_id" name="income_source_id" class="mt-1">
                <option value="">— Salary —</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->id }}"
                        @selected((int) old('income_source_id', $template?->income_source_id) === $source->id)>
                        {{ $source->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error class="mt-2" :messages="$errors->get('income_source_id')" />
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
        <a href="{{ route('recurring-incomes.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
        <x-primary-button>{{ $template ? 'Update Template' : 'Save Template' }}</x-primary-button>
    </div>
</form>
