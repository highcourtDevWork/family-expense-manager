<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Import Google Pay Statement</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm text-gray-600">
                Upload the transaction statement PDF exported from Google Pay. We'll read it and
                turn each <strong>Paid to</strong> row into an expense and each
                <strong>Received from</strong> row into income. Self-transfers are ignored, and
                you'll get a chance to review everything before anything is saved.
            </p>

            <x-input-error class="mt-4" :messages="$errors->get('statement')" />

            <form method="POST" action="{{ route('imports.parse') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                @csrf
                <div>
                    <x-input-label for="statement" value="Statement PDF (max 10MB)" />
                    <input id="statement" name="statement" type="file" accept="application/pdf,.pdf" required
                        class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Read Statement</x-primary-button>
                </div>
            </form>
        </div>

        <div class="mt-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
            <strong>Heads up:</strong> imported expenses are auto-categorised by a best guess
            (e.g. anything with "hospital" → Medical). You can re-categorise any of them afterward
            from the Expenses page.
        </div>
    </div>
</x-app-layout>
