{{--
    Public share media player.

    Usage:
        <v-dam-public-player
            media-url="{{ $inlineUrl }}"
            mime-type="{{ $mime }}"
            file-type="{{ $isVideo ? 'video' : 'audio' }}"
            file-name="{{ $asset->file_name }}"
            download-url="{{ $downloadUrl }}"
            cover-art-url="{{ $thumbnailUrl }}"
        ></v-dam-public-player>
--}}

@pushOnce('styles')
<style>
@keyframes audio-pulse-out {
    0%   { transform: scale(0.54); opacity: 1;   background: rgba(139, 92, 246, 0.15);
           border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
    25%  { border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%; }
    50%  { border-radius: 50% 60% 30% 70% / 40% 50% 70% 30%; opacity: 0.45; }
    75%  { border-radius: 70% 30% 50% 60% / 30% 70% 40% 60%; }
    100% { transform: scale(0.76); opacity: 0;   background: rgba(139, 92, 246, 0);
           border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
}
@keyframes audio-pulse-out-2 {
    0%   { transform: scale(0.54); opacity: 1;   background: rgba(139, 92, 246, 0.15);
           border-radius: 40% 60% 70% 30% / 40% 70% 30% 60%; }
    25%  { border-radius: 70% 30% 40% 60% / 60% 40% 70% 30%; }
    50%  { border-radius: 30% 70% 60% 40% / 70% 30% 50% 60%; opacity: 0.45; }
    75%  { border-radius: 60% 40% 30% 70% / 50% 60% 40% 50%; }
    100% { transform: scale(0.76); opacity: 0;   background: rgba(139, 92, 246, 0);
           border-radius: 40% 60% 70% 30% / 40% 70% 30% 60%; }
}
.audio-ring-1,
.audio-ring-2 {
    position: absolute;
    width: 208px;
    height: 208px;
    top: 50%;
    left: 50%;
    margin-top: -104px;
    margin-left: -104px;
    border: 3px solid rgba(139, 92, 246, 1);
    box-shadow: 0 0 20px 6px rgba(139, 92, 246, 0.75), inset 0 0 8px rgba(139, 92, 246, 0.2);
    pointer-events: none;
}
.dark .audio-ring-1,
.dark .audio-ring-2 {
    box-shadow: 0 0 16px 4px rgba(139, 92, 246, 0.55), inset 0 0 8px rgba(139, 92, 246, 0.2);
}
.audio-ring-1 { animation: audio-pulse-out   2.2s ease-in-out infinite;      animation-fill-mode: backwards; }
.audio-ring-2 { animation: audio-pulse-out-2 2.2s ease-in-out infinite 1.1s; animation-fill-mode: backwards; }
@keyframes disc-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.audio-disc-spinning {
    animation: disc-spin 8s linear infinite;
}
.audio-canvas-ring { transition: opacity 0.4s ease; }
.audio-blob-rings  { transition: opacity 0.4s ease; }
/* Responsive video controls: show all on desktop, hide secondary on mobile */
.dam-ctrl-desktop { display: none; }
@media (min-width: 525px) {
    .dam-ctrl-desktop { display: flex; }
}
/* Tailwind opacity modifier classes not compiled in admin CSS */
.from-black\/90 {
    --tw-gradient-from: rgb(0 0 0 / .9) var(--tw-gradient-from-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgb(0 0 0 / 0));
}
.via-black\/60 {
    --tw-gradient-via: rgb(0 0 0 / .6) var(--tw-gradient-via-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-via), var(--tw-gradient-to, rgb(0 0 0 / 0));
}
</style>
@endPushOnce

@pushOnce('scripts')

@include('dam::asset.preview-modal.video.video-player-script')
@include('dam::asset.preview-modal.audio.audio-player-script')

<script type="text/x-template" id="v-dam-public-player-template">
    <div class="relative w-full h-full">

        {{-- ── Video player ─────────────────────────────────────────── --}}
        <template v-if="previewData.file_type === 'video'">
            @include('dam::asset.preview-modal.video.video-player')
        </template>

        {{-- ── Audio player ─────────────────────────────────────────── --}}
        <template v-else-if="previewData.file_type === 'audio'">
            @include('dam::asset.preview-modal.audio.audio-player')
        </template>

    </div>
</script>

<script type="module">
app.component('v-dam-public-player', {
    template: '#v-dam-public-player-template',

    props: {
        mediaUrl:       { type: String, required: true },
        mimeType:       { type: String, required: true },
        fileType:       { type: String, required: true },
        fileName:       { type: String, required: true },
        downloadUrl:    { type: String, required: true },
        coverArtUrl:    { type: String, default: null },
        placeholderSvg: { type: String, default: '' },
    },

    data() {
        return {
            previewData: {
                mediaUrl:              this.mediaUrl,
                mime_type:             this.mimeType,
                file_type:             this.fileType,
                file_name:             this.fileName,
                downloadUrl:           this.downloadUrl,
                downloadCompressedUrl: null,
                coverArtUrl:           this.coverArtUrl,
                placeholderSvg:        this.placeholderSvg,
            },
            ...window._damVideoPlayer.data,
            ...window._damAudioPlayer.data,
        };
    },

    computed: {
        ...window._damVideoPlayer.computed,
        ...window._damAudioPlayer.computed,
    },

    methods: {
        ...window._damVideoPlayer.methods,
        ...window._damAudioPlayer.methods,

        autoplayMedia() {
            const tryPlay = (el) => {
                if (!el) return;
                const p = el.play();
                if (p && typeof p.catch === 'function') {
                    p.catch(() => { el.muted = true; el.play().catch(() => {}); });
                }
            };
            if (this.previewData.file_type === 'video') {
                tryPlay(this.$refs.videoEl);
                this.videoIsPlaying = true;
            } else if (this.previewData.file_type === 'audio') {
                tryPlay(this.$refs.audioEl);
                this.audioIsPlaying = true;
            }
        },

        handleKeydown(e) {
            const tag = e.target?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target?.isContentEditable) return;

            const rates = [0.5, 0.75, 1, 1.25, 1.5, 2];

            if (this.previewData.file_type === 'video' && this.$refs.videoEl) {
                switch (e.key) {
                    case ' ':        e.preventDefault(); this.videoTogglePlay(); break;
                    case 'f': case 'F': e.preventDefault(); this.videoToggleFullscreen(); break;
                    case 'm': case 'M': this.videoToggleMute(); break;
                    case 'l': case 'L': this.videoToggleLoop(); break;
                    case 'ArrowLeft':  e.preventDefault(); this.videoSkip(-5); break;
                    case 'ArrowRight': e.preventDefault(); this.videoSkip(5); break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.videoVolume = Math.min(1, Math.round((this.videoVolume + 0.1) * 10) / 10);
                        this.$refs.videoEl.volume = this.videoVolume;
                        if (this.videoIsMuted && this.videoVolume > 0) { this.videoIsMuted = false; this.$refs.videoEl.muted = false; }
                        try { localStorage.setItem('dam_video_volume', this.videoVolume); } catch(_) {}
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.videoVolume = Math.max(0, Math.round((this.videoVolume - 0.1) * 10) / 10);
                        this.$refs.videoEl.volume = this.videoVolume;
                        try { localStorage.setItem('dam_video_volume', this.videoVolume); } catch(_) {}
                        break;
                    case '+': case '=':
                        e.preventDefault();
                        { const i = rates.indexOf(this.videoSpeed); if (i < rates.length - 1) this.setVideoSpeed(rates[i + 1]); }
                        break;
                    case '-':
                        e.preventDefault();
                        { const i = rates.indexOf(this.videoSpeed); if (i > 0) this.setVideoSpeed(rates[i - 1]); }
                        break;
                    default: return;
                }
                this.videoShowControls();
            } else if (this.previewData.file_type === 'audio' && this.$refs.audioEl) {
                switch (e.key) {
                    case ' ':        e.preventDefault(); this.audioTogglePlay(); break;
                    case 'm': case 'M': this.audioToggleMute(); break;
                    case 'l': case 'L': this.audioToggleLoop(); break;
                    case 'ArrowLeft':  e.preventDefault(); this.audioSkip(-5); break;
                    case 'ArrowRight': e.preventDefault(); this.audioSkip(5); break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.audioVolume = Math.min(1, Math.round((this.audioVolume + 0.1) * 10) / 10);
                        this.$refs.audioEl.volume = this.audioVolume;
                        if (this.audioIsMuted && this.audioVolume > 0) { this.audioIsMuted = false; this.$refs.audioEl.muted = false; }
                        try { localStorage.setItem('dam_audio_volume', this.audioVolume); } catch(_) {}
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.audioVolume = Math.max(0, Math.round((this.audioVolume - 0.1) * 10) / 10);
                        this.$refs.audioEl.volume = this.audioVolume;
                        try { localStorage.setItem('dam_audio_volume', this.audioVolume); } catch(_) {}
                        break;
                    case '+': case '=':
                        e.preventDefault();
                        { const i = rates.indexOf(this.audioSpeed); if (i < rates.length - 1) this.setAudioSpeed(rates[i + 1]); }
                        break;
                    case '-':
                        e.preventDefault();
                        { const i = rates.indexOf(this.audioSpeed); if (i > 0) this.setAudioSpeed(rates[i - 1]); }
                        break;
                }
            }
        },

        _formatTime(s) {
            if (!s || isNaN(s)) return '0:00';
            const m = Math.floor(s / 60);
            return `${m}:${Math.floor(s % 60).toString().padStart(2, '0')}`;
        },
    },

    mounted() {
        window.addEventListener('keydown', this.handleKeydown);
        this.videoMounted();
        this.videoResetState();
        this.audioResetState();
        this.$nextTick(() => {
            this.videoInitEl();
            this.audioInitEl();
            this.autoplayMedia();
        });
    },

    beforeUnmount() {
        window.removeEventListener('keydown', this.handleKeydown);
        this.videoBeforeUnmount();
        this.videoStopOnClose();
        this.audioStopOnClose();
    },
});
</script>

@endPushOnce
