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
</style>
@endPushOnce

<div class="relative flex flex-col items-center justify-center gap-4 w-full h-full p-6 mt-4">
    <div class="absolute inset-x-0 top-0 h-48 pointer-events-none" style="background: linear-gradient(to bottom, rgba(139,92,246,0.07) 0%, transparent 100%);"></div>
    <!-- Circular disc -->
    <div class="flex items-center justify-center h-52 w-52 shrink-0">

        <!-- Canvas ring + pulse rings -->
        <div class="relative flex items-center justify-center h-52 w-52">
            <!-- Ambient glow -->
            <div
                class="absolute inset-0 rounded-full pointer-events-none"
                :style="{ background: audioIsPlaying ? 'radial-gradient(circle, rgba(139,92,246,0.18) 0%, transparent 70%)' : 'radial-gradient(circle, rgba(139,92,246,0.10) 0%, transparent 70%)' }"
            ></div>
            <canvas
                v-show="!audioIsPlaying"
                ref="visualizerCanvas"
                class="audio-canvas-ring absolute pointer-events-none"
                style="top:50%;left:50%;transform:translate(-50%,-50%);width:208px;height:208px;clip-path:circle(50%);"
                width="208"
                height="208"
            ></canvas>
            <div class="audio-blob-rings" v-show="audioIsPlaying">
                <span class="audio-ring-1"></span>
                <span class="audio-ring-2"></span>
            </div>

            <!-- Fallback image — circular disc (front) -->
            <div class="relative z-10 h-28 w-28 rounded-full bg-violet-50 dark:bg-gray-800 ring-2 ring-violet-200 dark:ring-violet-900 shadow-lg flex items-center justify-center overflow-hidden" :class="audioIsPlaying ? 'audio-disc-spinning' : ''">
                <img
                    src="{{ $coverArtUrl ?? $placeholderSvg }}"
                    alt="{{ $asset->file_name }}"
                    class="h-full w-full object-cover{{ $coverArtUrl ? '' : ' opacity-75' }}"
                    @if(! $coverArtUrl) style="transform: scale(1.2);" @endif
                />
            </div>
        </div>
    </div>

    <p class="text-sm font-medium mt-6 text-gray-700 dark:text-gray-300 truncate max-w-xl text-center">{{ $asset->file_name }}</p>

    <!-- Hidden native audio element driven by Vue -->
    <audio
        ref="audioEl"
        class="hidden"
        @timeupdate="audioOnTimeUpdate"
        @loadedmetadata="audioOnLoadedMeta"
        @ended="audioOnEnded"
    >
        <source src="{{ $mediaUrl }}" type="{{ $asset->mime_type }}">
    </audio>

    <div class="flex flex-col gap-2 w-full max-w-lg">

        <!-- Custom seek bar -->
        <div
            ref="audioSeekContainer"
            class="relative h-4 group cursor-pointer"
            @mousedown="audioOnSeekDown"
            @mousemove="audioOnSeekHover"
            @mouseleave="audioOnSeekLeave"
        >
            <!-- Tooltip -->
            <div
                v-show="audioSeekTooltipVisible && audioDuration"
                class="absolute px-1.5 py-0.5 rounded shadow-sm border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-mono pointer-events-none whitespace-nowrap z-10"
                :style="{ left: audioSeekTooltipX + 'px', bottom: 'calc(100% + 8px)', transform: 'translateX(-50%)' }"
            >@{{ audioSeekTooltip }}</div>

            <!-- Track: full bg / played -->
            <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div
                    class="absolute inset-y-0 left-0 bg-violet-500"
                    :style="{ width: (audioDuration ? (audioCurrentTime / audioDuration) * 100 : 0) + '%' }"
                ></div>
            </div>

            <!-- Playhead thumb -->
            <div
                class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-violet-600 shadow pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity"
                :style="{ left: (audioDuration ? (audioCurrentTime / audioDuration) * 100 : 0) + '%' }"
            ></div>
        </div>

        <!-- Time display -->
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 font-mono tabular-nums">
            <span>@{{ audioCurrentTimeDisplay }}</span>
            <span>@{{ audioDurationDisplay }}</span>
        </div>

        <!-- Controls row -->
        <div class="flex items-center justify-center gap-3 mt-1">

            <!-- Volume with mute button -->
            <div class="flex items-center gap-1.5 mr-auto">
                <button
                    type="button"
                    class="shrink-0 text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                    title="@lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.mute')"
                    @click="audioToggleMute"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                        <template v-if="!audioIsMuted">
                            <path v-if="audioVolume > 0.5" d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>
                            <path v-else-if="audioVolume > 0" d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
                        </template>
                        <template v-else>
                            <line x1="23" y1="9" x2="17" y2="15"/>
                            <line x1="17" y1="9" x2="23" y2="15"/>
                        </template>
                    </svg>
                </button>
                <input
                    type="range"
                    class="w-20 h-1 accent-violet-400 cursor-pointer"
                    min="0"
                    max="1"
                    step="0.01"
                    :value="audioIsMuted ? 0 : audioVolume"
                    @input="audioOnVolume"
                />
            </div>

            <!-- Skip back 10s -->
            <button
                type="button"
                class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-700 transition-colors shrink-0"
                title="@lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.back-10s')"
                @click="audioSkip(-10)"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
                </svg>
                @lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.10s')
            </button>

            <!-- Play / Pause -->
            <button
                type="button"
                class="flex items-center justify-center w-14 h-14 rounded-full bg-violet-600 hover:bg-violet-700 text-white shadow-md transition-colors shrink-0"
                @click="audioTogglePlay"
            >
                <svg v-if="audioIsPlaying" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
            </button>

            <!-- Skip forward 10s -->
            <button
                type="button"
                class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-gray-100 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-700 transition-colors shrink-0"
                title="@lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.forward-10s')"
                @click="audioSkip(10)"
            >
                @lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.10s')
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
                </svg>
            </button>

            <!-- Loop toggle -->
            <button
                type="button"
                class="flex items-center justify-center w-7 h-7 rounded transition-colors shrink-0 ml-auto"
                :class="audioIsLooping ? 'bg-violet-600 text-white' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
                title="@lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.loop')"
                @click="audioToggleLoop"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                    <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
            </button>
        </div>

        <!-- Speed selector -->
        <div class="flex items-center gap-1 justify-center mt-1">
            <span class="text-xs text-gray-400 dark:text-gray-500 mr-1">@lang('dam::app.admin.dam.asset.edit.preview-modal.video-player.speed')</span>
            <template v-for="rate in [0.5, 0.75, 1, 1.25, 1.5, 2]" :key="rate">
                <button
                    type="button"
                    class="px-2 py-1 rounded text-xs font-semibold transition-colors"
                    :class="audioSpeed === rate ? 'bg-violet-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                    @click="setAudioSpeed(rate)"
                >@{{ rate }}×</button>
            </template>
        </div>
    </div>
</div>
