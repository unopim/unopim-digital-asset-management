@php
    $mediaUrl = route('admin.dam.file.preview', ['path' => urlencode($asset->path)]);

    $placeholderSvg = match($asset->file_type) {
        'video'    => asset('storage/dam/preview/video.svg'),
        'audio'    => asset('storage/dam/preview/audio.svg'),
        'document' => asset('storage/dam/preview/file.svg'),
        default    => asset('storage/dam/preview/unspecified.svg'),
    };

    $coverArtUrl = ($asset->file_type === 'audio' && ! empty($asset->meta_data['cover_art_path']))
        ? route('admin.dam.file.cover-art', $asset->id)
        : null;

    $typeColor = 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300';

    $bytes    = (int) ($asset->file_size ?? 0);
    $fileSize = $bytes >= 1048576
        ? number_format($bytes / 1048576, 2) . ' MB'
        : ($bytes >= 1024 ? number_format($bytes / 1024, 1) . ' KB' : ($bytes > 0 ? $bytes . ' B' : null));

    $initialPreviewData = [
        'file_name'            => $asset->file_name,
        'extension'            => $asset->extension,
        'extension_upper'      => strtoupper($asset->extension ?? ''),
        'file_type'            => $asset->file_type,
        'mime_type'            => $asset->mime_type,
        'path'                 => $asset->path,
        'width'                => $asset->width ?? '',
        'height'               => $asset->height ?? '',
        'created_at'           => $asset->created_at?->format('d M Y, H:i'),
        'updated_at'           => $asset->updated_at?->format('d M Y, H:i'),
        'id'                   => $asset->id,
        'mediaUrl'             => $mediaUrl,
        'previewPath'          => $asset->previewPath,
        'placeholderSvg'       => $placeholderSvg,
        'coverArtUrl'          => $coverArtUrl,
        'typeColor'            => $typeColor,
        'fileSize'             => $fileSize,
        'downloadUrl'          => route('admin.dam.assets.download', $asset->id),
        'downloadCompressedUrl'=> route('admin.dam.assets.download_compressed', $asset->id),
    ];
@endphp

<script
    type="text/x-template"
    id="v-asset-preview-modal-template"
>
    <div class="flex flex-col items-center gap-2 w-full">
        @include('dam::asset.preview-modal.card')
        @include('dam::asset.preview-modal.info-modal')
        @include('dam::asset.preview-modal.viewer-modal')
        @include('dam::asset.preview-modal.editor-modal')
    </div>
</script>

@include('dam::asset.preview-modal.image.image-viewer-script')
@include('dam::asset.preview-modal.video.video-player-script')
@include('dam::asset.preview-modal.audio.audio-player-script')

