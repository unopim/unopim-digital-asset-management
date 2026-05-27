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
            @keyframes dam-card-in {
                from { opacity: 0; transform: translateY(14px); }
                to   { opacity: 1; transform: translateY(0); }
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
            .dam-card-new {
                animation: dam-card-in 0.35s ease both;
            }
        </style>
    @endpush

    <div class="min-h-screen bg-gray-50 dark:bg-cherry-950 flex flex-col">

        <header class="sticky top-0 bg-white dark:bg-cherry-900 border-b border-gray-200 dark:border-cherry-800 px-6 py-4" style="z-index: 100;">
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
                    <span class="icon-dam-download text-lg text-white"></span>
                    @lang('dam::app.share.public.download-zip')
                </a>
            </div>
        </header>

        {{-- Scrollable content --}}
        <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-8">
            @if ($assets->isEmpty())
                <div class="bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 p-12 text-center">
                    <p class="text-gray-500 dark:text-slate-300">
                        @lang('dam::app.share.public.empty-directory')
                    </p>
                </div>
            @else
                <div id="dam-asset-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
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
                            class="group bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden hover:border-violet-400 dark:hover:border-violet-500 transition dam-card-new"
                            style="animation-delay: {{ $loop->index * 20 }}ms"
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
                                    onload="this.style.opacity='1';var s=this.previousElementSibling;if(s&&s.classList.contains('dam-shimmer'))s.remove();"
                                    onerror="var s=this.previousElementSibling;if(s&&s.classList.contains('dam-shimmer'))s.classList.remove('dam-shimmer');this.remove();"
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

                {{-- Infinite-scroll sentinel + spinner --}}
                @if ($assets->hasMorePages())
                    <div id="dam-scroll-sentinel" class="flex justify-center items-center py-10" aria-hidden="true">
                        <svg class="animate-spin w-8 h-8 text-violet-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="#8A2BE2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                @endif
            @endif

            @if ($share->expires_at)
                <p id="dam-expiry-note" class="mt-6 text-xs text-gray-500 dark:text-slate-400 text-center">
                    @lang('dam::app.share.public.expires-on') {{ $share->expires_at->toDayDateTimeString() }}
                </p>
            @endif
        </main>

        <footer class="text-center text-xs text-gray-400 dark:text-slate-500 py-4">
            @lang('dam::app.share.public.powered-by-dam')
        </footer>
    </div>

    @push('scripts')
    <script>
    /* Strip dam-card-new after each SSR card animates in, before Vue re-renders on load */
    (function () {
        var g = document.getElementById('dam-asset-grid');
        if (g) {
            g.addEventListener('animationend', function (e) {
                if (e.target.classList && e.target.classList.contains('dam-card-new')) {
                    e.target.classList.remove('dam-card-new');
                    e.target.style.animationDelay = '';
                }
            });
        }
    })();

    /* Scroll-to-top button — injected outside Vue's #app so Vue re-render can't detach it */
    (function () {
        var btn = document.createElement('button');
        btn.setAttribute('aria-label', 'Scroll to top');
        btn.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;width:40px;height:40px;border-radius:9999px;background:#7c3aed;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.25);opacity:0;pointer-events:none;transition:opacity .3s';
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>';
        btn.onclick = function () { window.scrollTo({ top: 0, behavior: 'smooth' }); };
        document.body.appendChild(btn);

        window.addEventListener('scroll', function () {
            var show = window.scrollY > 300;
            btn.style.opacity = show ? '1' : '0';
            btn.style.pointerEvents = show ? 'auto' : 'none';
        }, { passive: true });
    })();
    </script>
    @endpush

    @if ($assets->hasMorePages())
    @push('scripts')
    <script>
    (function () {
        const ENDPOINT   = '{{ route('dam.share.list_assets', $share->token) }}';
        const startPage  = {{ $assets->currentPage() }};

        /*
         * Vue (app.js module) registers its load handler after this inline script
         * (deferred modules execute after regular scripts). Our load handler fires
         * first, so we use setTimeout(0) to run after Vue's synchronous app.mount().
         */
        window.addEventListener('load', function () {
        setTimeout(function () {
        const grid      = document.getElementById('dam-asset-grid');
        const sentinel  = document.getElementById('dam-scroll-sentinel');

        if (!grid || !sentinel) return;

        let currentPage = startPage;
        let loading     = false;
        let exhausted   = false;

        function badgeColor(fileType, ext) {
            if (fileType === 'video' || fileType === 'audio') return 'bg-violet-600';
            if (ext === 'pdf') return 'bg-red-600';
            if (fileType === 'image') return 'bg-gray-500';
            return 'bg-gray-600';
        }

        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function buildCardHTML(asset) {
            const ext      = (asset.extension || '').toLowerCase();
            const extUpper = ext.toUpperCase();
            const badgeCls = badgeColor(asset.file_type, ext);
            const badgeHtml = ext
                ? `<span class="absolute top-1.5 right-1.5 z-20 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white shadow-md ${badgeCls}">${extUpper}</span>`
                : '';
            const safeName = esc(asset.file_name);

            return `<a href="${esc(asset.view_url)}" class="group bg-white dark:bg-cherry-900 rounded-lg border border-gray-200 dark:border-cherry-800 overflow-hidden hover:border-violet-400 dark:hover:border-violet-500 transition">
                <div class="dam-card-img">
                    <div class="dam-shimmer absolute inset-0 z-0 flex items-center justify-center">
                        <img src="${esc(asset.placeholder_svg)}" alt="" aria-hidden="true"
                             class="w-14 h-14 object-contain opacity-40 dark:opacity-25 select-none pointer-events-none relative z-10" />
                    </div>
                    <img src="${esc(asset.thumbnail_url)}"
                         alt="${safeName}"
                         loading="lazy"
                         class="absolute inset-0 w-full h-full object-cover z-10 opacity-0 transition-[transform,opacity] duration-300 group-hover:scale-105"
                         onload="this.style.opacity=&apos;1&apos;;var s=this.previousElementSibling;if(s&&s.classList.contains(&apos;dam-shimmer&apos;))s.remove();"
                         onerror="var s=this.previousElementSibling;if(s&&s.classList.contains(&apos;dam-shimmer&apos;))s.classList.remove(&apos;dam-shimmer&apos;);this.remove();" />
                    ${badgeHtml}
                </div>
                <div class="px-3 py-2">
                    <p class="text-sm text-zinc-800 dark:text-slate-100 truncate" title="${safeName}">${safeName}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">${esc(asset.file_size_formatted)}</p>
                </div>
            </a>`;
        }

        async function loadNextPage() {
            if (loading || exhausted) return;
            loading = true;
            currentPage++;

            try {
                const res  = await fetch(`${ENDPOINT}?page=${currentPage}`);
                if (!res.ok) throw new Error('fetch failed');
                const json = await res.json();

                if (json.data && json.data.length) {
                    const before = grid.children.length;
                    grid.insertAdjacentHTML('beforeend', json.data.map(buildCardHTML).join(''));
                    const added = Array.from(grid.children).slice(before);
                    added.forEach(function (card, i) {
                        card.style.animationDelay = (i * 20) + 'ms';
                        card.classList.add('dam-card-new');
                    });
                    setTimeout(function () {
                        added.forEach(function (c) { c.classList.remove('dam-card-new'); });
                    }, 350 + added.length * 20 + 50);
                }

                if (!json.meta.has_more) {
                    exhausted = true;
                    sentinel.remove();
                }
            } catch (e) {
                exhausted = true;
                sentinel.remove();
            } finally {
                loading = false;
                if (!exhausted && sentinel.isConnected) {
                    observer.unobserve(sentinel);
                    observer.observe(sentinel);
                }
            }
        }

        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting && !loading) loadNextPage();
        }, { rootMargin: '300px' });

        observer.observe(sentinel);
        }, 0); /* end setTimeout */
        }); /* end window load */
    })();
    </script>
    @endpush
    @endif
</x-admin::layouts.anonymous>
