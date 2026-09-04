@props(['unread' => 0])

@php
    // Primary destinations live in the tab bar; everything else goes in "More".
    // icon = an SVG path drawn in a 24x24 viewBox with a 1.8 stroke.
    $tabs = [
        ['label' => 'Home', 'route' => 'dashboard', 'pattern' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6'],
        ['label' => 'Expenses', 'route' => 'expenses.index', 'pattern' => 'expenses.*', 'icon' => 'M3 6h18M3 12h18M3 18h12'],
        ['label' => 'Income', 'route' => 'incomes.index', 'pattern' => 'incomes.*', 'icon' => 'M12 3v14m0 0l-5-5m5 5l5-5M5 21h14'],
        ['label' => 'Reports', 'route' => 'reports.index', 'pattern' => 'reports.*', 'icon' => 'M9 17v-6m4 6V7m4 10v-3M4 4v16h16'],
    ];

    $moreItems = [
        ['label' => 'Loans', 'route' => 'loans.index', 'pattern' => 'loans.*', 'icon' => 'M3 10l9-6 9 6M4 10v9h16v-9M9 19v-5h6v5'],
        ['label' => 'Debts', 'route' => 'debts.index', 'pattern' => 'debts.*', 'icon' => 'M17 9V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2m2-6h10a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z'],
        ['label' => 'Savings', 'route' => 'savings.index', 'pattern' => 'savings.*', 'icon' => 'M4 7h16M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2M4 7v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7M9 12h6'],
        ['label' => 'Family', 'route' => 'family.index', 'pattern' => 'family.*', 'icon' => 'M17 20h5v-1a4 4 0 0 0-3-3.87M9 20H4v-1a4 4 0 0 1 3-3.87m6-1a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
        ['label' => 'Import', 'route' => 'imports.create', 'pattern' => 'imports.*', 'icon' => 'M12 15V3m0 12l-4-4m4 4l4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2'],
        ['label' => 'Settings', 'route' => 'settings.index', 'pattern' => 'settings.*', 'icon' => 'M10.3 4.3a1 1 0 0 1 3.4 0l.2.9a1 1 0 0 0 1.4.8l.8-.4a1 1 0 0 1 1.3 1.4l-.4.8a1 1 0 0 0 .8 1.4l.9.2a1 1 0 0 1 0 3.4l-.9.2a1 1 0 0 0-.8 1.4l.4.8a1 1 0 0 1-1.3 1.3l-.8-.4a1 1 0 0 0-1.4.8l-.2.9a1 1 0 0 1-3.4 0l-.2-.9a1 1 0 0 0-1.4-.8l-.8.4a1 1 0 0 1-1.3-1.3l.4-.8a1 1 0 0 0-.8-1.4l-.9-.2a1 1 0 0 1 0-3.4l.9-.2a1 1 0 0 0 .8-1.4l-.4-.8a1 1 0 0 1 1.3-1.4l.8.4a1 1 0 0 0 1.4-.8zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
    ];

    $moreActive = request()->routeIs('loans.*', 'debts.*', 'savings.*', 'family.*', 'imports.*', 'settings.*', 'notifications.*', 'profile.*', 'recurring.*', 'recurring-incomes.*', 'api-keys.*');
@endphp

<div x-data="{ moreOpen: false }" class="lg:hidden">
    {{-- Slide-up "More" sheet --}}
    <div x-show="moreOpen" x-cloak x-transition.opacity @click="moreOpen = false"
        class="fixed inset-0 z-40 bg-gray-900/40"></div>

    <div x-show="moreOpen" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
        class="pb-safe fixed inset-x-0 bottom-0 z-50 rounded-t-2xl bg-white shadow-2xl">
        <div class="mx-auto mt-2 h-1.5 w-10 rounded-full bg-gray-300"></div>
        <div class="flex items-center justify-between px-5 pb-1 pt-3">
            <h2 class="text-base font-semibold text-gray-800">More</h2>
            <button type="button" @click="moreOpen = false" class="tap-active -mr-2 p-2 text-gray-400" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-3 gap-1 px-3 pb-2">
            @foreach ($moreItems as $item)
                @php $active = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}"
                    class="tap-active flex flex-col items-center gap-1.5 rounded-xl px-2 py-3 {{ $active ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                    <span class="text-xs font-medium">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="border-t border-gray-100 px-3 py-2">
            <a href="{{ route('profile.edit') }}" class="tap-active flex items-center gap-3 rounded-xl px-3 py-2.5 text-gray-700">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-700">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
                <span class="text-sm font-medium">{{ auth()->user()->name }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="tap-active flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7M13 16v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1" />
                    </svg>
                    Log Out
                </button>
            </form>
        </div>
    </div>

    {{-- Fixed bottom tab bar --}}
    <nav class="bottom-nav pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white">
        <div class="grid grid-cols-5">
            @foreach ($tabs as $tab)
                @php $active = request()->routeIs($tab['pattern']); @endphp
                <a href="{{ route($tab['route']) }}"
                    class="tap-active flex flex-col items-center gap-0.5 py-2 {{ $active ? 'text-emerald-600' : 'text-gray-500' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $active ? '2.2' : '1.8' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                    <span class="text-[11px] font-medium leading-none">{{ $tab['label'] }}</span>
                </a>
            @endforeach

            {{-- More --}}
            <button type="button" @click="moreOpen = true"
                class="tap-active flex flex-col items-center gap-0.5 py-2 {{ $moreActive ? 'text-emerald-600' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $moreActive ? '2.2' : '1.8' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span class="text-[11px] font-medium leading-none">More</span>
            </button>
        </div>
    </nav>
</div>
