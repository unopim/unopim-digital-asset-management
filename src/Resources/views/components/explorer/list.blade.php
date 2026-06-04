@once('v-dam-explorer-list')
@push('scripts')

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
            <div class="grid-cols-[28px_1fr_110px_80px_110px_100px] grid gap-0 px-4 py-2 bg-gray-50 dark:bg-cherry-950 border-b border-gray-200 dark:border-cherry-700 text-xs font-bold uppercase tracking-widest text-gray-400">
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
                class="grid-cols-[28px_1fr_110px_80px_110px_100px] grid gap-0 px-4 py-2.5 text-sm border-b border-gray-100 dark:border-cherry-800 items-center cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 transition-colors"
                :data-dir-id="dir.id"
                @click="$emit('navigate', dir)"
                @contextmenu.prevent.stop="showCtx($event, dir, 'directory')"
            >
                <i class="icon-dam-folder text-lg text-violet-400"></i>
                <span class="font-medium text-violet-700 dark:text-violet-300 truncate">@{{ dir.name }}</span>
                <span class="text-gray-400 text-xs">@lang('dam::app.admin.explorer.sections.folder')</span>
                <span class="text-gray-400 text-xs">—</span>
                <div class="flex gap-2">
                    @if (bouncer()->hasPermission('dam.directory.rename'))
                    <button type="button" class="icon-dam-rename text-gray-400 hover:text-violet-600 text-base" @click.stop="renameDir(dir)"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.directory.destroy'))
                    <button type="button" class="icon-dam-delete text-gray-400 hover:text-red-500 text-base" @click.stop="delDir(dir)"></button>
                    @endif
                    @if (config('dam.explorer.bookmarks_enabled'))
                    <button
                        type="button"
                        class="text-gray-400 hover:text-violet-600 text-sm"
                        :data-bookmark-dir="dir.id"
                        @click.stop="$emit('bookmark', dir)"
                        title="@lang('dam::app.admin.explorer.context.bookmark')"
                    >🔖</button>
                    @endif
                </div>
            </div>

            {{-- Files divider --}}
            <div v-if="directories.length && assets.length" class="px-4 py-0.5 bg-gray-50 dark:bg-cherry-950 border-b border-gray-100 dark:border-cherry-800 text-[10px] font-bold uppercase tracking-widest text-violet-400">
                @lang('dam::app.admin.explorer.sections.files')
            </div>

            {{-- Asset rows --}}
            <div
                v-for="asset in assets" :key="`a-${asset.id}`"
                class="grid-cols-[28px_1fr_110px_80px_110px_100px] grid gap-0 px-4 py-2.5 text-sm border-b border-gray-100 dark:border-cherry-800 items-center hover:bg-gray-50 dark:hover:bg-cherry-800 transition-colors"
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
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? 'Done.' }); this.$emit('refresh'); this.$emitter.emit('dam:tree-reload'); })
                        .catch(err => this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message }));
                },
            });
        },
        renameDir(dir) { this.$emitter.emit('dam:open-rename-dir', { item: dir }); },
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
                                            this.$emitter.emit('dam:tree-reload');
                                            this.$emitter.emit(`dam:dir-deleted:${tabId}`);
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
