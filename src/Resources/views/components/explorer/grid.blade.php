@once('v-dam-explorer-grid')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-grid-template">
    {{-- Outer div catches right-click on empty space (item right-clicks use .stop) --}}
    <div class="pr-4" @contextmenu.prevent="showSpaceCtx($event)">
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
                <div class="grid grid-cols-[repeat(auto-fill,minmax(120px,1fr))] gap-3 mb-6">
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
                    </div>
                </div>
            </template>

            {{-- Assets --}}
            <template v-if="assets.length">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
                    @lang('dam::app.admin.explorer.sections.files')
                </p>
                <div class="grid grid-cols-2 md:!grid-cols-3 xl:!grid-cols-4 2xl:!grid-cols-5 gap-4">
                    <div
                        v-for="asset in assets"
                        :key="asset.id"
                        class="group rounded-lg border border-gray-300 dark:border-cherry-600 bg-white dark:bg-cherry-900 overflow-hidden transition-colors cursor-pointer"
                        style="box-shadow:0 1px 3px rgba(0,0,0,.08);"
                        draggable="true"
                        @dragstart="onAssetDragStart($event, asset)"
                        @dragend="onDragEnd($event)"
                        @click="preview(asset.id)"
                        @contextmenu.prevent.stop="showCtx($event, asset, 'asset')"
                    >
                        {{-- Thumbnail --}}
                        <div class="image-card relative overflow-hidden">
                            <img
                                :src="asset.path"
                                :alt="asset.file_name"
                                class="w-full h-full object-cover object-center"
                                @@error="onImgErr($event, asset)"
                            />

                            {{-- Extension badge --}}
                            <span
                                v-if="asset.extension"
                                class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase text-white shadow"
                                style="z-index:2;"
                                :class="{
                                    'bg-violet-600': asset.file_type==='video'||asset.file_type==='audio',
                                    'bg-red-600':    (asset.extension||'').toLowerCase()==='pdf',
                                    'bg-gray-600':   asset.file_type!=='video'&&asset.file_type!=='audio'&&(asset.extension||'').toLowerCase()!=='pdf',
                                }"
                            >@{{ (asset.extension||'').toUpperCase() }}</span>

                            {{-- Play / audio overlay --}}
                            <div
                                v-if="asset.file_type==='video'||asset.file_type==='audio'"
                                class="absolute inset-0 flex items-center justify-center pointer-events-none"
                            >
                                <span
                                    class="flex items-center justify-center w-10 h-10 rounded-full bg-black/55 text-white text-xl shadow-lg"
                                    :class="asset.file_type==='video' ? 'icon-play' : 'icon-information'"
                                    aria-hidden="true"
                                ></span>
                            </div>

                            {{-- Hover action overlay — buttons enabled only when card is hovered --}}
                            <div class="absolute inset-0 flex items-center justify-center bg-black/80 dark:bg-cherry-800/90 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                <div class="flex gap-1">
                                    @if (bouncer()->hasPermission('dam.asset.view'))
                                    <button type="button" class="icon-dam-preview text-xl p-1.5 rounded-md text-white hover:bg-violet-600 transition-colors pointer-events-none group-hover:pointer-events-auto" @click.stop="preview(asset.id)"></button>
                                    @endif
                                    @if (bouncer()->hasPermission('dam.asset.edit'))
                                    <button type="button" class="icon-edit text-xl p-1.5 rounded-md text-white hover:bg-violet-600 transition-colors pointer-events-none group-hover:pointer-events-auto" @click.stop="edit(asset.id)"></button>
                                    @endif
                                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                                    <button type="button" class="icon-delete text-xl p-1.5 rounded-md text-white hover:bg-red-600 transition-colors pointer-events-none group-hover:pointer-events-auto" @click.stop="del(asset)"></button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="px-2 py-1.5">
                            <p class="text-xs text-gray-600 dark:text-gray-300 truncate">@{{ asset.file_name }}</p>
                        </div>
                    </div>
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
            ctxKey: 0,
            _ctxClose: null,
            _springTimer: null,
            dropTargetId: null,
            placeholders: {
                video:       '{{ unopim_asset('images/grid/video.svg', 'dam') }}',
                audio:       '{{ unopim_asset('images/grid/audio.svg', 'dam') }}',
                pdf:         '{{ unopim_asset('images/grid/file.svg', 'dam') }}',
                spreadsheet: '{{ unopim_asset('images/grid/sheet.svg', 'dam') }}',
                csv:         '{{ unopim_asset('images/grid/csv.svg', 'dam') }}',
                document:    '{{ unopim_asset('images/grid/file.svg', 'dam') }}',
                image:       '{{ unopim_asset('images/grid/image.svg', 'dam') }}',
            },
            fallback: '{{ unopim_asset('images/grid/unspecified.svg', 'dam') }}',
        };
    },

    methods: {
        onImgErr(e, a)   { e.target.src = this.placeholders[a.file_type] ?? this.fallback; e.target.className = 'w-full h-full object-contain p-4'; },
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
            this.ctxKey++;
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
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? 'Done.' }); this.$emit('refresh'); this.$emitter.emit('dam:tree-reload'); })
                        .catch(err => this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message }));
                },
            });
        },
    },
});
</script>
@endpush
@endonce
