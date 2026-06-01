@props(['tabId'])

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

                            {{-- Hover action overlay — pointer-events-none on dark bg; buttons only active on hover via .explorer-asset-actions CSS class --}}
                            <div class="absolute inset-0 flex items-center justify-center bg-black/80 dark:bg-cherry-800/90 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                <div class="flex gap-1 explorer-asset-actions">
                                    @if (bouncer()->hasPermission('dam.asset.view'))
                                    <button type="button" class="icon-dam-preview text-xl p-1.5 rounded-md text-white hover:bg-violet-600 transition-colors" @click.stop="preview(asset.id)"></button>
                                    @endif
                                    @if (bouncer()->hasPermission('dam.asset.edit'))
                                    <button type="button" class="icon-edit text-xl p-1.5 rounded-md text-white hover:bg-violet-600 transition-colors" @click.stop="edit(asset.id)"></button>
                                    @endif
                                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                                    <button type="button" class="icon-delete text-xl p-1.5 rounded-md text-white hover:bg-red-600 transition-colors" @click.stop="del(asset)"></button>
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

@once('v-dam-explorer-ctx')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-ctx-template">
    <div
        class="fixed bg-white dark:bg-cherry-800 border border-gray-200 dark:border-cherry-600 rounded-lg shadow-2xl py-1 min-w-[185px]"
        style="z-index:9999;"
        :style="{ top: `${y}px`, left: `${x}px` }"
    >
        {{-- Directory actions --}}
        <template v-if="itemType === 'directory'">
            <button class="ctx-item" @click="doNavigate">
                <i class="icon-dam-folder text-sm text-gray-400"></i>
                @lang('dam::app.admin.explorer.context.open')
            </button>
            <button class="ctx-item" @click="doOpenNewTab">
                <i class="icon-link text-sm text-gray-400"></i>
                @lang('dam::app.admin.explorer.context.open-new-tab')
            </button>
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="ctx-item" @click="uploadHere">
                <i class="icon-dam-upload text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.upload-files')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="ctx-item" @click="folderUploadHere">
                <i class="icon-dam-add-folder text-sm text-gray-400"></i>
                @lang('dam::app.admin.explorer.context.folder-upload')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.store'))
            <button class="ctx-item" @click="createHere">
                <i class="icon-dam-add-folder text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.add-directory')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.rename'))
            <button class="ctx-item" @click="renameDir">
                <i class="icon-dam-rename text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.rename')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.copy_structure'))
            <button class="ctx-item" @click="copyStructure">
                <i class="icon-dam-directory text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.copy-directory-structured')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.download_zip'))
            <button class="ctx-item" @click="downloadZip">
                <i class="icon-dam-zip text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.download-zip')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.share'))
            <button class="ctx-item" @click="share">
                <i class="icon-dam-link text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.share')
            </button>
            @endif
            <button class="ctx-item" @click="doBookmark">
                <span class="text-sm">🔖</span>
                @lang('dam::app.admin.explorer.context.bookmark')
            </button>
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            <button class="ctx-item" @click="doCopy">
                <i class="icon-copy text-sm text-gray-400"></i>
                @lang('dam::app.admin.explorer.context.copy')
            </button>
            @if (bouncer()->hasPermission('dam.directory.destroy'))
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            <button class="ctx-item-danger" @click="delDir">
                <i class="icon-dam-delete text-sm"></i>
                @lang('dam::app.admin.dam.index.directory.actions.delete')
            </button>
            @endif
        </template>

        {{-- Space actions (right-click on empty area in current directory) --}}
        <template v-else-if="itemType === 'space'">
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="ctx-item" @click="uploadHere">
                <i class="icon-dam-upload text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.upload-files')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="ctx-item" @click="folderUploadHere">
                <i class="icon-dam-add-folder text-sm text-gray-400"></i>
                @lang('dam::app.admin.explorer.context.folder-upload')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.store'))
            <button class="ctx-item" @click="createHere">
                <i class="icon-dam-add-folder text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.add-directory')
            </button>
            @endif
            <template v-if="clipboard">
                <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
                <button class="ctx-item" @click="doPaste">
                    <i class="icon-paste text-sm text-gray-400"></i>
                    @lang('dam::app.admin.explorer.context.paste')
                </button>
            </template>
        </template>

        {{-- Asset actions --}}
        <template v-else-if="itemType === 'asset'">
            @if (bouncer()->hasPermission('dam.asset.view'))
            <button class="ctx-item" @click="preview">
                <i class="icon-dam-preview text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.asset.edit.preview-modal.card.preview')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.edit'))
            <button class="ctx-item" @click="edit">
                <i class="icon-edit text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.edit')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.rename'))
            <button class="ctx-item" @click="renameAsset">
                <i class="icon-dam-rename text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.rename')
            </button>
            @endif
            <button class="ctx-item" @click="doCopy">
                <i class="icon-copy text-sm text-gray-400"></i>
                @lang('dam::app.admin.explorer.context.copy')
            </button>
            @if (bouncer()->hasPermission('dam.asset.download'))
            <button class="ctx-item" @click="download">
                <i class="icon-import text-sm text-gray-400"></i>
                @lang('dam::app.admin.dam.index.directory.actions.download')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.destroy'))
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            <button class="ctx-item-danger" @click="delAsset">
                <i class="icon-dam-delete text-sm"></i>
                @lang('dam::app.admin.dam.index.directory.actions.delete')
            </button>
            @endif
        </template>
    </div>
</script>

