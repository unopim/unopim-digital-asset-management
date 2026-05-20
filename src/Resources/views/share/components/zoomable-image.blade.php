{{--
    Reusable zoomable image viewer.

    Usage:
        <v-zoomable-image
            src="/path/to/image.jpg"
            alt="filename"
            container-class="max-h-[70vh]"
        ></v-zoomable-image>

    Behaviours:
      - Mouse wheel zooms (clamped to 10%–1000%)
      - Click-drag pans when zoomed in
      - Toolbar overlay: rotate L/R, zoom -/+, fit, 1:1, reset
      - Double-click toggles 1:1 / fit
--}}
<script type="text/x-template" id="v-zoomable-image-template">
    <div
        class="flex flex-col w-full h-full overflow-hidden select-none bg-gray-50 dark:bg-cherry-800 rounded"
        :class="containerClass"
    >
        <!-- Image area -->
        <div
            class="relative flex-1 min-h-0 overflow-hidden flex items-center justify-center"
            @wheel.prevent="onWheel"
            @mousedown="onMouseDown"
            @dblclick="toggleActualSize"
        >
            <img
                :src="src"
                :alt="alt"
                class="max-w-none max-h-none block pointer-events-none"
                :style="imgStyle"
                draggable="false"
            />
        </div>

        <!-- Toolbar row (sits below the image, not overlaying it) -->
        <div class="flex items-center justify-center gap-1 px-3 py-2 border-t border-gray-200 dark:border-cherry-700 bg-white dark:bg-cherry-900 text-gray-700 dark:text-gray-200 text-xs">
            <!-- Rotate left -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors"
                :title="t.rotateLeft"
                @click.stop="rotateLeft"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            </button>

            <!-- Rotate right -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors"
                :title="t.rotateRight"
                @click.stop="rotateRight"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
            </button>

            <span class="w-px h-4 bg-gray-300 dark:bg-cherry-700 mx-1"></span>

            <!-- Zoom out -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors"
                :title="t.zoomOut"
                @click.stop="zoomOut"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </button>

            <!-- Zoom percent -->
            <span class="min-w-[44px] text-center font-mono tabular-nums">@{{ zoomPercent }}%</span>

            <!-- Zoom in -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors"
                :title="t.zoomIn"
                @click.stop="zoomIn"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
            </button>

            <span class="w-px h-4 bg-gray-300 dark:bg-cherry-700 mx-1"></span>

            <!-- Fit -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors"
                :title="t.fit"
                @click.stop="fitToScreen"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            </button>

            <!-- 1:1 -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors text-[11px] font-bold"
                :title="t.actualSize"
                @click.stop="actualSize"
            >1:1</button>

            <!-- Reset -->
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-100 dark:hover:bg-cherry-800 transition-colors"
                :title="t.reset"
                @click.stop="reset"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
            </button>
        </div>
    </div>
</script>

<script type="module">
    app.component('v-zoomable-image', {
        template: '#v-zoomable-image-template',
        props: {
            src:            { type: String, required: true },
            alt:            { type: String, default: '' },
            containerClass: { type: String, default: '' },
        },
        data() {
            return {
                zoom:        1,
                rotation:    0,
                panX:        0,
                panY:        0,
                isDragging:  false,
                dragStartX:  0,
                dragStartY:  0,
                panStartX:   0,
                panStartY:   0,
                t: {
                    rotateLeft:  @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.rotate-left')),
                    rotateRight: @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.rotate-right')),
                    zoomOut:     @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.zoom-out')),
                    zoomIn:      @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.zoom-in')),
                    fit:         @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.fit-to-screen')),
                    actualSize:  @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.actual-size')),
                    reset:       @js(trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.reset-all')),
                },
            };
        },
        computed: {
            zoomPercent() { return Math.round(this.zoom * 100); },
            imgStyle() {
                return {
                    transform: `translate(${this.panX}px, ${this.panY}px) scale(${this.zoom}) rotate(${this.rotation}deg)`,
                    transformOrigin: 'center center',
                    transition: this.isDragging ? 'none' : 'transform 0.15s ease',
                    maxHeight: '100%',
                    maxWidth:  '100%',
                    cursor: this.isDragging ? 'grabbing' : (this.zoom > 1 ? 'grab' : 'default'),
                };
            },
        },
        mounted() {
            window.addEventListener('mousemove', this.onMouseMove);
            window.addEventListener('mouseup',   this.onMouseUp);
        },
        beforeUnmount() {
            window.removeEventListener('mousemove', this.onMouseMove);
            window.removeEventListener('mouseup',   this.onMouseUp);
        },
        watch: {
            src() { this.reset(); },
        },
        methods: {
            zoomIn()      { this.zoom = Math.min(10,  parseFloat((this.zoom + 0.25).toFixed(2))); },
            zoomOut()     { this.zoom = Math.max(0.1, parseFloat((this.zoom - 0.25).toFixed(2))); },
            rotateLeft()  { this.rotation = (this.rotation - 90 + 360) % 360; },
            rotateRight() { this.rotation = (this.rotation + 90) % 360; },
            fitToScreen() { this.zoom = 1; this.panX = 0; this.panY = 0; },
            actualSize()  { this.zoom = 1; this.panX = 0; this.panY = 0; },
            reset()       { this.zoom = 1; this.rotation = 0; this.panX = 0; this.panY = 0; this.isDragging = false; },
            toggleActualSize() {
                if (this.zoom === 1) this.zoom = 2;
                else this.fitToScreen();
            },
            onWheel(e) {
                const factor = e.deltaY < 0 ? 1.1 : 0.9;
                this.zoom = Math.min(10, Math.max(0.1, parseFloat((this.zoom * factor).toFixed(3))));
            },
            onMouseDown(e) {
                if (e.button !== 0) return;
                if (this.zoom <= 1) return;
                this.isDragging = true;
                this.dragStartX = e.clientX;
                this.dragStartY = e.clientY;
                this.panStartX  = this.panX;
                this.panStartY  = this.panY;
                e.preventDefault();
            },
            onMouseMove(e) {
                if (!this.isDragging) return;
                this.panX = this.panStartX + (e.clientX - this.dragStartX);
                this.panY = this.panStartY + (e.clientY - this.dragStartY);
            },
            onMouseUp() {
                this.isDragging = false;
            },
        },
    });
</script>
