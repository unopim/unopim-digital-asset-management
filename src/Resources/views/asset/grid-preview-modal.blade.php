{{--
    Fullscreen preview modal launched from the DAM grid's eye icon.

    The eye icon on each gallery card emits `dam-open-preview` with the asset id;
    this component fetches the asset from `admin.dam.assets.show` and delegates
    rendering to the same viewer-modal, video-player, audio-player, and image-viewer
    used on the asset edit page — giving identical playback / zoom / rotate behaviour.
--}}
<v-dam-grid-preview-modal></v-dam-grid-preview-modal>

@pushOnce('scripts')
    @include('dam::asset.preview-modal.image.image-viewer-script')
    @include('dam::asset.preview-modal.video.video-player-script')
    @include('dam::asset.preview-modal.audio.audio-player-script')

    <script type="text/x-template" id="v-dam-grid-preview-modal-template">
        {{-- Loading overlay --}}
        <div
            v-if="isLoading"
            class="fixed inset-0 z-[10010] flex items-center justify-center bg-white dark:bg-gray-900"
        >
            <svg class="animate-spin h-10 w-10 text-violet-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="#8A2BE2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        {{-- Full viewer — same template as edit page --}}
        @include('dam::asset.preview-modal.viewer-modal')
    </script>

    <script type="module">
        app.component('v-dam-grid-preview-modal', {
            template: '#v-dam-grid-preview-modal-template',

            data() {
                return {
                    isOpen:    false,
                    isLoading: false,

                    previewData: {
                        id:                    null,
                        file_name:             '',
                        extension:             '',
                        extension_upper:       '',
                        file_type:             '',
                        mime_type:             '',
                        path:                  '',
                        width:                 '',
                        height:                '',
                        created_at:            '',
                        updated_at:            '',
                        mediaUrl:              '',
                        previewPath:           '',
                        placeholderSvg:        '',
                        coverArtUrl:           null,
                        typeColor:             'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300',
                        fileSize:              '',
                        downloadUrl:           '',
                        downloadCompressedUrl: '',
                    },

                    showUrlTemplate: @js(route('admin.dam.assets.show', '__id__')),

                    ...window._damImageViewer.data,
                    ...window._damVideoPlayer.data,
                    ...window._damAudioPlayer.data,
                };
            },

            computed: {
                ...window._damImageViewer.computed,
                ...window._damVideoPlayer.computed,
                ...window._damAudioPlayer.computed,
            },

            mounted() {
                this.$emitter.on('dam-open-preview', this.openById);

                window.addEventListener('keydown', this.handleEscape);

                this.imgMounted();
                this.videoMounted();
                this.imgResetState();
                this.videoResetState();
                this.audioResetState();

                this.$nextTick(() => {
                    this.videoInitEl();
                    this.audioInitEl();
                });
            },

            beforeUnmount() {
                window.removeEventListener('keydown', this.handleEscape);
                this.imgBeforeUnmount();
                this.videoBeforeUnmount();
                document.body.style.overflow = '';
            },

            methods: {
                ...window._damImageViewer.methods,
                ...window._damVideoPlayer.methods,
                ...window._damAudioPlayer.methods,

                _formatTime(s) {
                    if (!s || isNaN(s)) return '0:00';
                    const m = Math.floor(s / 60);
                    return `${m}:${Math.floor(s % 60).toString().padStart(2, '0')}`;
                },

                openById(id) {
                    this.isLoading = true;
                    this.imgResetState();
                    this.videoResetState();
                    this.audioResetState();
                    document.body.style.overflow = 'hidden';

                    this.$axios.get(this.showUrlTemplate.replace('__id__', id))
                        .then(response => {
                            const payload = response.data || {};
                            const asset   = payload.asset || {};

                            this.previewData = {
                                id:                    asset.id,
                                file_name:             asset.file_name,
                                extension:             asset.extension || '',
                                extension_upper:       (asset.extension || '').toUpperCase(),
                                file_type:             asset.file_type,
                                mime_type:             asset.mime_type,
                                path:                  asset.path,
                                width:                 payload.width ?? '',
                                height:                payload.height ?? '',
                                created_at:            payload.createdAtFormatted ?? '',
                                updated_at:            payload.updatedAtFormatted ?? '',
                                mediaUrl:              payload.mediaUrl || '',
                                previewPath:           payload.previewPath || '',
                                placeholderSvg:        payload.placeholderSvg || '',
                                coverArtUrl:           payload.coverArtUrl || null,
                                typeColor:             payload.typeColor || 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300',
                                fileSize:              payload.fileSize || '',
                                downloadUrl:           payload.downloadUrl || '',
                                downloadCompressedUrl: payload.downloadCompressedUrl || '',
                            };

                            this.openPreview();
                        })
                        .catch(error => {
                            document.body.style.overflow = '';
                            this.$emitter.emit('add-flash', {
                                type:    'error',
                                message: error.response?.data?.message || error.message,
                            });
                        })
                        .finally(() => {
                            this.isLoading = false;
                        });
                },

                openPreview() {
                    this.isOpen = true;
                    this.$nextTick(() => {
                        this.videoInitEl();
                        this.audioInitEl();
                        this.autoplayMedia();
                    });
                },

                autoplayMedia() {
                    const tryPlay = (el) => {
                        if (!el) return;
                        const p = el.play();
                        if (p && typeof p.catch === 'function') {
                            p.catch(() => { el.muted = true; el.play().catch(() => {}); });
                        }
                    };

                    if (this.previewData?.file_type === 'video') {
                        tryPlay(this.$refs.videoEl);
                        this.videoIsPlaying = true;
                    } else if (this.previewData?.file_type === 'audio') {
                        tryPlay(this.$refs.audioEl);
                        this.audioIsPlaying = true;
                    }
                },

                closePreview() {
                    this.videoStopOnClose();
                    this.audioStopOnClose();
                    this.isOpen = false;
                    document.body.style.overflow = '';
                },

                handleEscape(e) {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.closePreview();
                        return;
                    }

                    if (!this.isOpen) return;

                    const target = e.target;
                    const tag    = target && target.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (target && target.isContentEditable)) return;

                    const isVideoKey = this.$refs.videoEl && [' ', 'f', 'F', 'm', 'M', 'l', 'L', '+', '=', '-', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key);

                    switch (e.key) {
                        case ' ':
                            if (this.$refs.videoEl)      { e.preventDefault(); this.videoTogglePlay(); }
                            else if (this.$refs.audioEl) { e.preventDefault(); this.audioTogglePlay(); }
                            break;
                        case 'f': case 'F':
                            if (this.$refs.videoEl) { e.preventDefault(); this.videoToggleFullscreen(); }
                            break;
                        case 'm': case 'M':
                            if (this.$refs.videoEl)      this.videoToggleMute();
                            else if (this.$refs.audioEl) this.audioToggleMute();
                            break;
                        case 'ArrowLeft':
                            if (this.$refs.videoEl)      { e.preventDefault(); this.videoSkip(-5); }
                            else if (this.$refs.audioEl) { e.preventDefault(); this.audioSkip(-5); }
                            break;
                        case 'ArrowRight':
                            if (this.$refs.videoEl)      { e.preventDefault(); this.videoSkip(5); }
                            else if (this.$refs.audioEl) { e.preventDefault(); this.audioSkip(5); }
                            break;
                        case 'ArrowUp':
                            if (this.$refs.videoEl) {
                                e.preventDefault();
                                this.videoVolume = Math.min(1, Math.round((this.videoVolume + 0.1) * 10) / 10);
                                this.$refs.videoEl.volume = this.videoVolume;
                                if (this.videoIsMuted && this.videoVolume > 0) { this.videoIsMuted = false; this.$refs.videoEl.muted = false; }
                                try { localStorage.setItem('dam_video_volume', this.videoVolume); } catch (_) {}
                            } else if (this.$refs.audioEl) {
                                e.preventDefault();
                                this.audioVolume = Math.min(1, Math.round((this.audioVolume + 0.1) * 10) / 10);
                                this.$refs.audioEl.volume = this.audioVolume;
                                if (this.audioIsMuted && this.audioVolume > 0) { this.audioIsMuted = false; this.$refs.audioEl.muted = false; }
                                try { localStorage.setItem('dam_audio_volume', this.audioVolume); } catch (_) {}
                            }
                            break;
                        case 'ArrowDown':
                            if (this.$refs.videoEl) {
                                e.preventDefault();
                                this.videoVolume = Math.max(0, Math.round((this.videoVolume - 0.1) * 10) / 10);
                                this.$refs.videoEl.volume = this.videoVolume;
                                try { localStorage.setItem('dam_video_volume', this.videoVolume); } catch (_) {}
                            } else if (this.$refs.audioEl) {
                                e.preventDefault();
                                this.audioVolume = Math.max(0, Math.round((this.audioVolume - 0.1) * 10) / 10);
                                this.$refs.audioEl.volume = this.audioVolume;
                                try { localStorage.setItem('dam_audio_volume', this.audioVolume); } catch (_) {}
                            }
                            break;
                        case '+': case '=':
                            e.preventDefault();
                            if (this.$refs.videoEl) {
                                const vRates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                                const vIdx   = vRates.indexOf(this.videoSpeed);
                                if (vIdx < vRates.length - 1) this.setVideoSpeed(vRates[vIdx + 1]);
                            } else if (this.$refs.audioEl) {
                                const aRates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                                const aIdx   = aRates.indexOf(this.audioSpeed);
                                if (aIdx < aRates.length - 1) this.setAudioSpeed(aRates[aIdx + 1]);
                            } else {
                                this.imgZoomIn();
                            }
                            break;
                        case '-':
                            e.preventDefault();
                            if (this.$refs.videoEl) {
                                const vRates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                                const vIdx   = vRates.indexOf(this.videoSpeed);
                                if (vIdx > 0) this.setVideoSpeed(vRates[vIdx - 1]);
                            } else if (this.$refs.audioEl) {
                                const aRates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                                const aIdx   = aRates.indexOf(this.audioSpeed);
                                if (aIdx > 0) this.setAudioSpeed(aRates[aIdx - 1]);
                            } else {
                                this.imgZoomOut();
                            }
                            break;
                        case 'r': case 'R': this.imgRotateRight(); break;
                        case 'l': case 'L':
                            if (this.$refs.videoEl)      this.videoToggleLoop();
                            else if (this.$refs.audioEl) this.audioToggleLoop();
                            else                         this.imgRotateLeft();
                            break;
                        case '0': this.imgReset(); break;
                    }

                    if (isVideoKey) this.videoShowControls();
                },
            },
        });
    </script>
@endPushOnce
