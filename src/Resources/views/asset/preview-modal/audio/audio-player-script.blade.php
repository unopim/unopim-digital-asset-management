<script type="module">
window._damAudioPlayer = {

    data: {
        audioIsPlaying:          false,
        audioCurrentTime:        0,
        audioDuration:           0,
        audioVolume:             1,
        audioEnded:              false,
        audioIsSeeking:          false,
        audioIsLooping:          false,
        audioIsMuted:            false,
        audioSpeed:              1,
        audioSeekTooltip:        '',
        audioSeekTooltipX:       0,
        audioSeekTooltipVisible: false,
    },

    computed: {
        audioCurrentTimeDisplay() { return this._formatTime(this.audioCurrentTime); },
        audioDurationDisplay()    { return this._formatTime(this.audioDuration); },
    },

    methods: {
        // ── Lifecycle helpers ─────────────────────────────────────────
        audioResetState() {
            this.audioIsPlaying = false; this.audioCurrentTime = 0;
            this.audioDuration = 0; this.audioVolume = 1; this.audioEnded = false;
            this.audioIsLooping = false; this.audioIsMuted = false; this.audioSpeed = 1;
            this.audioSeekTooltipVisible = false;
            this.audioStopViz();
            try { const sa = parseFloat(localStorage.getItem('dam_audio_volume')); if (!isNaN(sa)) this.audioVolume = sa; } catch(_) {}
        },

        audioInitEl() {
            if (this.$refs.audioEl) {
                this.$refs.audioEl.pause();
                this.$refs.audioEl.currentTime = 0;
                this.$refs.audioEl.volume = this.audioVolume;
                this.$refs.audioEl.loop = false;
            }
            this._audioDrawRing();
        },

        audioStopOnClose() {
            if (this.$refs.audioEl) this.$refs.audioEl.pause();
            this.audioIsPlaying = false;
        },

        // ── Playback ──────────────────────────────────────────────────
        audioTogglePlay() {
            const el = this.$refs.audioEl;
            if (!el) return;
            if (this.audioEnded) { el.currentTime = 0; this.audioCurrentTime = 0; this.audioEnded = false; }
            if (el.paused) {
                el.play();
                this.audioIsPlaying = true;
            } else {
                el.pause();
                this.audioIsPlaying = false;
                this.$nextTick(() => this._audioDrawRing());
            }
        },

        audioSkip(sec) {
            const el = this.$refs.audioEl;
            if (!el) return;
            el.currentTime = Math.max(0, Math.min(el.duration || 0, el.currentTime + sec));
            this.audioCurrentTime = el.currentTime;
        },

        setAudioSpeed(rate) {
            this.audioSpeed = rate;
            if (this.$refs.audioEl) this.$refs.audioEl.playbackRate = rate;
        },

        audioToggleLoop() {
            this.audioIsLooping = !this.audioIsLooping;
            if (this.$refs.audioEl) this.$refs.audioEl.loop = this.audioIsLooping;
        },

        audioToggleMute() {
            const el = this.$refs.audioEl;
            if (!el) return;
            this.audioIsMuted = !this.audioIsMuted;
            el.muted = this.audioIsMuted;
        },

        // ── Volume ────────────────────────────────────────────────────
        audioOnVolume(e) {
            const v = parseFloat(e.target.value);
            this.audioVolume = v;
            if (this.$refs.audioEl) this.$refs.audioEl.volume = v;
            if (this.audioIsMuted && v > 0) { this.audioIsMuted = false; this.$refs.audioEl.muted = false; }
            try { localStorage.setItem('dam_audio_volume', v); } catch(_) {}
        },

        // ── Seek ──────────────────────────────────────────────────────
        audioOnSeekDown(e) {
            e.preventDefault();
            this.audioIsSeeking = true;
            this._audioSeekFromEvent(e);
            const move = (ev) => { if (this.audioIsSeeking) this._audioSeekFromEvent(ev); };
            const up   = () => {
                this.audioIsSeeking = false;
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup',   up);
            };
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup',   up);
        },

        _audioSeekFromEvent(e) {
            const container = this.$refs.audioSeekContainer;
            if (!container || !this.audioDuration) return;
            const rect = container.getBoundingClientRect();
            const pct  = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            const t    = pct * this.audioDuration;
            this.audioCurrentTime = t;
            if (this.$refs.audioEl) this.$refs.audioEl.currentTime = t;
        },

        audioOnSeekHover(e) {
            if (!this.audioDuration) return;
            const container = this.$refs.audioSeekContainer;
            if (!container) return;
            const rect = container.getBoundingClientRect();
            const relX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
            this.audioSeekTooltip        = this._formatTime((relX / rect.width) * this.audioDuration);
            this.audioSeekTooltipX       = relX;
            this.audioSeekTooltipVisible = true;
        },

        audioOnSeekLeave() { this.audioSeekTooltipVisible = false; },

        // ── Native audio events ───────────────────────────────────────
        audioOnTimeUpdate() {
            if (!this.$refs.audioEl || this.audioIsSeeking) return;
            this.audioCurrentTime = this.$refs.audioEl.currentTime;
        },

        audioOnLoadedMeta() {
            if (this.$refs.audioEl) this.audioDuration = this.$refs.audioEl.duration;
        },

        audioOnEnded() {
            this.audioIsPlaying = false;
            this.audioEnded = true;
            this.$nextTick(() => this._audioDrawRing());
        },

        // ── Ring canvas ───────────────────────────────────────────────
        _audioDrawRing() {
            const canvas = this.$refs.visualizerCanvas;
            if (!canvas) return;
            const size   = 208;
            canvas.width  = size;
            canvas.height = size;
            const ctx    = canvas.getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            const cx     = size / 2;
            const cy     = size / 2;
            const innerR = size * 0.33;
            const bins   = 128;
            const lineW  = Math.max(1.5, (2 * Math.PI * innerR / bins) * 0.68);
            const barH   = isDark ? 2.5 : 3.5;
            const light  = isDark ? 65 : 48;

            ctx.clearRect(0, 0, size, size);
            ctx.lineWidth  = lineW;
            ctx.lineCap    = 'round';
            ctx.shadowBlur = 0;

            for (let i = 0; i < bins; i++) {
                const angle = (i / bins) * Math.PI * 2 - Math.PI / 2;
                const cos   = Math.cos(angle);
                const sin   = Math.sin(angle);
                ctx.strokeStyle = `hsla(260, 78%, ${light}%, ${isDark ? 0.4 : 0.65})`;
                ctx.beginPath();
                ctx.moveTo(cx + cos * innerR,          cy + sin * innerR);
                ctx.lineTo(cx + cos * (innerR + barH), cy + sin * (innerR + barH));
                ctx.stroke();
            }
        },

        audioStopViz() {
            const canvas = this.$refs.visualizerCanvas;
            if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        },
    },
};
</script>
