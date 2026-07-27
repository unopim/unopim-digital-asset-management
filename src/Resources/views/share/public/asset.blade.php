<x-admin::layouts.anonymous>
    <x-slot:title>
        {{ $asset->file_name }} · @lang('dam::app.share.public.app-name')
    </x-slot:title>

    @push('styles')
        @unoPimVite(['src/Resources/assets/css/app.css'], 'dam')
    <style>
        {{--
            Pin the document to the viewport so the viewer never scrolls. The wrapper is sized
            against the body's content box rather than the viewport directly, so any padding an
            injected bar adds to the body (the Debugbar adds ~33px in local dev) is subtracted
            from the available height instead of pushing the footer off-screen.
        --}}
        html,
        body {
            height: 100vh;
            height: 100dvh;
        }

        body {
            overflow: hidden;
        }

        {{-- The layout nests the page inside #app, so the percentage chain has to run through it. --}}
        #app,
        .dam-share-viewport {
            height: 100%;
        }

        {{-- Touch devices never fire :hover, so keep the navigation permanently visible there. --}}
        @media (hover: none) {
            .dam-share-nav { opacity: 1; }
        }
    </style>
    @endpush

    @php
        $mime = $asset->mime_type ?? '';
        $isImage = \Illuminate\Support\Str::startsWith($mime, 'image/');
        $isVideo = \Illuminate\Support\Str::startsWith($mime, 'video/');
        $isAudio = \Illuminate\Support\Str::startsWith($mime, 'audio/');
        $isPdf   = $mime === 'application/pdf';
        $downloadUrl = $share->share_type === \Webkul\DAM\Models\Share::TYPE_ASSET
            ? route('dam.share.download', ['token' => $share->token])
            : route('dam.share.asset_download', ['token' => $share->token, 'assetId' => $asset->id]);
        $inlineUrl = $downloadUrl.'?disposition=inline';
        $thumbnailUrl = route('dam.share.thumbnail', ['token' => $share->token, 'assetId' => $asset->id]);
        $backUrl     = $share->share_type === \Webkul\DAM\Models\Share::TYPE_DIRECTORY
            ? route('dam.share.show', ['token' => $share->token])
            : null;
        $isAssetShare = $share->share_type === \Webkul\DAM\Models\Share::TYPE_ASSET;
        $prevUrl = (! $isAssetShare && isset($prevAssetId) && $prevAssetId)
            ? route('dam.share.asset_view', ['token' => $share->token, 'assetId' => $prevAssetId])
            : null;
        $nextUrl = (! $isAssetShare && isset($nextAssetId) && $nextAssetId)
            ? route('dam.share.asset_view', ['token' => $share->token, 'assetId' => $nextAssetId])
            : null;
    @endphp

    <div class="dam-share-viewport bg-gray-50 dark:bg-cherry-950 flex flex-col overflow-hidden">
        <header class="shrink-0 bg-white dark:bg-cherry-900 border-b border-gray-200 dark:border-cherry-800 px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    @if ($backUrl)
                        <a
                            href="{{ $backUrl }}"
                            class="transparent-button"
                            title="@lang('dam::app.share.public.back-to-gallery')"
                        >
                            <i class="icon-left text-xl -mt-px" aria-hidden="true"></i>
                            @lang('dam::app.share.public.back')
                        </a>
                    @endif
                    <h1 class="text-lg font-semibold text-zinc-900 dark:text-slate-50 truncate" title="{{ $asset->file_name }}">
                        {{ $asset->file_name }}
                    </h1>
                </div>

                <a
                    href="{{ $downloadUrl }}"
                    class="primary-button shrink-0"
                    download
                >
                    <span class="icon-dam-download text-lg text-white"></span>
                    <span>@lang('dam::app.share.public.download')</span>
                </a>
            </div>
        </header>

        <main class="flex flex-1 min-h-0 w-full flex-col px-4 py-4">
            <div class="flex flex-1 min-h-0 flex-col bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden">
                <div class="group relative flex flex-1 min-h-0 items-center justify-center bg-gray-100 dark:bg-cherry-800">
                    @if ($isImage)
                        <div class="w-full h-full">
                            <v-zoomable-image
                                src="{{ $inlineUrl }}"
                                alt="{{ $asset->file_name }}"
                            ></v-zoomable-image>
                        </div>
                    @elseif ($isVideo)
                        <div class="relative w-full h-full">
                            <v-dam-public-player
                                media-url="{{ $inlineUrl }}"
                                mime-type="{{ $mime }}"
                                file-type="video"
                                file-name="{{ $asset->file_name }}"
                                download-url="{{ $downloadUrl }}"
                            ></v-dam-public-player>
                        </div>
                    @elseif ($isAudio)
                        <div class="w-full h-full">
                            <v-dam-public-player
                                media-url="{{ $inlineUrl }}"
                                mime-type="{{ $mime }}"
                                file-type="audio"
                                file-name="{{ $asset->file_name }}"
                                download-url="{{ $downloadUrl }}"
                                cover-art-url="{{ $thumbnailUrl }}"
                                placeholder-svg="{{ asset('storage/dam/grid/audio.svg') }}"
                            ></v-dam-public-player>
                        </div>
                    @elseif ($isPdf)
                        <iframe
                            src="{{ $inlineUrl }}"
                            class="w-full h-full"
                            style="border: 0;"
                            title="{{ $asset->file_name }}"
                        ></iframe>
                    @else
                        <div class="flex flex-col items-center gap-3 p-12">
                            <img src="{{ $thumbnailUrl }}" alt="" class="w-24 h-24 object-contain opacity-80" />
                            <p class="text-sm text-gray-600 dark:text-slate-300">
                                @lang('dam::app.share.public.preview-not-available')
                            </p>
                        </div>
                    @endif

                    @if (! $isAssetShare)
                        @if ($prevUrl)
                            <a
                                href="{{ $prevUrl }}"
                                class="dam-share-nav absolute left-2 top-1/2 -translate-y-1/2 z-10 opacity-0 group-hover:opacity-100 focus-visible:opacity-100 flex w-9 h-9 items-center justify-center rounded-full bg-white dark:bg-gray-600 border-2 border-gray-300 dark:border-gray-500 shadow text-gray-700 dark:text-gray-100 hover:bg-violet-50 hover:text-violet-700 hover:border-violet-500 dark:hover:bg-violet-800 dark:hover:text-violet-200 dark:hover:border-violet-500 transition"
                                title="@lang('dam::app.admin.dam.asset.edit.previous')"
                                aria-label="@lang('dam::app.admin.dam.asset.edit.previous')"
                            >
                                <span class="text-2xl leading-none" aria-hidden="true">&#8249;</span>
                            </a>
                        @endif

                        @if ($nextUrl)
                            <a
                                href="{{ $nextUrl }}"
                                class="dam-share-nav absolute right-2 top-1/2 -translate-y-1/2 z-10 opacity-0 group-hover:opacity-100 focus-visible:opacity-100 flex w-9 h-9 items-center justify-center rounded-full bg-white dark:bg-gray-600 border-2 border-gray-300 dark:border-gray-500 shadow text-gray-700 dark:text-gray-100 hover:bg-violet-50 hover:text-violet-700 hover:border-violet-500 dark:hover:bg-violet-800 dark:hover:text-violet-200 dark:hover:border-violet-500 transition"
                                title="@lang('dam::app.admin.dam.asset.edit.next')"
                                aria-label="@lang('dam::app.admin.dam.asset.edit.next')"
                            >
                                <span class="text-2xl leading-none" aria-hidden="true">&#8250;</span>
                            </a>
                        @endif
                    @endif
                </div>

                <div class="shrink-0 px-6 py-4 border-t border-gray-200 dark:border-cherry-800">
                    <dl class="grid grid-cols-2 md:!grid-cols-3 xl:!grid-cols-4 2xl:!grid-cols-5 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-slate-400">@lang('dam::app.share.public.file-name')</dt>
                            <dd class="text-zinc-900 dark:text-slate-100 font-medium truncate">{{ $asset->file_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-slate-400">@lang('dam::app.share.public.file-type')</dt>
                            <dd class="text-zinc-900 dark:text-slate-100">{{ $asset->mime_type ?: ucfirst($asset->file_type ?? '') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-slate-400">@lang('dam::app.share.public.file-size')</dt>
                            <dd class="text-zinc-900 dark:text-slate-100">
                                {{ $asset->file_size ? \Illuminate\Support\Number::fileSize($asset->file_size, precision: 1) : '—' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if ($share->expires_at)
                <p class="shrink-0 mt-3 text-xs text-gray-500 dark:text-slate-400 text-center">
                    @lang('dam::app.share.public.expires-on') {{ $share->expires_at->toDayDateTimeString() }}
                </p>
            @endif
        </main>

        <footer class="shrink-0 text-center text-xs text-gray-400 dark:text-slate-500 py-3">
            @lang('dam::app.share.public.powered-by-dam')
        </footer>
    </div>

    @pushOnce('scripts')
        @include('dam::share.components.zoomable-image')
    @endPushOnce

    @include('dam::share.components.media-player')
</x-admin::layouts.anonymous>
