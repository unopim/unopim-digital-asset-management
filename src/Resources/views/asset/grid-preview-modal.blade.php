{{--
    Standalone fullscreen preview modal launched from the DAM grid's eye icon.

    The eye icon on each gallery card emits `dam-open-preview` with the asset
    id; this component fetches the asset's preview payload from
    `admin.dam.assets.show` and opens a fullscreen viewer:
      - image  → simple <img> with zoom / rotate / pan
      - video  → native <video controls>
      - audio  → native <audio controls>
      - pdf    → native <iframe>
      - other  → placeholder + filename

    Self-contained: no dependency on the edit-page custom video/audio/image
    players or any `window._dam*` globals. Safe to mount on the listing page.
--}}
<v-dam-grid-preview-modal></v-dam-grid-preview-modal>

@pushOnce('scripts')
    <script type="text/x-template" id="v-dam-grid-preview-modal-template">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[10010] flex items-center justify-center"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/75" @click="close"></div>

            {{-- Loading --}}
            <div
                v-if="isLoading"
                class="relative z-10 flex items-center justify-center w-24 h-24 rounded-xl bg-white dark:bg-gray-900 shadow-2xl"
            >
                <svg class="animate-spin h-10 w-10 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>

            {{-- Large layout: image / video / pdf --}}
            <div
                v-else-if="asset && isLargeLayout"
                class="relative z-10 flex flex-col w-[85vw] h-[88vh] max-w-6xl rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10"
            >
                <div class="flex items-center gap-3 px-5 py-3 shrink-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                    <span class="shrink-0 px-2 py-0.5 rounded text-xs font-semibold bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300" v-text="badge"></span>
                    <p class="flex-1 text-sm font-semibold text-gray-800 dark:text-white truncate" v-text="asset.file_name"></p>
                    <span v-if="humanSize" class="shrink-0 text-xs text-gray-400 dark:text-gray-500 hidden sm:block" v-text="humanSize"></span>
                    <button
                        type="button"
                        class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                        @click="close"
                        aria-label="@lang('dam::app.admin.dam.asset.edit.preview-modal.close')"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 min-h-0 overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                    {{-- Image viewer --}}
                    <div
                        v-if="asset.file_type === 'image'"
                        class="relative w-full h-full overflow-hidden flex items-center justify-center select-none"
                        :class="isDragging ? 'cursor-grabbing' : (zoom > 1 ? 'cursor-grab' : 'cursor-default')"
                        @wheel.prevent="onWheel"
                        @mousedown="onMouseDown"
                    >
                        <img
                            :src="asset.preview_url"
                            :alt="asset.file_name"
                            class="max-w-none max-h-none block pointer-events-none"
                            :style="{
                                transform: transformStyle,
                                transformOrigin: 'center center',
                                transition: isDragging ? 'none' : 'transform 0.15s ease',
                                maxHeight: '100%',
                                maxWidth: '100%',
                            }"
                            draggable="false"
                        />

                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1 px-3 py-1.5 rounded-full bg-black/60 text-white text-xs shadow-lg z-10 select-none">
                            <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="@lang('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.rotate-left')" @click="rotateLeft">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            </button>
                            <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="@lang('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.rotate-right')" @click="rotateRight">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                            </button>
                            <span class="w-px h-4 bg-white/30 mx-1"></span>
                            <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="@lang('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.zoom-out')" @click="zoomOut">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            </button>
                            <span class="min-w-[44px] text-center font-mono tabular-nums">@{{ zoomPercent }}%</span>
                            <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="@lang('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.zoom-in')" @click="zoomIn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            </button>
                            <span class="w-px h-4 bg-white/30 mx-1"></span>
                            <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="@lang('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.reset-all')" @click="resetView">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Video --}}
                    <video
                        v-else-if="asset.file_type === 'video'"
                        :src="asset.preview_url"
                        controls
                        controlslist="nodownload"
                        class="max-w-full max-h-full"
                    ></video>

                    {{-- PDF --}}
                    <iframe
                        v-else
                        :src="asset.preview_url"
                        class="w-full h-full border-0"
                        :title="asset.file_name"
                    ></iframe>
                </div>
            </div>

            {{-- Compact layout: audio / fallback --}}
            <div
                v-else-if="asset"
                class="relative z-10 w-full max-w-lg mx-4 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10"
            >
                <div class="flex items-center gap-3 px-5 py-3 shrink-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                    <span class="shrink-0 px-2 py-0.5 rounded text-xs font-semibold bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300" v-text="badge"></span>
                    <p class="flex-1 text-sm font-semibold text-gray-800 dark:text-white truncate" v-text="asset.file_name"></p>
                    <span v-if="humanSize" class="shrink-0 text-xs text-gray-400 dark:text-gray-500 hidden sm:block" v-text="humanSize"></span>
                    <button
                        type="button"
                        class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                        @click="close"
                        aria-label="@lang('dam::app.admin.dam.asset.edit.preview-modal.close')"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col items-center justify-center gap-4 p-8">
                    <audio
                        v-if="asset.file_type === 'audio'"
                        :src="asset.preview_url"
                        controls
                        class="w-full"
                    ></audio>

                    <template v-else>
                        <img :src="placeholder" :alt="asset.file_name" class="w-24 h-24 object-contain opacity-60" />
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                            @lang('dam::app.admin.dam.asset.edit.preview-modal.not-available')
                        </p>
                    </template>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-dam-grid-preview-modal', {
            template: '#v-dam-grid-preview-modal-template',

            data() {
                return {
                    isOpen:    false,
                    isLoading: false,
                    asset:     null,

                    // Image viewer state.
                    zoom:        1,
                    rotation:    0,
                    panX:        0,
                    panY:        0,
                    isDragging:  false,
                    dragStartX:  0,
                    dragStartY:  0,
                    panStartX:   0,
                    panStartY:   0,

                    showUrlTemplate: @js(route('admin.dam.assets.show', '__id__')),
                    placeholders: {
                        video:    @js(asset('storage/dam/preview/video.svg')),
                        audio:    @js(asset('storage/dam/preview/audio.svg')),
                        document: @js(asset('storage/dam/preview/file.svg')),
                        default:  @js(asset('storage/dam/preview/unspecified.svg')),
                    },
                };
            },

            computed: {
                // Image, video and PDF use the wide modal; audio and every
                // other type use the compact one.
                isLargeLayout() {
                    if (! this.asset) {
                        return false;
                    }

                    return ['image', 'video'].includes(this.asset.file_type)
                        || this.asset.extension === 'pdf';
                },

                badge() {
                    return (this.asset?.extension || '').toUpperCase();
                },

                humanSize() {
                    const bytes = this.asset?.file_size || 0;

                    if (bytes >= 1048576) {
                        return (bytes / 1048576).toFixed(2) + ' MB';
                    }

                    if (bytes >= 1024) {
                        return (bytes / 1024).toFixed(1) + ' KB';
                    }

                    return bytes > 0 ? bytes + ' B' : '';
                },

                placeholder() {
                    return this.placeholders[this.asset?.file_type] || this.placeholders.default;
                },

                transformStyle() {
                    return `translate(${this.panX}px, ${this.panY}px) scale(${this.zoom}) rotate(${this.rotation}deg)`;
                },

                zoomPercent() {
                    return Math.round(this.zoom * 100);
                },
            },

            mounted() {
                this.$emitter.on('dam-open-preview', this.open);

                window.addEventListener('keydown', this.handleEscape);
                window.addEventListener('mousemove', this.onMouseMove);
                window.addEventListener('mouseup', this.onMouseUp);
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this.handleEscape);
                window.removeEventListener('mousemove', this.onMouseMove);
                window.removeEventListener('mouseup', this.onMouseUp);
                document.body.style.overflow = '';
            },

            methods: {
                /**
                 * Open the modal and load the requested asset's preview data.
                 * `admin.dam.assets.show` returns the asset under `data.asset`
                 * plus `previewPath` (URL for the preview render). We flatten
                 * it into a single `asset` object the template can consume.
                 */
                open(id) {
                    this.asset     = null;
                    this.isLoading = true;
                    this.isOpen    = true;
                    this.resetView();

                    document.body.style.overflow = 'hidden';

                    this.$axios.get(this.showUrlTemplate.replace('__id__', id))
                        .then(response => {
                            const payload = response.data || {};
                            const asset = payload.asset || {};

                            this.asset = {
                                id:           asset.id,
                                file_name:    asset.file_name,
                                file_type:    asset.file_type,
                                file_size:    asset.file_size,
                                mime_type:    asset.mime_type,
                                extension:    asset.extension,
                                preview_url:  payload.previewPath || payload.mediaUrl || '',
                            };
                        })
                        .catch(error => {
                            this.close();

                            this.$emitter.emit('add-flash', {
                                type:    'error',
                                message: error.response?.data?.message || error.message,
                            });
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },

                close() {
                    this.isOpen = false;
                    this.asset  = null;
                    document.body.style.overflow = '';
                },

                resetView() {
                    this.zoom       = 1;
                    this.rotation   = 0;
                    this.panX       = 0;
                    this.panY       = 0;
                    this.isDragging = false;
                },

                zoomIn()      { this.zoom = Math.min(10,  parseFloat((this.zoom + 0.25).toFixed(2))); },
                zoomOut()     { this.zoom = Math.max(0.1, parseFloat((this.zoom - 0.25).toFixed(2))); },
                rotateRight() { this.rotation = (this.rotation + 90) % 360; },
                rotateLeft()  { this.rotation = (this.rotation - 90 + 360) % 360; },

                onWheel(e) {
                    const factor = e.deltaY < 0 ? 1.1 : 0.9;
                    this.zoom = Math.min(10, Math.max(0.1, parseFloat((this.zoom * factor).toFixed(3))));
                },

                onMouseDown(e) {
                    if (e.button !== 0) {
                        return;
                    }

                    this.isDragging = true;
                    this.dragStartX = e.clientX;
                    this.dragStartY = e.clientY;
                    this.panStartX  = this.panX;
                    this.panStartY  = this.panY;
                    e.preventDefault();
                },

                onMouseMove(e) {
                    if (! this.isDragging) {
                        return;
                    }

                    this.panX = this.panStartX + (e.clientX - this.dragStartX);
                    this.panY = this.panStartY + (e.clientY - this.dragStartY);
                },

                onMouseUp() {
                    this.isDragging = false;
                },

                handleEscape(e) {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.close();
                    }
                },
            },
        });
    </script>
@endPushOnce
