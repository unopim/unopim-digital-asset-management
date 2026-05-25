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

                <a
                    href="{{ route('dam.share.download_zip', $share->token) }}"
                    class="primary-button shrink-0"
                >
                    <span class="icon-dam-download text-lg"></span>
                    @lang('dam::app.share.public.download-zip')
                </a>
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
                            $thumbnailUrl   = route('dam.share.thumbnail', ['token' => $share->token, 'assetId' => $asset->id]);
                            $viewUrl        = route('dam.share.asset_view', ['token' => $share->token, 'assetId' => $asset->id]);
                            $placeholderSvg = match ($asset->file_type) {
                                'image'    => asset('storage/dam/grid/image.svg'),
                                'video'    => asset('storage/dam/grid/video.svg'),
                                'audio'    => asset('storage/dam/grid/audio.svg'),
                                'document' => asset('storage/dam/grid/file.svg'),
                                default    => asset('storage/dam/grid/unspecified.svg'),
                            };
                            $extension  = strtolower(pathinfo($asset->file_name, PATHINFO_EXTENSION));
                            $badgeColor = match (true) {
                                in_array($asset->file_type, ['video', 'audio']) => 'bg-violet-600',
                                $extension === 'pdf'                            => 'bg-red-600',
                                default                                         => 'bg-gray-600',
                            };
                        @endphp
                        <a
                            href="{{ $viewUrl }}"
                            class="group bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden hover:border-violet-400 dark:hover:border-violet-500 transition"
                        >
                            <div class="aspect-square overflow-hidden relative bg-gray-100 dark:bg-cherry-800">
                                {{--
                                    Fallback layer: file-type SVG + shimmer.
                                    z-0 keeps it behind the thumbnail (z-10).
                                    Removed by the onload handler once the real thumbnail renders.
                                --}}
                                <div class="absolute inset-0 z-0 flex items-center justify-center bg-gray-100 dark:bg-cherry-800">
                                    <img
                                        src="{{ $placeholderSvg }}"
                                        alt=""
                                        aria-hidden="true"
                                        class="w-20 h-20 object-contain opacity-60 dark:opacity-40 select-none pointer-events-none"
                                    />
                                    <div class="absolute inset-0 animate-pulse bg-gray-200/70 dark:bg-cherry-700/60 pointer-events-none"></div>
                                </div>

                                {{--
                                    Real thumbnail.
                                    In normal flow (position: relative) so aspect-square keeps its height.
                                    z-10 stacks it above the fallback layer.
                                    Starts opacity-0; fades in on load and removes the fallback layer.
                                --}}
                                <img
                                    src="{{ $thumbnailUrl }}"
                                    alt="{{ $asset->file_name }}"
                                    loading="lazy"
                                    class="relative z-10 w-full h-full object-cover opacity-0 transition-[transform,opacity] duration-300 group-hover:scale-105"
                                    onload="this.style.opacity='1'; this.previousElementSibling.remove();"
                                    onerror="this.remove();"
                                />

                                @if ($extension)
                                    <span class="absolute top-1.5 right-1.5 z-10 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white shadow-md {{ $badgeColor }}">
                                        {{ strtoupper($extension) }}
                                    </span>
                                @endif
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
