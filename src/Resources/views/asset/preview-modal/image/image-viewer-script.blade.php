<script type="module">
window._damImageViewer = {

    data: {
        // Crop / Resize
        cropWidth:  null,
        cropHeight: null,

        // Crop overlay (drag interaction)
        cropBox:        { x: 0, y: 0, w: 0, h: 0 },
        cropHandle:     null,
        cropStart:      null,
        cropImgW:       0,
        cropImgH:       0,
        cropNatW:       0,
        cropNatH:       0,
        cropImgOffsetX: 0,
        cropImgOffsetY: 0,

        // Brightness, Contrast, Sharpen, Blur
        brightness: 0,
        contrast:   0,
        sharpen:    0,
        blur:       0,

        // Filters
        filterGreyscale: false,
        filterInvert:    false,

        // Edit Background
        bgSubTab:             'color',
        bgColorMode:          '{{ $asset->extension === "png" ? "normal" : "ai" }}',
        bgColor:              null,
        bgPreviewDataUrl:     null,
        bgPreviewLoading:     false,
        bgPreviewTimer:       null,
        bgUploadFile:         null,
        bgAiPrompt:           '',
        bgPlatforms:          [],
        bgSelectedPlatformId: null,
        bgSelectedModel:      null,
        bgPlatformError:      null,
        bgSwatches: {!! json_encode([
            ['hex' => null,      'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-none')],
            ['hex' => '#ffffff', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-white')],
            ['hex' => '#f3f4f6', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-silver')],
            ['hex' => '#e5e7eb', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-gray')],
            ['hex' => '#9ca3af', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-gray')],
            ['hex' => '#6b7280', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-slate')],
            ['hex' => '#374151', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-dark-gray')],
            ['hex' => '#1f2937', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-charcoal')],
            ['hex' => '#000000', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-black')],
            ['hex' => '#fee2e2', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-red')],
            ['hex' => '#ef4444', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-red')],
            ['hex' => '#fef3c7', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-yellow')],
            ['hex' => '#fbbf24', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-yellow')],
            ['hex' => '#d1fae5', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-green')],
            ['hex' => '#22c55e', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-green')],
            ['hex' => '#cffafe', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-cyan')],
            ['hex' => '#06b6d4', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-cyan')],
            ['hex' => '#dbeafe', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-blue')],
            ['hex' => '#3b82f6', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-blue')],
            ['hex' => '#ede9fe', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-purple')],
            ['hex' => '#8b5cf6', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-purple')],
            ['hex' => '#fce7f3', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-pink')],
            ['hex' => '#ec4899', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-pink')],
            ['hex' => '#fff1f2', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-light-rose')],
            ['hex' => '#f43f5e', 'name' => trans('dam::app.admin.dam.asset.edit.image-editor.color-rose')],
        ]) !!},

        // Rotate & Flip
        rotation: 0,
        flipH:    false,
        flipV:    false,

        // Image editor
        editTool:     null,
        editApplying: false,
        editError:    null,

        // Image viewer
        imgZoom:       1,
        imgRotation:   0,
        imgPanX:       0,
        imgPanY:       0,
        imgIsDragging: false,
        imgDragStartX: 0,
        imgDragStartY: 0,
        imgPanStartX:  0,
        imgPanStartY:  0,
    },

    computed: {
        imgTransformStyle() {
            return `translate(${this.imgPanX}px,${this.imgPanY}px) scale(${this.imgZoom}) rotate(${this.imgRotation}deg)`;
        },
        imgZoomPercent() {
            return Math.round(this.imgZoom * 100);
        },
        cropPixelW() {
            return this.cropImgW ? Math.round(this.cropBox.w * this.cropNatW / this.cropImgW) : 0;
        },
        cropPixelH() {
            return this.cropImgH ? Math.round(this.cropBox.h * this.cropNatH / this.cropImgH) : 0;
        },
        bgCurrentPlatformModels() {
            const p = this.bgPlatforms.find(p => p.id === this.bgSelectedPlatformId);
            if (!p) return [];
            const imagePatterns = {
                openai: ['gpt-image'],
                gemini: ['gemini-2', 'imagen'],
                xai:    ['grok'],
            };
            const patterns = imagePatterns[p.provider] || [];
            if (!patterns.length) return p.models || [];
            return (p.models || []).filter(m => patterns.some(pat => m.toLowerCase().includes(pat)));
        },
        editPreviewFilter() {
            const parts = [];
            if (this.editTool === 'adjust') {
                parts.push(`brightness(${(100 + this.brightness) / 100})`);
                parts.push(`contrast(${(100 + this.contrast) / 100})`);
                if (this.blur > 0) parts.push(`blur(${(this.blur * 0.3).toFixed(1)}px)`);
            }
            if (this.editTool === 'filters') {
                if (this.filterGreyscale) parts.push('grayscale(1)');
                if (this.filterInvert)    parts.push('invert(1)');
            }
            return parts.join(' ');
        },
        editPreviewTransform() {
            if (this.editTool !== 'rotate') return '';
            const sx = this.flipH ? -1 : 1;
            const sy = this.flipV ? -1 : 1;
            return `rotate(${this.rotation}deg) scaleX(${sx}) scaleY(${sy})`;
        },
    },

    methods: {
        // ── Lifecycle helpers ─────────────────────────────────────────
        imgResetState() {
            this.imgZoom = 1; this.imgRotation = 0;
            this.imgPanX = 0; this.imgPanY = 0; this.imgIsDragging = false;
        },

        imgMounted() {
            window.addEventListener('mousemove', this.imgOnMouseMove);
            window.addEventListener('mouseup',   this.imgOnMouseUp);
            window.addEventListener('mousemove', this.cropMouseMove);
            window.addEventListener('mouseup',   this.cropMouseUp);

            const url = new URL(window.location.href);
            if (url.searchParams.has('_img')) {
                url.searchParams.delete('_img');
                history.replaceState(null, '', url.toString());
            }
        },

        imgBeforeUnmount() {
            window.removeEventListener('mousemove', this.imgOnMouseMove);
            window.removeEventListener('mouseup',   this.imgOnMouseUp);
            window.removeEventListener('mousemove', this.cropMouseMove);
            window.removeEventListener('mouseup',   this.cropMouseUp);
        },

        // ── Image viewer ──────────────────────────────────────────────
        imgZoomIn()      { this.imgZoom = Math.min(10,  parseFloat((this.imgZoom + 0.25).toFixed(2))); },
        imgZoomOut()     { this.imgZoom = Math.max(0.1, parseFloat((this.imgZoom - 0.25).toFixed(2))); },
        imgRotateRight() { this.imgRotation = (this.imgRotation + 90) % 360; },
        imgRotateLeft()  { this.imgRotation = (this.imgRotation - 90 + 360) % 360; },
        imgFitToScreen() { this.imgZoom = 1; this.imgPanX = 0; this.imgPanY = 0; },
        imgActualSize()  { this.imgZoom = 1; this.imgPanX = 0; this.imgPanY = 0; },
        imgReset()       { this.imgZoom = 1; this.imgRotation = 0; this.imgPanX = 0; this.imgPanY = 0; },

        imgOnWheel(e) {
            const factor = e.deltaY < 0 ? 1.1 : 0.9;
            this.imgZoom = Math.min(10, Math.max(0.1, parseFloat((this.imgZoom * factor).toFixed(3))));
        },

        imgOnMouseDown(e) {
            if (e.button !== 0) return;
            this.imgIsDragging = true;
            this.imgDragStartX = e.clientX; this.imgDragStartY = e.clientY;
            this.imgPanStartX  = this.imgPanX; this.imgPanStartY = this.imgPanY;
            e.preventDefault();
        },

        imgOnMouseMove(e) {
            if (!this.imgIsDragging) return;
            this.imgPanX = this.imgPanStartX + (e.clientX - this.imgDragStartX);
            this.imgPanY = this.imgPanStartY + (e.clientY - this.imgDragStartY);
        },

        imgOnMouseUp() { this.imgIsDragging = false; },

        // ── Crop overlay ──────────────────────────────────────────────
        initCropBox() {
            this.$nextTick(() => {
                const img = this.$refs.editImg;
                if (!img) return;
                const doInit = () => {
                    this.cropImgW       = img.offsetWidth;
                    this.cropImgH       = img.offsetHeight;
                    this.cropNatW       = img.naturalWidth  || img.offsetWidth;
                    this.cropNatH       = img.naturalHeight || img.offsetHeight;
                    this.cropImgOffsetX = img.offsetLeft;
                    this.cropImgOffsetY = img.offsetTop;
                    const padX          = Math.round(this.cropImgW * 0.1);
                    const padY          = Math.round(this.cropImgH * 0.1);
                    this.cropBox        = { x: padX, y: padY, w: this.cropImgW - 2 * padX, h: this.cropImgH - 2 * padY };
                };
                if (img.complete && img.naturalWidth > 0) {
                    doInit();
                } else {
                    img.addEventListener('load', doInit, { once: true });
                }
            });
        },

        cropMouseDown(e, handle) {
            this.cropHandle = handle;
            this.cropStart  = { mx: e.clientX, my: e.clientY, box: { ...this.cropBox } };
        },

        cropStartDraw(e) {
            if (!this.cropImgW) return;
            e.preventDefault();
            const img = this.$refs.editImg;
            if (!img) return;
            const rect      = img.getBoundingClientRect();
            const imgStartX = Math.max(0, Math.min(this.cropImgW, e.clientX - rect.left));
            const imgStartY = Math.max(0, Math.min(this.cropImgH, e.clientY - rect.top));
            this.cropBox    = { x: imgStartX, y: imgStartY, w: 0, h: 0 };
            this.cropHandle = 'draw';
            this.cropStart  = {
                mx: e.clientX, my: e.clientY,
                box: { x: imgStartX, y: imgStartY, w: 0, h: 0 },
                imgLeft: rect.left, imgTop: rect.top,
                imgStartX, imgStartY,
            };
        },

        cropMouseMove(e) {
            if (!this.cropHandle || !this.cropStart) return;

            if (this.cropHandle === 'draw') {
                const { imgLeft, imgTop, imgStartX: sx, imgStartY: sy } = this.cropStart;
                const cx = Math.max(0, Math.min(this.cropImgW, e.clientX - imgLeft));
                const cy = Math.max(0, Math.min(this.cropImgH, e.clientY - imgTop));
                this.cropBox = {
                    x: Math.min(cx, sx),
                    y: Math.min(cy, sy),
                    w: Math.max(1, Math.abs(cx - sx)),
                    h: Math.max(1, Math.abs(cy - sy)),
                };
                return;
            }

            const dx = e.clientX - this.cropStart.mx;
            const dy = e.clientY - this.cropStart.my;
            const { x, y, w, h } = this.cropStart.box;
            const MIN = 20;
            let nx = x, ny = y, nw = w, nh = h;

            if (this.cropHandle === 'move') {
                nx = Math.max(0, Math.min(this.cropImgW - w, x + dx));
                ny = Math.max(0, Math.min(this.cropImgH - h, y + dy));
            } else {
                if (this.cropHandle.includes('l')) { nw = w - dx; nx = x + (w - nw); }
                if (this.cropHandle.includes('r')) { nw = w + dx; }
                if (this.cropHandle.includes('t')) { nh = h - dy; ny = y + (h - nh); }
                if (this.cropHandle.includes('b')) { nh = h + dy; }
                nw = Math.max(MIN, nw); nh = Math.max(MIN, nh);
                if (this.cropHandle.includes('l')) nx = x + w - nw;
                if (this.cropHandle.includes('t')) ny = y + h - nh;
                if (nx < 0) { nw += nx; nx = 0; }
                if (ny < 0) { nh += ny; ny = 0; }
                if (nx + nw > this.cropImgW) nw = this.cropImgW - nx;
                if (ny + nh > this.cropImgH) nh = this.cropImgH - ny;
            }

            this.cropBox = { x: nx, y: ny, w: Math.max(MIN, nw), h: Math.max(MIN, nh) };
        },

        cropMouseUp() {
            if (this.cropHandle === 'draw') {
                const MIN = 20;
                if (this.cropBox.w < MIN || this.cropBox.h < MIN) {
                    this.cropBox = { x: 0, y: 0, w: this.cropImgW, h: this.cropImgH };
                }
            }
            this.cropHandle = null;
            this.cropStart  = null;
        },

        // ── Image Editor ──────────────────────────────────────────────
        onEditToolSelect(tool) {
            if (this.editTool === tool) {
                this.editTool = null;
                return;
            }
            this.editTool        = tool;
            this.editError       = null;
            this.editApplying    = false;
            this.cropWidth       = null;
            this.cropHeight      = null;
            this.brightness      = 0;
            this.contrast        = 0;
            this.sharpen         = 0;
            this.blur            = 0;
            this.filterGreyscale = false;
            this.filterInvert    = false;
            this.rotation        = 0;
            this.flipH           = false;
            this.flipV           = false;
            this.bgSubTab        = 'color';
            this.bgColorMode     = '{{ $asset->extension === "png" ? "normal" : "ai" }}';
            this.bgColor         = null;
            this.bgUploadFile    = null;
            this.bgAiPrompt      = '';
            this.bgPlatformError = null;
            this.bgPreviewDataUrl = null;
            this.bgPreviewLoading = false;
            clearTimeout(this.bgPreviewTimer);
            if (tool === 'crop')    this.initCropBox();
            if (tool === 'edit-bg' && !(this.bgSubTab === 'color' && this.bgColorMode === 'normal')) this.loadBgPlatforms();
        },

        scheduleBgPreview(color) {
            clearTimeout(this.bgPreviewTimer);
            this.bgPreviewTimer = setTimeout(() => this.fetchBgPreview(color), 400);
        },

        async fetchBgPreview(color) {
            if (!/^#[0-9a-fA-F]{6}$/.test(color)) return;
            this.bgPreviewLoading = true;
            try {
                const res = await window.axios.post(
                    '{{ route('admin.dam.assets.image_edit.bg_preview', $asset->id) }}',
                    { color }
                );
                this.bgPreviewDataUrl = res.data.dataUrl;
            } catch (_) {
                // silent — original image stays visible
            } finally {
                this.bgPreviewLoading = false;
            }
        },

        async loadBgPlatforms() {
            if (this.bgPlatforms.length) return;
            try {
                const { data } = await window.axios.get('{{ route('admin.magic_ai.platforms') }}');
                const imagePlatforms = (data.platforms || []).filter(p => ['openai', 'gemini', 'xai'].includes(p.provider));
                this.bgPlatforms = imagePlatforms;
                if (!this.bgPlatforms.length) {
                    this.bgPlatformError = '{{ trans('dam::app.admin.dam.asset.edit.image-editor.error-no-image-platform') }}';
                    return;
                }
                const def = this.bgPlatforms.find(p => p.is_default) || this.bgPlatforms[0];
                if (def) {
                    this.bgSelectedPlatformId = def.id;
                    this.bgSelectedModel      = this.bgCurrentPlatformModels[0] || null;
                }
                if (!this.bgSelectedModel) {
                    this.bgPlatformError = '{{ trans('dam::app.admin.dam.asset.edit.image-editor.no-models') }}';
                }
            } catch (e) {
                this.bgPlatformError = '{{ trans('dam::app.admin.dam.asset.edit.image-editor.error-platforms') }}';
            }
        },

        onBgPlatformChange() {
            this.bgSelectedModel = this.bgCurrentPlatformModels[0] || null;
            this.bgPlatformError = this.bgSelectedModel
                ? null
                : '{{ trans('dam::app.admin.dam.asset.edit.image-editor.no-models') }}';
        },

        async applyEdit() {
            if (!this.editTool || this.editApplying) return;
            this.editApplying = true;
            this.editError    = null;

            const routeMap = {
                crop:    '{{ route('admin.dam.assets.image_edit.resize',    ['id' => $asset->id]) }}',
                adjust:  '{{ route('admin.dam.assets.image_edit.adjust',    ['id' => $asset->id]) }}',
                rotate:  '{{ route('admin.dam.assets.image_edit.transform', ['id' => $asset->id]) }}',
                filters: '{{ route('admin.dam.assets.image_edit.filters',   ['id' => $asset->id]) }}',
            };

            const bodyMap = {
                crop: () => ({
                    crop_x:    Math.round(this.cropBox.x * this.cropNatW / this.cropImgW),
                    crop_y:    Math.round(this.cropBox.y * this.cropNatH / this.cropImgH),
                    crop_w:    this.cropPixelW,
                    crop_h:    this.cropPixelH,
                    img_nat_w: this.cropNatW,
                    img_nat_h: this.cropNatH,
                    width:     this.cropWidth  || null,
                    height:    this.cropHeight || null,
                }),
                adjust:  () => ({ brightness: this.brightness, contrast: this.contrast, sharpen: this.sharpen, blur: this.blur }),
                rotate:  () => ({ rotation: this.rotation, flip_h: this.flipH, flip_v: this.flipV }),
                filters: () => ({ greyscale: this.filterGreyscale, invert: this.filterInvert }),
            };

            let postRoute, postBody;

            if (this.editTool === 'edit-bg') {
                const needsPlatform = !(this.bgSubTab === 'color' && this.bgColorMode === 'normal');
                if (needsPlatform && (!this.bgSelectedPlatformId || !this.bgSelectedModel)) {
                    this.editError    = '{{ trans('dam::app.admin.dam.asset.edit.image-editor.error-platforms') }}';
                    this.editApplying = false;
                    return;
                }
                const bgRoutes = {
                    color:  '{{ route('admin.dam.assets.image_edit.bg_color',  ['id' => $asset->id]) }}',
                    upload: '{{ route('admin.dam.assets.image_edit.bg_upload', ['id' => $asset->id]) }}',
                    ai:     '{{ route('admin.dam.assets.image_edit.bg_ai',     ['id' => $asset->id]) }}',
                };
                if (this.bgSubTab !== 'color') postRoute = bgRoutes[this.bgSubTab];
                if (this.bgSubTab === 'color') {
                    if (!this.bgColor) {
                        this.editError    = '{{ trans('dam::app.admin.dam.asset.edit.image-editor.error-select-color') }}';
                        this.editApplying = false;
                        return;
                    }
                    if (this.bgColorMode === 'normal') {
                        postRoute = '{{ route('admin.dam.assets.image_edit.bg_color_normal', ['id' => $asset->id]) }}';
                        postBody  = { color: this.bgColor };
                    } else {
                        postRoute = bgRoutes.color;
                        postBody  = { color: this.bgColor, platform_id: this.bgSelectedPlatformId, model: this.bgSelectedModel };
                    }
                } else if (this.bgSubTab === 'upload') {
                    const fd = new FormData();
                    fd.append('image',       this.bgUploadFile);
                    fd.append('platform_id', this.bgSelectedPlatformId);
                    fd.append('model',       this.bgSelectedModel);
                    postBody = fd;
                } else {
                    postBody = { prompt: this.bgAiPrompt, platform_id: this.bgSelectedPlatformId, model: this.bgSelectedModel };
                }
            } else {
                postRoute = routeMap[this.editTool];
                postBody  = bodyMap[this.editTool]();
            }

            try {
                await window.axios.post(postRoute, postBody);
                this.isEditOpen = false;
                this.editTool   = null;
                const url = new URL(window.location.href);
                url.searchParams.set('_img', Date.now());
                window.location.replace(url.toString());
            } catch (e) {
                this.editError = e?.response?.data?.message || '{{ trans('dam::app.admin.dam.asset.edit.image-editor.error-operation') }}';
            } finally {
                this.editApplying = false;
            }
        },
    },
};
</script>
