<script type="module">
window._damVideoPlayer = {

    data: {
        videoSpeed:              1,
        videoIsPlaying:          false,
        videoCurrentTime:        0,
        videoDuration:           0,
        videoVolume:             1,
        videoEnded:              false,
        videoIsSeeking:          false,
        videoControlsVisible:    true,
        videoControlsTimer:      null,
        videoIsFullscreen:       false,
        videoIsMuted:            false,
        videoBuffered:           0,
        videoIsBuffering:        false,
        videoIsLooping:          false,
        videoClickFlash:         false,
        videoClickTimer:         null,
        videoSeekTooltip:        '',
        videoSeekTooltipX:       0,
        videoSeekTooltipVisible: false,
        videoSupportsPiP:        typeof document !== 'undefined' && 'pictureInPictureEnabled' in document,
        videoMenuOpen:           false,
        videoLinkCopied:         false,
    },

    computed: {
        videoCurrentTimeDisplay() { return this._formatTime(this.videoCurrentTime); },
        videoDurationDisplay()    { return this._formatTime(this.videoDuration); },
    },

    methods: {
        // ── Lifecycle helpers ─────────────────────────────────────────
        videoResetState() {
            this.videoSpeed = 1; this.videoIsPlaying = false;
            this.videoCurrentTime = 0; this.videoDuration = 0;
            this.videoVolume = 1; this.videoEnded = false;
            this.videoControlsVisible = true; clearTimeout(this.videoControlsTimer);
            this.videoIsMuted = false; this.videoIsLooping = false;
            this.videoIsBuffering = false; this.videoBuffered = 0;
            this.videoClickFlash = false;
            this.videoMenuOpen = false; this.videoLinkCopied = false;
            try { const sv = parseFloat(localStorage.getItem('dam_video_volume')); if (!isNaN(sv)) this.videoVolume = sv; } catch(_) {}
        },

        videoInitEl() {
            if (this.$refs.videoEl) {
                this.$refs.videoEl.pause();
                this.$refs.videoEl.currentTime = 0;
                this.$refs.videoEl.volume = this.videoVolume;
                this.$refs.videoEl.playbackRate = 1;
                this.$refs.videoEl.loop = false;
            }
            this.videoCurrentTime = 0;
        },

        videoStopOnClose() {
            if (this.$refs.videoEl) this.$refs.videoEl.pause();
            this.videoIsPlaying = false;
            this.videoMenuOpen = false;
            clearTimeout(this.videoControlsTimer);
        },

        videoMounted() {
            document.addEventListener('fullscreenchange', this.videoOnFullscreenChange);
        },

        videoBeforeUnmount() {
            document.removeEventListener('fullscreenchange', this.videoOnFullscreenChange);
        },

        // ── Playback controls ─────────────────────────────────────────
        setVideoSpeed(rate) {
            this.videoSpeed = rate;
            if (this.$refs.videoEl) this.$refs.videoEl.playbackRate = rate;
        },

        videoSkip(sec) {
            const el = this.$refs.videoEl;
            if (!el) return;
            el.currentTime = Math.max(0, Math.min(el.duration || 0, el.currentTime + sec));
            this.videoCurrentTime = el.currentTime;
        },

        videoShowControls() {
            this.videoControlsVisible = true;
            clearTimeout(this.videoControlsTimer);
            if (this.videoIsPlaying) {
                this.videoControlsTimer = setTimeout(() => { this.videoControlsVisible = false; }, 3000);
            }
        },

        videoKeepControls() {
            this.videoControlsVisible = true;
            clearTimeout(this.videoControlsTimer);
        },

        videoTogglePlay() {
            const el = this.$refs.videoEl;
            if (!el) return;
            if (this.videoEnded) { el.currentTime = 0; this.videoCurrentTime = 0; this.videoEnded = false; }
            if (el.paused) {
                el.play(); this.videoIsPlaying = true; this.videoShowControls();
            } else {
                el.pause(); this.videoIsPlaying = false; this.videoKeepControls();
            }
            this.videoClickFlash = true;
            clearTimeout(this._videoFlashTimer);
            this._videoFlashTimer = setTimeout(() => { this.videoClickFlash = false; }, 400);
        },

        videoOnClick() {
            clearTimeout(this.videoClickTimer);
            this.videoClickTimer = setTimeout(() => { this.videoTogglePlay(); }, 220);
        },

        videoOnDblClick() {
            clearTimeout(this.videoClickTimer);
            this.videoToggleFullscreen();
        },

        videoToggleFullscreen() {
            const container = this.$refs.videoContainer;
            if (!container) return;
            if (document.fullscreenElement) document.exitFullscreen();
            else container.requestFullscreen();
        },

        videoToggleMute() {
            const el = this.$refs.videoEl;
            if (!el) return;
            this.videoIsMuted = !this.videoIsMuted;
            el.muted = this.videoIsMuted;
        },

        videoToggleLoop() {
            this.videoIsLooping = !this.videoIsLooping;
            if (this.$refs.videoEl) this.$refs.videoEl.loop = this.videoIsLooping;
        },

        async videoTogglePiP() {
            const el = this.$refs.videoEl;
            if (!el) return;
            try {
                if (document.pictureInPictureElement) await document.exitPictureInPicture();
                else await el.requestPictureInPicture();
            } catch(_) {}
        },

        // ── Seek ──────────────────────────────────────────────────────
        videoOnSeekDown(e) {
            e.preventDefault();
            this.videoIsSeeking = true;
            this._seekFromEvent(e);
            const move = (ev) => { if (this.videoIsSeeking) this._seekFromEvent(ev); };
            const up   = () => {
                this.videoIsSeeking = false;
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup',   up);
            };
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup',   up);
        },

        _seekFromEvent(e) {
            const container = this.$refs.videoSeekContainer;
            if (!container || !this.videoDuration) return;
            const rect = container.getBoundingClientRect();
            const pct  = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            const t    = pct * this.videoDuration;
            this.videoCurrentTime = t;
            if (this.$refs.videoEl) this.$refs.videoEl.currentTime = t;
        },

        videoOnSeekHover(e) {
            if (!this.videoDuration) return;
            const container = this.$refs.videoSeekContainer;
            if (!container) return;
            const rect = container.getBoundingClientRect();
            const relX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
            this.videoSeekTooltip        = this._formatTime((relX / rect.width) * this.videoDuration);
            this.videoSeekTooltipX       = relX;
            this.videoSeekTooltipVisible = true;
        },

        videoOnSeekLeave() { this.videoSeekTooltipVisible = false; },

        // ── Volume ────────────────────────────────────────────────────
        videoOnVolume(e) {
            const v = parseFloat(e.target.value);
            this.videoVolume = v;
            if (this.$refs.videoEl) this.$refs.videoEl.volume = v;
            try { localStorage.setItem('dam_video_volume', v); } catch(_) {}
        },

        // ── Native video events ───────────────────────────────────────
        videoOnTimeUpdate() {
            const el = this.$refs.videoEl;
            if (!el || this.videoIsSeeking) return;
            this.videoCurrentTime = el.currentTime;
        },

        videoOnLoadedMeta() {
            if (this.$refs.videoEl) this.videoDuration = this.$refs.videoEl.duration;
        },

        videoOnEnded() {
            this.videoIsPlaying = false;
            if (!this.videoIsLooping) this.videoEnded = true;
        },

        videoOnFullscreenChange() {
            this.videoIsFullscreen = !!document.fullscreenElement;
        },

        videoOnProgress() {
            const el = this.$refs.videoEl;
            if (!el || !el.buffered.length || !el.duration) { this.videoBuffered = 0; return; }
            this.videoBuffered = (el.buffered.end(el.buffered.length - 1) / el.duration) * 100;
        },

        videoOnWaiting() { this.videoIsBuffering = true; },

        videoOnCanPlay() { this.videoIsBuffering = false; },

        // ── Action menu ───────────────────────────────────────────────
        videoCopyLink(url) {
            url = url || window.location.href;
            const done = () => {
                this.videoLinkCopied = true;
                setTimeout(() => { this.videoLinkCopied = false; this.videoMenuOpen = false; }, 1500);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done).catch(() => this._videoCopyFallback(url, done));
            } else {
                this._videoCopyFallback(url, done);
            }
        },

        _videoCopyFallback(text, done) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
            document.body.appendChild(ta);
            ta.focus(); ta.select();
            try { document.execCommand('copy'); done(); } catch(_) {}
            document.body.removeChild(ta);
        },
    },
};
</script>
