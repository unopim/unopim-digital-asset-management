@props(['tabId'])

<v-dam-explorer-list
    :directories="directories" :assets="assets"
    :is-loading="isLoading" :sort-by="sortBy" :sort-order="sortOrder"
    tab-id="{{ $tabId }}"
    @navigate="$emit('navigate', $event)"
    @open-new-tab="$emit('open-new-tab', $event)"
    @bookmark="$emit('bookmark', $event)"
    @sort-change="$emit('sort-change', $event)"
    @refresh="$emit('refresh')"
></v-dam-explorer-list>

@once('v-dam-explorer-list')
@push('scripts')
<style>
.explorer-list-grid { grid-template-columns: 28px 1fr 110px 80px 110px 100px; }
</style>

<script type="text/x-template" id="v-dam-explorer-list-template">
    <div
        class="border border-gray-200 dark:border-cherry-700 rounded-lg overflow-hidden bg-white dark:bg-cherry-900"
        @contextmenu.prevent="showSpaceCtx($event)"
    >

        <div v-if="isLoading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-violet-600" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>

        <template v-else>
            {{-- Header --}}
            <div class="explorer-list-grid grid gap-0 px-4 py-2 bg-gray-50 dark:bg-cherry-950 border-b border-gray-200 dark:border-cherry-700 text-xs font-bold uppercase tracking-widest text-gray-400">
                <span></span>
                <span class="cursor-pointer hover:text-gray-600" @click="sort('name')">
                    @lang('dam::app.admin.explorer.list.header.name') <span v-if="sortBy==='name'">@{{ sortOrder==='asc'?'↑':'↓' }}</span>
                </span>
                <span>@lang('dam::app.admin.explorer.list.header.type')</span>
                <span class="cursor-pointer hover:text-gray-600" @click="sort('size')">
                    @lang('dam::app.admin.explorer.list.header.size') <span v-if="sortBy==='size'">@{{ sortOrder==='asc'?'↑':'↓' }}</span>
                </span>
                <span class="cursor-pointer hover:text-gray-600" @click="sort('updated_at')">
                    @lang('dam::app.admin.explorer.list.header.modified') <span v-if="sortBy==='updated_at'">@{{ sortOrder==='asc'?'↑':'↓' }}</span>
                </span>
                <span>@lang('dam::app.admin.explorer.list.header.actions')</span>
            </div>

            {{-- Folder rows --}}
            <div
                v-for="dir in directories" :key="`d-${dir.id}`"
                class="explorer-list-grid grid gap-0 px-4 py-2.5 text-sm border-b border-gray-100 dark:border-cherry-800 items-center cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 transition-colors"
                :data-dir-id="dir.id"
                @click="$emit('navigate', dir)"
                @contextmenu.prevent.stop="showCtx($event, dir, 'directory')"
            >
                <i class="icon-dam-folder text-lg text-violet-400"></i>
                <span class="font-medium text-violet-700 dark:text-violet-300 truncate">@{{ dir.name }}</span>
                <span class="text-gray-400 text-xs">@lang('dam::app.admin.explorer.sections.folder')</span>
                <span class="text-gray-400 text-xs">@{{ dir.assets_count }} @lang('dam::app.admin.explorer.sections.items')</span>
                <span class="text-gray-400 text-xs">—</span>
                <div class="flex gap-2">
                    @if (bouncer()->hasPermission('dam.directory.rename'))
                    <button type="button" class="icon-dam-rename text-gray-400 hover:text-violet-600 text-base" @click.stop="renameDir(dir)"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.directory.destroy'))
                    <button type="button" class="icon-dam-delete text-gray-400 hover:text-red-500 text-base" @click.stop="delDir(dir)"></button>
                    @endif
                    <button
                        type="button"
                        class="text-gray-400 hover:text-violet-600 text-sm"
                        :data-bookmark-dir="dir.id"
                        @click.stop="$emit('bookmark', dir)"
                        title="@lang('dam::app.admin.explorer.context.bookmark')"
                    >🔖</button>
                </div>
            </div>

            {{-- Files divider --}}
            <div v-if="directories.length && assets.length" class="px-4 py-0.5 bg-gray-50 dark:bg-cherry-950 border-b border-gray-100 dark:border-cherry-800 text-[10px] font-bold uppercase tracking-widest text-violet-400">
                @lang('dam::app.admin.explorer.sections.files')
            </div>

            {{-- Asset rows --}}
            <div
                v-for="asset in assets" :key="`a-${asset.id}`"
                class="explorer-list-grid grid gap-0 px-4 py-2.5 text-sm border-b border-gray-100 dark:border-cherry-800 items-center hover:bg-gray-50 dark:hover:bg-cherry-800 transition-colors"
                @contextmenu.prevent.stop="showCtx($event, asset, 'asset')"
            >
                <i class="text-lg" :class="icon(asset.file_type)"></i>
                <span class="text-gray-700 dark:text-gray-200 truncate">@{{ asset.file_name }}</span>
                <div class="flex items-center overflow-hidden"><span class="text-xs font-bold rounded px-1 py-px uppercase whitespace-nowrap overflow-hidden" style="max-width:100%;text-overflow:ellipsis;" :class="badge(asset.file_type, asset.extension)">@{{ asset.file_type }}</span></div>
                <span class="text-gray-400 text-xs">@{{ fmtSize(asset.file_size) }}</span>
                <span class="text-gray-400 text-xs">@{{ fmtDate(asset.updated_at) }}</span>
                <div class="flex gap-2">
                    @if (bouncer()->hasPermission('dam.asset.view'))
                    <button type="button" class="icon-dam-preview text-gray-400 hover:text-violet-600 text-base" @click="preview(asset.id)"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.asset.edit'))
                    <button type="button" class="icon-edit text-gray-400 hover:text-violet-600 text-base" @click="edit(asset.id)"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                    <button type="button" class="icon-delete text-gray-400 hover:text-red-500 text-base" @click="del(asset)"></button>
                    @endif
                </div>
            </div>

            {{-- Empty --}}
            <div v-if="!directories.length && !assets.length" class="flex flex-col items-center justify-center py-16 gap-3">
                <img src="{{ unopim_asset('images/no-records-found.svg', 'dam') }}" class="w-24 h-24 opacity-60" alt="" />
                <p class="font-bold text-gray-700 dark:text-slate-50">@lang('admin::app.components.datagrid.table.no-records-available')</p>
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
app.component('v-dam-explorer-list', {
    template: '#v-dam-explorer-list-template',
    emits: ['navigate','open-new-tab','bookmark','sort-change','refresh'],

    props: {
        directories:  { type: Array, default: () => [] },
        assets:       { type: Array, default: () => [] },
        isLoading:    { type: Boolean, default: false },
        sortBy:       { type: String, default: 'name' },
        sortOrder:    { type: String, default: 'asc' },
        tabId:        { type: String, required: true },
        currentDirId: { type: Number, default: null },
        clipboard:    { type: Object, default: null },
    },

    data() { return { ctx: { on: false, x: 0, y: 0, item: null, type: null }, _ctxClose: null }; },

    methods: {
        sort(col) {
            const ord = this.sortBy === col && this.sortOrder === 'asc' ? 'desc' : 'asc';
            this.$emit('sort-change', { sortBy: col, sortOrder: ord });
        },
        icon(type) { return { image: 'icon-dam-image', video: 'icon-dam-video', audio: 'icon-dam-audio', document: 'icon-dam-doc' }[type] ?? 'icon-dam-image'; },
        badge(type, ext) {
            if ((ext||'').toLowerCase() === 'pdf')    return 'bg-red-100 text-red-700';
            if (type === 'video' || type === 'audio') return 'bg-violet-100 text-violet-700';
            if (type === 'image')                     return 'bg-violet-100 text-violet-700';
            if (type === 'spreadsheet')               return 'bg-green-100 text-green-700';
            return 'bg-gray-100 text-gray-600';
        },
        fmtSize(b) {
            if (!b) return '—';
            return b >= 1048576 ? (b/1048576).toFixed(1)+' MB' : b >= 1024 ? (b/1024).toFixed(0)+' KB' : b+' B';
        },
        fmtDate(iso) {
            if (!iso) return '—';
            return new Date(iso).toLocaleDateString(undefined, { day:'numeric', month:'short', year:'numeric' });
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
        preview(id) { this.$emitter.emit('dam-open-preview', id); },
        edit(id)    { window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', id); },
        del(asset) {
            this.$emitter.emit('open-delete-modal', {
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', asset.id))
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? 'Done.' }); this.$emit('refresh'); })
                        .catch(err => this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message }));
                },
            });
        },
        renameDir(dir) { this.$emitter.emit(`dam:explorer-rename-dir:${this.tabId}`, { dir }); },
        delDir(dir) {
            const tabId = this.tabId;
            this.$emitter.emit('open-delete-modal', {
                message: "@lang('dam::app.admin.components.modal.confirm.message')",
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.directory.destroy', ':id') }}`.replace(':id', dir.id))
                        .then(({ data }) => {
                            this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? 'Done.' });
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