<style>
.ctx-item { display: flex; align-items: center; gap: 0.5rem; width: 100%; text-align: left; padding: 0.375rem 1rem; font-size: 0.875rem; color: rgb(55 65 81); transition: background-color 0.15s; }
.ctx-item:hover { background-color: rgb(243 244 246); }
.dark .ctx-item { color: rgb(229 231 235); }
.dark .ctx-item:hover { background-color: rgb(var(--cherry-700)); }
.ctx-item-danger { display: flex; align-items: center; gap: 0.5rem; width: 100%; text-align: left; padding: 0.375rem 1rem; font-size: 0.875rem; color: rgb(220 38 38); transition: background-color 0.15s; }
.ctx-item-danger:hover { background-color: rgb(254 242 242); }
</style>

<script type="module">
app.component('v-dam-explorer-ctx', {
    template: '#v-dam-explorer-ctx-template',
    emits: ['close','navigate','open-new-tab','bookmark','refresh'],
    props: { x: Number, y: Number, item: Object, itemType: String, tabId: { type: String, required: true }, clipboard: { type: Object, default: null } },

    mounted() {
        this.$nextTick(() => {
            const el = this.$el;
            if (! el) return;
            const r = el.getBoundingClientRect();
            if (r.bottom > window.innerHeight) el.style.top  = Math.max(4, window.innerHeight - r.height - 8) + 'px';
            if (r.right  > window.innerWidth)  el.style.left = Math.max(4, window.innerWidth  - r.width  - 8) + 'px';
        });
    },

    methods: {
        close()         { this.$emit('close'); },
        doNavigate()    { this.$emit('navigate', this.item); this.close(); },
        doOpenNewTab()  { this.$emit('open-new-tab', this.item); this.close(); },
        doBookmark()    { this.$emit('bookmark', this.item); this.close(); },

        preview()     { this.$emitter.emit('dam-open-preview', this.item.id); this.close(); },
        edit()        { window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', this.item.id); },
        renameAsset() { this.$emitter.emit(`dam:explorer-rename-asset:${this.tabId}`, { asset: this.item }); this.close(); },
        download()    { window.open(`{{ route('admin.dam.assets.download', ':id') }}`.replace(':id', this.item.id), '_self'); this.close(); },
        ctxRefresh(delayed = false) {
            this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`);
            if (delayed) setTimeout(() => this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`), 800);
        },
        delAsset() {
            this.close();
            this.$emitter.emit('open-delete-modal', {
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', this.item.id))
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.dam.index.directory.actions.delete')" }); this.ctxRefresh(); })
                        .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
                },
            });
        },
        renameDir()   { this.$emitter.emit(`dam:explorer-rename-dir:${this.tabId}`, { dir: this.item }); this.close(); },
        copyStructure() {
            const tabId = this.tabId;
            this.close();
            this.$axios.post("{{ route('admin.dam.directory.copy_structure') }}", this.item)
                .then(({ data }) => {
                    this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.context.copy-progress')" });
                    let attempts = 0;
                    const poll = () => {
                        if (++attempts > 15) return;
                        this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'copy_directory_structure'))
                            .then(({ data: d }) => {
                                if (d.status === 'completed') {
                                    this.$emitter.emit('add-flash', { type: 'success', message: 'Action completed successfully' });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                } else if (d.status === 'failed') {
                                    this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                } else { setTimeout(poll, 2000); }
                            }).catch(() => {});
                    };
                    setTimeout(poll, 1000);
                })
                .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
        },
        downloadZip() { this.close(); window.open(`{{ route('admin.dam.directory.zip_download', ':id') }}`.replace(':id', this.item.id), '_self'); },
        share()       { this.close(); this.$emitter.emit('open-share-modal', { targetType:'directory', targetId: this.item.id }); },
        uploadHere()       { this.close(); this.$emitter.emit(`dam:explorer-upload-here:${this.tabId}`, { directoryId: this.item.id }); },
        folderUploadHere() { this.close(); this.$emitter.emit(`dam:explorer-folder-upload-here:${this.tabId}`, { directoryId: this.item.id }); },
        createHere()       { this.close(); this.$emitter.emit(`dam:explorer-create-dir:${this.tabId}`, { parentId: this.item.id }); },
        doCopy() {
            this.$emitter.emit(`dam:explorer-copy:${this.tabId}`, { item: this.item, type: this.itemType });
            this.close();
        },
        doPaste() {
            this.$emitter.emit(`dam:explorer-paste:${this.tabId}`, { targetDirId: this.item.id });
            this.close();
        },
        delDir() {
            const tabId = this.tabId;
            this.close();
            this.$emitter.emit('open-delete-modal', {
                message: "@lang('dam::app.admin.components.modal.confirm.message')",
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.directory.destroy', ':id') }}`.replace(':id', this.item.id))
                        .then(({ data }) => {
                            this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.dam.index.directory.deleting-in-progress')" });
                            let attempts = 0;
                            const poll = () => {
                                if (++attempts > 15) return;
                                this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'delete_directory'))
                                    .then(({ data: d }) => {
                                        if (d.status === 'completed') {
                                            this.$emitter.emit('add-flash', { type: 'success', message: 'Action completed successfully' });
                                            this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                        } else if (d.status === 'failed') {
                                            this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                            this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                        } else { setTimeout(poll, 2000); }
                                    }).catch(() => {});
                            };
                            setTimeout(poll, 1000);
                        })
                        .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
                },
            });
        },
    },
});
</script>
@endpush
@endonce
