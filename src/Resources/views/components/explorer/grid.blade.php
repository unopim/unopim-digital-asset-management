@props(['tabId'])

@include('dam::components.shared.asset-card')

<v-dam-explorer-grid
    :directories="directories"
    :assets="assets"
    :is-loading="isLoading"
    tab-id="{{ $tabId }}"
    @navigate="$emit('navigate', $event)"
    @open-new-tab="$emit('open-new-tab', $event)"
    @bookmark="$emit('bookmark', $event)"
    @refresh="$emit('refresh')"
></v-dam-explorer-grid>

@once('v-dam-explorer-grid')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-grid-template">
    {{-- Outer div catches right-click on empty space (item right-clicks use .stop) --}}
    <div @contextmenu.prevent="showSpaceCtx($event)">
        {{-- Shimmer --}}
        <div v-if="isLoading" class="grid explorer-asset-grid grid-cols-2 gap-4 animate-pulse">
            <div v-for="n in 10" :key="n" class="aspect-square bg-gray-100 dark:bg-cherry-800 rounded-lg"></div>
        </div>

        <template v-else>
            {{-- Folders --}}
            <template v-if="directories.length">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
                    @lang('dam::app.admin.explorer.sections.folders')
                </p>
                <div class="grid explorer-folder-grid gap-3 mb-6">
                    <div
                        v-for="dir in directories"
                        :key="dir.id"
                        class="group flex flex-col items-center gap-1 p-2 rounded-lg border text-center cursor-pointer transition-colors select-none min-w-0"
                        :class="dropTargetId === dir.id
                            ? 'border-violet-500 bg-violet-200 dark:bg-violet-900/60 ring-2 ring-violet-400'
                            : 'border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40'"
                        :data-dir-id="dir.id"
                        draggable="true"
                        @dragstart="onDragStart($event, dir)"
                        @dragend="onDragEnd($event)"
                        @dragover.prevent="onFolderDragOver($event, dir)"
                        @dragleave="onFolderDragLeave($event, dir)"
                        @drop.prevent.stop="onInternalDrop($event, dir)"
                        @click="$emit('navigate', dir)"
                        @contextmenu.prevent.stop="showCtx($event, dir, 'directory')"
                    >
                        <i class="icon-dam-folder text-2xl text-violet-400 dark:text-violet-500 shrink-0"></i>
                        <div class="text-xs font-semibold text-violet-700 dark:text-violet-300 truncate w-full">@{{ dir.name }}</div>
                        <div class="text-[10px] text-violet-400">@{{ dir.assets_count }}</div>
                    </div>
                </div>
            </template>

            {{-- Assets --}}
            <template v-if="assets.length">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
                    @lang('dam::app.admin.explorer.sections.files')
                </p>
                <div class="grid explorer-asset-grid grid-cols-2 gap-4">
                    <div
                        v-for="asset in assets"
                        :key="asset.id"
                    >
                        <v-dam-asset-card
                            :asset="asset"
                            :draggable="true"
                            @preview="preview(asset.id)"
                            @edit="edit(asset.id)"
                            @delete="del(asset)"
                            @dragstart="onAssetDragStart($event, asset)"
                            @dragend="onDragEnd($event)"
                            @contextmenu="showCtx($event, asset, 'asset')"
                        ></v-dam-asset-card>
                        <p class="text-xs text-gray-600 dark:text-gray-300 truncate px-1 mt-1">@{{ asset.file_name }}</p>
                    </div>
                </div>
            </template>

            {{-- Empty --}}
            <template v-if="!directories.length && !assets.length">
                @include('dam::components.shared.empty-state')
            </template>
        </template>

        {{-- Context menu --}}
        <v-dam-explorer-ctx
            v-if="ctx.on"
            :x="ctx.x"
            :y="ctx.y"
            :item="ctx.item"
            :item-type="ctx.type"
            :tab-id="tabId"
            :clipboard="clipboard"
            @close="ctx.on=false"
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
    emits: ['navigate', 'open-new-tab', 'bookmark', 'refresh', 'internal-drop'],

    props: {
        directories:  { type: Array, default: () => [] },
        assets:       { type: Array, default: () => [] },
        isLoading:    { type: Boolean, default: false },
        tabId:        { type: String, required: true },
        currentDirId: { type: Number, default: null },
        clipboard:    { type: Object, default: null },
    },

    data() {
        return {
            ctx: { on: false, x: 0, y: 0, item: null, type: null },
            _ctxClose: null,
            _springTimer: null,
            dropTargetId: null,
        };
    },

    methods: {
        onDragStart(e, d) {
            e.dataTransfer.setData('application/json', JSON.stringify({
                type: 'dam-folder',
                id: d.id,
                name: d.name,
                assetsCount: d.assets_count ?? 0,
            }));
            e.currentTarget.style.opacity = '0.4';
        },
        onDragEnd(e) { e.currentTarget.style.opacity = ''; },
        onAssetDragStart(e, asset) {
            e.dataTransfer.setData('application/json', JSON.stringify({
                type: 'dam-asset',
                id: asset.id,
                name: asset.file_name,
            }));
            e.currentTarget.style.opacity = '0.4';
        },
        onFolderDragOver(e, dir) {
            this.dropTargetId = dir.id;
            if (this._springTimer?.dirId === dir.id) return;
            clearTimeout(this._springTimer?.id);
            const timerId = setTimeout(() => {
                if (this.dropTargetId === dir.id) this.$emit('navigate', dir);
            }, 1500);
            this._springTimer = { dirId: dir.id, id: timerId };
        },
        onFolderDragLeave(e, dir) {
            if (! e.currentTarget.contains(e.relatedTarget) && this.dropTargetId === dir.id) {
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
            if (payload.id === targetDir.id) return;
            this.$emit('internal-drop', { payload, targetDir });
        },
        showCtx(e, item, type) {
            if (this._ctxClose) { document.removeEventListener('click', this._ctxClose); }
            this.ctx = { on: true, x: e.clientX, y: e.clientY, item, type };
            this._ctxClose = () => { this.ctx.on = false; this._ctxClose = null; };
            document.addEventListener('click', this._ctxClose, { once: true });
            this.$emitter.emit(`dam:ctx-open:${this.tabId}`, { item, type });
        },
        showSpaceCtx(e) {
            if (! this.currentDirId) return;
            this.showCtx(e, { id: this.currentDirId }, 'space');
        },
        preview(id)  { this.$emitter.emit('dam-open-preview', id); },
        edit(id)     { window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', id); },
        del(asset) {
            this.$emitter.emit('open-delete-modal', {
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', asset.id))
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? 'Done.' }); this.$emit('refresh'); })
                        .catch(err => this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message }));
                },
            });
        },
    },
});
</script>
@endpush
@endonce
