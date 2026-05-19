<div
    class="relative w-full h-full overflow-hidden flex items-center justify-center select-none"
    @wheel.prevent="imgOnWheel"
    @mousedown="imgOnMouseDown"
    :class="imgIsDragging ? 'cursor-grabbing' : (imgZoom > 1 ? 'cursor-grab' : 'cursor-default')"
>
    <img
        src="{{ $asset->previewPath }}"
        alt="{{ $asset->file_name }}"
        class="max-w-none max-h-none block pointer-events-none"
        :style="{
            transform: imgTransformStyle,
            transformOrigin: 'center center',
            transition: imgIsDragging ? 'none' : 'transform 0.15s ease',
            maxHeight: '100%',
            maxWidth: '100%',
        }"
        draggable="false"
    />

    <!-- Toolbar overlay -->
    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1 px-3 py-1.5 rounded-full bg-black/60 text-white text-xs shadow-lg z-10 select-none">

        <!-- Rotate left -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.rotate-left') }}" @click="imgRotateLeft">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        </button>

        <!-- Rotate right -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.rotate-right') }}" @click="imgRotateRight">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
        </button>

        <span class="w-px h-4 bg-white/30 mx-1"></span>

        <!-- Zoom out -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.zoom-out') }}" @click="imgZoomOut">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
        </button>

        <!-- Zoom % -->
        <span class="min-w-[44px] text-center font-mono tabular-nums">@{{ imgZoomPercent }}%</span>

        <!-- Zoom in -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.zoom-in') }}" @click="imgZoomIn">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
        </button>

        <span class="w-px h-4 bg-white/30 mx-1"></span>

        <!-- Fit to screen -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.fit-to-screen') }}" @click="imgFitToScreen">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
        </button>

        <!-- 1:1 actual size -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors text-[11px] font-bold" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.actual-size') }}" @click="imgActualSize">1:1</button>

        <!-- Reset all -->
        <button type="button" class="flex items-center justify-center w-7 h-7 rounded hover:bg-white/20 transition-colors" title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.image-viewer.reset-all') }}" @click="imgReset">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
        </button>
    </div>
</div>
