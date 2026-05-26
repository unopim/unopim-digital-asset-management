<x-admin::layouts.anonymous>
    <x-slot:title>
        {{ $directory->name }} · @lang('dam::app.share.public.app-name')
    </x-slot:title>

    @push('styles')
        @unoPimVite(['src/Resources/assets/css/app.css'], 'dam')
        <style>
@keyframes dam-shimmer {
                0%   { background-position: 200% center; }
                100% { background-position: -200% center; }
            }
            .dam-card-img {
                position: relative;
                width: 100%;
                aspect-ratio: 1 / 1;
                overflow: hidden;
            }
            .dam-shimmer {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(90deg, #e5e7eb 25%, #f9fafb 50%, #e5e7eb 75%);
                background-size: 400% 100%;
                animation: dam-shimmer 5s ease-in-out infinite;
            }
            html.dark .dam-shimmer {
                background: linear-gradient(90deg, #1e1b2e 25%, #2d2a40 50%, #1e1b2e 75%);
                background-size: 400% 100%;
            }
        </style>
    @endpush

    <div class="min-h-screen bg-gray-50 dark:bg-cherry-950 flex flex-col">

        {{-- Sticky wrapper: header + pagination bar move together --}}
        <div class="sticky top-0" style="z-index: 100;">
            <header class="bg-white dark:bg-cherry-900 border-b border-gray-200 dark:border-cherry-800 px-6 py-4">
                <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="icon-dam-folder text-2xl text-gray-500 dark:text-slate-400"></span>
                        <h1 class="text-lg font-semibold text-zinc-900 dark:text-slate-50 truncate">
                            {{ $directory->name }}
                        </h1>
                        <span class="text-sm text-gray-500 dark:text-slate-400 ml-2">
                            @lang('dam::app.share.public.files-count', ['count' => $assets->total()])
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

            @if ($assets->hasPages())
                <div class="bg-white dark:bg-cherry-900 border-b border-gray-200 dark:border-cherry-800 px-6">
                    <div class="max-w-6xl mx-auto">
                        @include('dam::share.public.partials.pagination', ['assets' => $assets, 'perPage' => $perPage])
                    </div>
                </div>
            @endif
        </div>

        {{-- Scrollable content --}}
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
                                $asset->file_type === 'image'                   => 'bg-gray-500',
                                default                                         => 'bg-gray-600',
                            };
                        @endphp
                        <a
                            href="{{ $viewUrl }}"
                            class="group bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden hover:border-violet-400 dark:hover:border-violet-500 transition"
                        >
                            <div class="dam-card-img">

                                {{-- Shimmer skeleton shown while thumbnail loads --}}
                                <div class="dam-shimmer absolute inset-0 z-0 flex items-center justify-center">
                                    <img
                                        src="{{ $placeholderSvg }}"
                                        alt=""
                                        aria-hidden="true"
                                        class="w-14 h-14 object-contain opacity-40 dark:opacity-25 select-none pointer-events-none relative z-10"
                                    />
                                </div>

                                <img
                                    src="{{ $thumbnailUrl }}"
                                    alt="{{ $asset->file_name }}"
                                    loading="lazy"
                                    class="absolute inset-0 w-full h-full object-cover z-10 opacity-0 transition-[transform,opacity] duration-300 group-hover:scale-105"
                                    onload="this.style.opacity='1'; this.previousElementSibling && this.previousElementSibling.remove();"
                                    onerror="this.previousElementSibling && this.previousElementSibling.classList.remove('dam-shimmer'); this.remove();"
                                />

                                @if ($extension)
                                    <span class="absolute top-1.5 right-1.5 z-20 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white shadow-md {{ $badgeColor }}">
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
