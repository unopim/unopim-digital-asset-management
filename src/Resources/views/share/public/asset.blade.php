<x-admin::layouts.anonymous>
    <x-slot:title>
        {{ $asset->file_name }} · @lang('dam::app.share.public.app-name')
    </x-slot:title>

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
        $backUrl = $share->share_type === \Webkul\DAM\Models\Share::TYPE_DIRECTORY
            ? route('dam.share.show', ['token' => $share->token])
            : null;
    @endphp

    <div class="min-h-screen bg-gray-50 dark:bg-cherry-950 flex flex-col">
        <header class="bg-white dark:bg-cherry-900 border-b border-gray-200 dark:border-cherry-800 px-6 py-4">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    @if ($backUrl)
                        <a
                            href="{{ $backUrl }}"
                            class="secondary-button"
                            title="@lang('dam::app.share.public.back-to-gallery')"
                        >
                            <span class="icon-back text-lg"></span>
                            <span class="hidden sm:inline">@lang('dam::app.share.public.back')</span>
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
                    <span class="icon-dam-download text-lg"></span>
                    <span>@lang('dam::app.share.public.download')</span>
                </a>
            </div>
        </header>

        <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-8">
            <div class="bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden">
                <div class="flex items-center justify-center bg-gray-100 dark:bg-cherry-800" style="min-height: 320px">
                    @if ($isImage)
                        <div class="w-full" style="height: 70vh;">
                            <v-zoomable-image
                                src="{{ $inlineUrl }}"
                                alt="{{ $asset->file_name }}"
                            ></v-zoomable-image>
                        </div>
                    @elseif ($isVideo)
                        <video
                            controls
                            preload="metadata"
                            class="max-w-full max-h-[70vh]"
                        >
                            <source src="{{ $inlineUrl }}" type="{{ $mime }}">
                            @lang('dam::app.share.public.video-not-supported')
                        </video>
                    @elseif ($isAudio)
                        <div class="w-full p-8 flex flex-col items-center gap-4">
                            <img src="{{ $thumbnailUrl }}" alt="" class="w-40 h-40 object-cover rounded" />
                            <audio controls class="w-full max-w-md">
                                <source src="{{ $inlineUrl }}" type="{{ $mime }}">
                                @lang('dam::app.share.public.audio-not-supported')
                            </audio>
                        </div>
                    @elseif ($isPdf)
                        <iframe
                            src="{{ $inlineUrl }}"
                            class="w-full"
                            style="height: 70vh; border: 0;"
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
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-cherry-800">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
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
                <p class="mt-4 text-xs text-gray-500 dark:text-slate-400 text-center">
                    @lang('dam::app.share.public.expires-on') {{ $share->expires_at->toDayDateTimeString() }}
                </p>
            @endif
        </main>

        <footer class="text-center text-xs text-gray-400 dark:text-slate-500 py-4">
            @lang('dam::app.share.public.powered-by-dam')
        </footer>
    </div>

    @pushOnce('scripts')
        @include('dam::share.components.zoomable-image')
    @endPushOnce
</x-admin::layouts.anonymous>
