<x-admin::layouts.anonymous>
    <x-slot:title>
        {{ $directory->name }} · @lang('dam::app.share.public.app-name')
    </x-slot:title>

    <div class="min-h-screen bg-gray-50 dark:bg-cherry-950 flex flex-col">
        <header class="bg-white dark:bg-cherry-900 border-b border-gray-200 dark:border-cherry-800 px-6 py-4">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="icon-dam-folder text-2xl text-gray-500 dark:text-slate-400"></span>
                    <h1 class="text-lg font-semibold text-zinc-900 dark:text-slate-50 truncate">
                        {{ $directory->name }}
                    </h1>
                    <span class="text-sm text-gray-500 dark:text-slate-400 ml-2">
                        @lang('dam::app.share.public.files-count', ['count' => $assets->count()])
                    </span>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-8">
            @if ($assets->isEmpty())
                <div class="bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 p-12 text-center">
                    <p class="text-gray-500 dark:text-slate-300">
                        @lang('dam::app.share.public.empty-directory')
                    </p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach ($assets as $asset)
                        @php
                            $thumbnailUrl = route('dam.share.thumbnail', ['token' => $share->token, 'assetId' => $asset->id]);
                            $viewUrl = route('dam.share.asset_view', ['token' => $share->token, 'assetId' => $asset->id]);
                        @endphp
                        <a
                            href="{{ $viewUrl }}"
                            class="group bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden hover:border-blue-400 transition"
                        >
                            <div class="aspect-square bg-gray-100 dark:bg-cherry-800 flex items-center justify-center overflow-hidden">
                                <img
                                    src="{{ $thumbnailUrl }}"
                                    alt="{{ $asset->file_name }}"
                                    loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-105 transition"
                                />
                            </div>
                            <div class="px-3 py-2">
                                <p class="text-sm text-zinc-800 dark:text-slate-100 truncate" title="{{ $asset->file_name }}">
                                    {{ $asset->file_name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ $asset->file_size ? \Illuminate\Support\Number::fileSize($asset->file_size, precision: 1) : '' }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($share->expires_at)
                <p class="mt-6 text-xs text-gray-500 dark:text-slate-400 text-center">
                    @lang('dam::app.share.public.expires-on') {{ $share->expires_at->toDayDateTimeString() }}
                </p>
            @endif
        </main>

        <footer class="text-center text-xs text-gray-400 dark:text-slate-500 py-4">
            @lang('dam::app.share.public.powered-by-dam')
        </footer>
    </div>
</x-admin::layouts.anonymous>
