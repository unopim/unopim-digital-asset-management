<div class="flex flex-wrap items-center justify-between gap-4 py-3">
    {{-- Per-page dropdown --}}
    <div class="flex items-center gap-2">
        <x-admin::dropdown>
            <x-slot:toggle>
                <button
                    type="button"
                    class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                >
                    <span>{{ $perPage }}</span>
                    <span class="icon-chevron-down text-2xl" aria-hidden="true"></span>
                </button>
            </x-slot>

            <x-slot:menu>
                @foreach ([50, 100, 150, 200, 250] as $option)
                    <x-admin::dropdown.menu.item>
                        <a
                            href="{{ $assets->appends(['per_page' => $option])->url(1) }}"
                            class="block w-full {{ $perPage === $option ? 'font-semibold text-violet-600 dark:text-violet-400' : '' }}"
                        >{{ $option }}</a>
                    </x-admin::dropdown.menu.item>
                @endforeach
            </x-slot>
        </x-admin::dropdown>

        <p class="whitespace-nowrap text-gray-600 dark:text-gray-300">
            @lang('dam::app.share.public.per-page')
        </p>
    </div>

    {{-- Navigation --}}
    <div class="flex items-center gap-x-2">
        <span class="whitespace-nowrap text-sm text-gray-600 dark:text-slate-300">
            {{ $assets->currentPage() }} / {{ $assets->lastPage() }}
        </span>

        <div class="flex items-center gap-1">
            {{-- First --}}
            @if ($assets->onFirstPage())
                <span class="inline-flex items-center px-1.5 py-1 rounded-md text-gray-300 dark:text-slate-600 cursor-not-allowed text-2xl">&#171;</span>
            @else
                <a href="{{ $assets->appends(['per_page' => $perPage])->url(1) }}" class="inline-flex items-center px-1.5 py-1 rounded-md text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-gray-800 transition text-2xl">&#171;</a>
            @endif

            {{-- Previous --}}
            @if ($assets->onFirstPage())
                <span class="inline-flex items-center p-1.5 rounded-md text-gray-300 dark:text-slate-600 cursor-not-allowed text-2xl">&#8249;</span>
            @else
                <a href="{{ $assets->appends(['per_page' => $perPage])->previousPageUrl() }}" class="inline-flex items-center p-1.5 rounded-md text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-gray-800 transition text-2xl">&#8249;</a>
            @endif

            {{-- Next --}}
            @if ($assets->hasMorePages())
                <a href="{{ $assets->appends(['per_page' => $perPage])->nextPageUrl() }}" class="inline-flex items-center p-1.5 rounded-md text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-gray-800 transition text-2xl">&#8250;</a>
            @else
                <span class="inline-flex items-center p-1.5 rounded-md text-gray-300 dark:text-slate-600 cursor-not-allowed text-2xl">&#8250;</span>
            @endif

            {{-- Last --}}
            @if ($assets->hasMorePages())
                <a href="{{ $assets->appends(['per_page' => $perPage])->url($assets->lastPage()) }}" class="inline-flex items-center px-1.5 py-1 rounded-md text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-gray-800 transition text-2xl">&#187;</a>
            @else
                <span class="inline-flex items-center px-1.5 py-1 rounded-md text-gray-300 dark:text-slate-600 cursor-not-allowed text-2xl">&#187;</span>
            @endif
        </div>
    </div>
</div>
