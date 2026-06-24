@once('v-dam-explorer-grid')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-grid-template">
    {{-- Outer div catches right-click and drops on empty space (item events use .stop) --}}
    <div class="pr-4 min-h-72" @contextmenu.prevent="showSpaceCtx($event)" @dragover.prevent @drop.prevent="onSpaceDrop($event)">
        {{-- Shimmer --}}
        <div v-if="isLoading" class="grid grid-cols-2 md:!grid-cols-3 xl:!grid-cols-4 2xl:!grid-cols-5 gap-4 animate-pulse">
            <div v-for="n in 10" :key="n" class="aspect-square bg-gray-100 dark:bg-cherry-800 rounded-lg"></div>
        </div>

        <template v-else>
            {{-- Folders --}}
            <template v-if="directories.length">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
                    @lang('dam::app.admin.explorer.sections.folders')
                </p>
                <div class="grid grid-cols-[repeat(auto-fill,96px)] gap-1 mb-6">
                    <v-dam-explorer-folder-card
                        v-for="dir in directories"
                        :key="dir.id"
                        :dir="dir"
                        :is-drop-target="dropTargetId === dir.id"
                        :data-dir-id="dir.id"
                        :is-selected="isSelectedById(dir.id, 'directory')"
                        :any-selected="selection.ids.length > 0"
                        @toggle-select="$emit('toggle-select', dir.id, 'directory')"
                        @navigate="$emit('navigate', $event)"
                        @ctx="showCtx($event.event, $event.dir, 'directory')"
                        @drag-start="onDragStart($event, dir)"
                        @drag-end="onDragEnd"
                        @drag-over="onFolderDragOver($event, dir)"
                        @drag-leave="onFolderDragLeave($event, dir)"
                        @drop="onInternalDrop($event, dir)"
                    ></v-dam-explorer-folder-card>
                </div>
            </template>

            {{-- Assets --}}
            <template v-if="assets.length">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
                    @lang('dam::app.admin.explorer.sections.files')
                </p>
                <div class="grid grid-cols-2 md:!grid-cols-3 xl:!grid-cols-4 2xl:!grid-cols-5 gap-4">
                    <v-dam-asset-card
                        v-for="asset in assets"
                        :key="asset.id"
                        :asset="asset"
                        :tab-id="tabId"
                        :is-selected="isSelectedById(asset.id, 'asset')"
                        :any-selected="selection.ids.length > 0"
                        @toggle-select="$emit('toggle-select', asset.id, 'asset')"
                        @preview="preview"
                        @edit="edit"
                        @delete="del"
                        @ctx="showCtx($event.event, $event.asset, 'asset')"
                    ></v-dam-asset-card>
                </div>
            </template>

            {{-- Empty --}}
            <div v-if="!directories.length && !assets.length" class="flex flex-col items-center justify-center py-16 gap-4">
                <img src="{{ unopim_asset('images/no-records-found.svg', 'dam') }}" class="w-32 h-32 opacity-60" alt="" />
                <p class="text-lg font-bold text-gray-700 dark:text-slate-50">@lang('admin::app.components.datagrid.table.no-records-available')</p>
            </div>
        </template>

        {{-- Context menu --}}
        <v-dam-explorer-ctx
            v-if="ctx.on"
            :key="ctxKey"
            :x="ctx.x"
            :y="ctx.y"
            :item="ctx.item"
            :item-type="ctx.type"
            :tab-id="tabId"
            :clipboard="clipboard"
            @close="closeCtx"
            @navigate="$emit('navigate', $event)"
            @open-new-tab="$emit('open-new-tab', $event)"
            @bookmark="$emit('bookmark', $event)"
            @refresh="$emit('refresh')"
        ></v-dam-explorer-ctx>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer-grid', {
    template: '#v-dam-explorer-grid-template',
    emits: ['navigate', 'open-new-tab', 'bookmark', 'refresh', 'internal-drop', 'toggle-select'],

    props: {
        directories:          { type: Array, default: () => [] },
        assets:               { type: Array, default: () => [] },
        isLoading:            { type: Boolean, default: false },
        tabId:                { type: String, required: true },
        currentDirId:         { type: Number, default: null },
        clipboard:            { type: Object, default: null },
        canAccessCurrentDir:  { type: Boolean, default: true },
        selection:            { type: Object, default: () => ({ ids: [], mode: 'none' }) },
    },

    data() {
        return {
            ctx: { on: false, x: 0, y: 0, item: null, type: null },
            ctxKey: 0,
            _ctxClose: null,
            _ctxScroll: null,
            _springTimer: null,
            dropTargetId: null,
        };
    },

    methods: {
        isSelectedById(id, type) {
            return this.selection.ids.some(i => i.id === id && i.type === type);
        },
        onDragStart(e, d) {
            e.dataTransfer.setData('application/json', JSON.stringify({
                type: 'dam-folder',
                id: d.id,
                name: d.name,
                assetsCount: d.assets_count ?? 0,
                tabId: this.tabId,
            }));
            this._draggingId = d.id;
            // opacity handled by v-dam-explorer-folder-card
        },
        onDragEnd() {
            this._draggingId = null;
            this.dropTargetId = null;
            clearTimeout(this._springTimer?.id);
            this._springTimer = null;
        },
        onFolderDragOver(e, dir) {
            if (this._draggingId === dir.id) return;
            this.dropTargetId = dir.id;
            if (this._springTimer?.dirId === dir.id) return;
            clearTimeout(this._springTimer?.id);
            const timerId = setTimeout(() => {
                if (this.dropTargetId === dir.id) this.$emit('navigate', dir);
            }, 1500);
            this._springTimer = { dirId: dir.id, id: timerId };
        },
        onFolderDragLeave(e, dir) {
            // card already filters child dragleaves — any emission here means truly left
            if (this.dropTargetId === dir.id) {
                this.dropTargetId = null;
                clearTimeout(this._springTimer?.id);
                this._springTimer = null;
            }
        },
        onInternalDrop(e, targetDir) {
            this.dropTargetId = null;
            clearTimeout(this._springTimer?.id);
            this._springTimer = null;
            let payload;
            try { payload = JSON.parse(e.dataTransfer.getData('application/json')); } catch { return; }
            if (payload.type === 'dam-folder' && payload.id === targetDir.id) return;
            this.$emit('internal-drop', { payload, targetDir });
        },
        showCtx(e, item, type) {
            this.closeCtx();
            this.ctxKey++;
            this.ctx = { on: true, x: e.clientX, y: e.clientY, item, type };
            let lastScrollY = window.scrollY, lastScrollX = window.scrollX;
            this._ctxScroll = () => {
                const dy = window.scrollY - lastScrollY, dx = window.scrollX - lastScrollX;
                lastScrollY = window.scrollY; lastScrollX = window.scrollX;
                this.ctx = { ...this.ctx, y: this.ctx.y - dy, x: this.ctx.x - dx };
            };
            this._ctxClose = () => this.closeCtx();
            document.addEventListener('click',  this._ctxClose, { once: true });
            window.addEventListener('scroll', this._ctxScroll, { capture: true });
            this.$emitter.emit(`dam:ctx-open:${this.tabId}`, { item, type });
        },
        closeCtx() {
            this.ctx.on = false;
            if (this._ctxClose)  { document.removeEventListener('click',  this._ctxClose); this._ctxClose  = null; }
            if (this._ctxScroll) { window.removeEventListener('scroll', this._ctxScroll, { capture: true }); this._ctxScroll = null; }
        },
        showSpaceCtx(e) {
            if (! this.currentDirId) return;
            this.showCtx(e, { id: this.currentDirId, can_access: this.canAccessCurrentDir }, 'space');
        },
        preview(id)  { this.$emitter.emit('dam-open-preview', id); },
        edit(id)     { window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', id); },
        del(asset) {
            this.$emitter.emit('open-delete-modal', {
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', asset.id))
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.action-completed')" }); this.$emit('refresh'); this.$emitter.emit('dam:tree-reload'); })
                        .catch(err => this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message }));
                },
            });
        },

        onSpaceDrop(e) {
            if (e.dataTransfer?.types?.includes('Files')) return;
            if (! this.currentDirId) return;
            let payload;
            try { payload = JSON.parse(e.dataTransfer.getData('application/json')); } catch { return; }
            if (! payload?.type) return;
            // Item already lives in this directory — drop is a no-op
            if (payload.type === 'dam-folder' && this.directories.some(d => d.id === payload.id)) return;
            if (payload.type === 'dam-asset'  && this.assets.some(a => a.id === payload.id)) return;
            this.$emit('internal-drop', { payload, targetDir: { id: this.currentDirId } });
        },
    },
});
</script>
@endpush
@endonce