<script type="module">
    // Keep the plain CSRF token in the X-CSRF-TOKEN header so it's always valid
    // regardless of cookie state. Blade re-renders this on every page load.
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    app.component('v-asset-preview-modal', {
        template: '#v-asset-preview-modal-template',

        data() {
            return {
                isOpen:     false,
                infoHover:  false,
                isInfoOpen: false,
                isEditOpen: false,

                previewData: @json($initialPreviewData),

                // Image
                ...window._damImageViewer.data,

                // Video
                ...window._damVideoPlayer.data,

                // Audio
                ...window._damAudioPlayer.data,
            };
        },

        computed: {
            ...window._damImageViewer.computed,
            ...window._damVideoPlayer.computed,
            ...window._damAudioPlayer.computed,
        },

        methods: {
            // ── Open / close ──────────────────────────────────────────
            openPreview() {
                this.imgResetState();
                this.videoResetState();
                this.audioResetState();
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => {
                    this.videoInitEl();
                    this.audioInitEl();
                });
            },

            closePreview() {
                this.videoStopOnClose();
                this.audioStopOnClose();
                this.isOpen = false;
                document.body.style.overflow = '';
            },

            handleEscape(e) {
                if (e.key === 'Escape' && this.isInfoOpen) { this.isInfoOpen = false; return; }
                if (e.key === 'Escape' && this.isEditOpen) { this.isEditOpen = false; this.editTool = null; this.editPrompt = ''; return; }
                if (!this.isOpen) return;
                const isVideoKey = this.$refs.videoEl && [' ', 'f', 'F', 'm', 'M', 'l', 'L', '+', '=', '-', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key);
                switch (e.key) {
                    case 'Escape': this.closePreview(); break;
                    case ' ':
                        if (this.$refs.videoEl) { e.preventDefault(); this.videoTogglePlay(); }
                        else if (this.$refs.audioEl) { e.preventDefault(); this.audioTogglePlay(); }
                        break;
                    case 'f': case 'F':
                        if (this.$refs.videoEl) { e.preventDefault(); this.videoToggleFullscreen(); }
                        break;
                    case 'm': case 'M':
                        if (this.$refs.videoEl) { this.videoToggleMute(); }
                        else if (this.$refs.audioEl) { this.audioToggleMute(); }
                        break;
                    case 'ArrowLeft':
                        if (this.$refs.videoEl) { e.preventDefault(); this.videoSkip(-5); }
                        else if (this.$refs.audioEl) { e.preventDefault(); this.audioSkip(-5); }
                        break;
                    case 'ArrowRight':
                        if (this.$refs.videoEl) { e.preventDefault(); this.videoSkip(5); }
                        else if (this.$refs.audioEl) { e.preventDefault(); this.audioSkip(5); }
                        break;
                    case 'ArrowUp':
                        if (this.$refs.videoEl) {
                            e.preventDefault();
                            this.videoVolume = Math.min(1, Math.round((this.videoVolume + 0.1) * 10) / 10);
                            this.$refs.videoEl.volume = this.videoVolume;
                            if (this.videoIsMuted && this.videoVolume > 0) { this.videoIsMuted = false; this.$refs.videoEl.muted = false; }
                            try { localStorage.setItem('dam_video_volume', this.videoVolume); } catch(_) {}
                        } else if (this.$refs.audioEl) {
                            e.preventDefault();
                            this.audioVolume = Math.min(1, Math.round((this.audioVolume + 0.1) * 10) / 10);
                            this.$refs.audioEl.volume = this.audioVolume;
                            if (this.audioIsMuted && this.audioVolume > 0) { this.audioIsMuted = false; this.$refs.audioEl.muted = false; }
                            try { localStorage.setItem('dam_audio_volume', this.audioVolume); } catch(_) {}
                        }
                        break;
                    case 'ArrowDown':
                        if (this.$refs.videoEl) {
                            e.preventDefault();
                            this.videoVolume = Math.max(0, Math.round((this.videoVolume - 0.1) * 10) / 10);
                            this.$refs.videoEl.volume = this.videoVolume;
                            try { localStorage.setItem('dam_video_volume', this.videoVolume); } catch(_) {}
                        } else if (this.$refs.audioEl) {
                            e.preventDefault();
                            this.audioVolume = Math.max(0, Math.round((this.audioVolume - 0.1) * 10) / 10);
                            this.$refs.audioEl.volume = this.audioVolume;
                            try { localStorage.setItem('dam_audio_volume', this.audioVolume); } catch(_) {}
                        }
                        break;
                    case '+': case '=':
                        e.preventDefault();
                        if (this.$refs.videoEl) {
                            const rates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                            const idx = rates.indexOf(this.videoSpeed);
                            if (idx < rates.length - 1) this.setVideoSpeed(rates[idx + 1]);
                        } else if (this.$refs.audioEl) {
                            const rates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                            const idx = rates.indexOf(this.audioSpeed);
                            if (idx < rates.length - 1) this.setAudioSpeed(rates[idx + 1]);
                        } else { this.imgZoomIn(); }
                        break;
                    case '-':
                        e.preventDefault();
                        if (this.$refs.videoEl) {
                            const rates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                            const idx = rates.indexOf(this.videoSpeed);
                            if (idx > 0) this.setVideoSpeed(rates[idx - 1]);
                        } else if (this.$refs.audioEl) {
                            const rates = [0.5, 0.75, 1, 1.25, 1.5, 2];
                            const idx = rates.indexOf(this.audioSpeed);
                            if (idx > 0) this.setAudioSpeed(rates[idx - 1]);
                        } else { this.imgZoomOut(); }
                        break;
                    case 'r': case 'R': this.imgRotateRight(); break;
                    case 'l': case 'L':
                        if (this.$refs.videoEl) this.videoToggleLoop();
                        else if (this.$refs.audioEl) this.audioToggleLoop();
                        else this.imgRotateLeft();
                        break;
                    case '0':           this.imgReset(); break;
                }
                if (isVideoKey) this.videoShowControls();
            },

            // ── SPA navigation ────────────────────────────────────────
            onDamAssetChanged(data) {
                this.closePreview();
                this.isInfoOpen = false;
                this.isEditOpen = false;
                this.imgResetState();
                this.videoResetState();
                this.audioResetState();

                this.previewData = {
                    file_name:             data.asset.file_name,
                    extension:             data.asset.extension,
                    extension_upper:       (data.asset.extension || '').toUpperCase(),
                    file_type:             data.asset.file_type,
                    mime_type:             data.asset.mime_type,
                    path:                  data.asset.path,
                    width:                 data.width ?? '',
                    height:                data.height ?? '',
                    created_at:            data.createdAtFormatted ?? '',
                    updated_at:            data.updatedAtFormatted ?? '',
                    id:                    data.asset.id,
                    mediaUrl:              data.mediaUrl,
                    previewPath:           data.previewPath,
                    placeholderSvg:        data.placeholderSvg,
                    coverArtUrl:           data.coverArtUrl,
                    typeColor:             data.typeColor,
                    fileSize:              data.fileSize,
                    downloadUrl:           data.downloadUrl,
                    downloadCompressedUrl: data.downloadCompressedUrl,
                };

                this.$nextTick(() => {
                    if (this.$refs.videoEl) this.$refs.videoEl.load();
                    if (this.$refs.audioEl) this.$refs.audioEl.load();
                });
            },

            // ── Image ─────────────────────────────────────────────────
            ...window._damImageViewer.methods,

            // ── Video ─────────────────────────────────────────────────
            ...window._damVideoPlayer.methods,

            // ── Audio ─────────────────────────────────────────────────
            ...window._damAudioPlayer.methods,

            _formatTime(s) {
                if (!s || isNaN(s)) return '0:00';
                const m = Math.floor(s / 60);
                return `${m}:${Math.floor(s % 60).toString().padStart(2, '0')}`;
            },
        },

        mounted() {
            window.addEventListener('keydown', this.handleEscape);
            this.imgMounted();
            this.videoMounted();
            this._onAssetChange = (data) => this.onDamAssetChanged(data);
            this.$emitter.on('dam-asset-changed', this._onAssetChange);
        },

        beforeUnmount() {
            window.removeEventListener('keydown', this.handleEscape);
            this.imgBeforeUnmount();
            this.videoBeforeUnmount();
            if (this._onAssetChange) {
                this.$emitter.off('dam-asset-changed', this._onAssetChange);
            }
            document.body.style.overflow = '';
        },
    });
</script>
