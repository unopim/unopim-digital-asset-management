@once('v-dam-explorer-list')
@push('scripts')

<script type="text/x-template" id="v-dam-explorer-list-template">
    <div
        class="border border-gray-200 dark:border-cherry-700 rounded-lg overflow-hidden bg-white dark:bg-cherry-900 min-h-72"
        @contextmenu.prevent="showSpaceCtx($event)"
        @dragover.prevent
        @drop.prevent="onSpaceDrop($event)"
    >

        <div v-if="isLoading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-violet-600" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>

        <template v-else>
            {{-- Header --}}
            <div class="grid-cols-[28px_28px_1fr_110px_80px_110px_100px] grid gap-x-2 px-4 py-2 bg-gray-50 dark:bg-cherry-800 border-b border-gray-200 dark:border-cherry-700 text-xs font-bold uppercase tracking-widest text-gray-400">
                <span></span>
                <span></span>
                <span class="cursor-pointer hover:text-gray-600 dark:hover:text-white" @click="sort('name')">
                    @lang('dam::app.admin.explorer.list.header.name') <span v-if="sortBy==='name'">@{{ sortOrder==='asc'?'↑':'↓' }}</span>
                </span>
                <span>@lang('dam::app.admin.explorer.list.header.type')</span>
                <span class="cursor-pointer hover:text-gray-600 dark:hover:text-white" @click="sort('size')">
                    @lang('dam::app.admin.explorer.list.header.size') <span v-if="sortBy==='size'">@{{ sortOrder==='asc'?'↑':'↓' }}</span>
                </span>
                <span class="cursor-pointer hover:text-gray-600 dark:hover:text-white" @click="sort('updated_at')">
                    @lang('dam::app.admin.explorer.list.header.modified') <span v-if="sortBy==='updated_at'">@{{ sortOrder==='asc'?'↑':'↓' }}</span>
                </span>
                <span>@lang('dam::app.admin.explorer.list.header.actions')</span>
            </div>

            {{-- Folder rows --}}
            <div
                v-for="dir in directories" :key="`d-${dir.id}`"
                class="grid-cols-[28px_28px_1fr_110px_80px_110px_100px] grid gap-x-2 px-4 py-2.5 text-sm border-b border-gray-100 dark:border-cherry-800 items-center cursor-pointer hover:bg-violet-100 dark:hover:bg-violet-800/50 transition-colors"
                :class="{ 'ring-2 ring-inset ring-violet-400': dropTargetId === dir.id }"
                :data-dir-id="dir.id"
                draggable="true"
                @click="$emit('navigate', dir)"
                @contextmenu.prevent.stop="showCtx($event, dir, 'directory')"
                @dragstart="onDirDragStart($event, dir)"
                @dragend="onDragEnd"
                @dragover.prevent="onDirDragOver($event, dir)"
                @dragleave="onDirDragLeave($event, dir)"
                @drop.prevent.stop="onDirDrop($event, dir)"
            >
                <div class="flex items-center justify-center" @click.stop>
                    <label :for="`sel-dir-${dir.id}`" class="flex items-center justify-center cursor-pointer">
                        <input
                            type="checkbox"
                            class="peer hidden"
                            :id="`sel-dir-${dir.id}`"
                            :checked="isSelectedById(dir.id, 'directory')"
                            @change="$emit('toggle-select', dir.id, 'directory')"
                        >
                        <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 rounded-md text-xl cursor-pointer"></span>
                    </label>
                </div>
                <i class="icon-dam-folder text-lg text-violet-400"></i>
                <span class="font-medium text-violet-700 dark:text-violet-300 truncate">@{{ dir.name }}</span>
                <span class="text-gray-400 text-xs">@lang('dam::app.admin.explorer.sections.folder')</span>
                <span class="text-gray-400 text-xs">@{{ "@lang('dam::app.admin.explorer.list.items-count')".replace(':count', dir.assets_count + dir.children_count) }}</span>
                <span class="text-gray-400 text-xs">@{{ fmtDate(dir.updated_at) }}</span>
                <div class="flex gap-2 items-center">
                    @if (bouncer()->hasPermission('dam.directory.rename'))
                    <button v-if="dir.can_access !== false" type="button" class="icon-dam-rename text-gray-400 hover:text-violet-600 text-base" @click.stop="renameDir(dir)" title="@lang('dam::app.admin.dam.index.directory.actions.rename')"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.directory.destroy'))
                    <button v-if="dir.can_access !== false" type="button" class="icon-dam-delete text-gray-400 hover:text-red-500 text-base" @click.stop="delDir(dir)" title="@lang('dam::app.admin.dam.index.directory.actions.delete')"></button>
                    @endif
                    @if (config('dam.explorer.bookmarks_enabled'))
                    <button
                        type="button"
                        class="icon-star text-gray-400 hover:text-violet-600 text-base"
                        :data-bookmark-dir="dir.id"
                        @click.stop="$emit('bookmark', dir)"
                        title="@lang('dam::app.admin.explorer.context.bookmark')"
                    ></button>
                    @endif
                </div>
            </div>

            {{-- Files divider --}}
            <div v-if="directories.length && assets.length" class="px-4 py-2 bg-gray-50 dark:bg-cherry-800 border-b border-gray-200 dark:border-cherry-700 text-xs font-bold uppercase tracking-widest text-gray-400">
                @lang('dam::app.admin.explorer.sections.files')
            </div>

            {{-- Asset rows --}}
            <div
                v-for="asset in assets" :key="`a-${asset.id}`"
                class="grid-cols-[28px_28px_1fr_110px_80px_110px_100px] grid gap-x-2 px-4 py-2.5 text-sm border-b border-gray-100 dark:border-cherry-800 items-center hover:bg-violet-100 dark:hover:bg-violet-800/50 transition-colors"
                draggable="true"
                @contextmenu.prevent.stop="showCtx($event, asset, 'asset')"
                @dragstart="onAssetDragStart($event, asset)"
                @dragend="onDragEnd"
            >
                <div class="flex items-center justify-center" @click.stop>
                    <label :for="`sel-asset-${asset.id}`" class="flex items-center justify-center cursor-pointer">
                        <input
                            type="checkbox"
                            class="peer hidden"
                            :id="`sel-asset-${asset.id}`"
                            :checked="isSelectedById(asset.id, 'asset')"
                            @change="$emit('toggle-select', asset.id, 'asset')"
                        >
                        <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 rounded-md text-xl cursor-pointer"></span>
                    </label>
                </div>
                <i class="text-lg" :class="icon(asset.file_type)"></i>
                <span class="text-gray-700 dark:text-gray-200 truncate">@{{ asset.file_name }}</span>
                <div class="flex items-center overflow-hidden"><span class="text-xs font-bold rounded px-1 py-px uppercase whitespace-nowrap overflow-hidden" style="max-width:100%;text-overflow:ellipsis;" :class="badge(asset.file_type, asset.extension)">@{{ asset.file_type }}</span></div>
                <span class="text-gray-400 text-xs">@{{ fmtSize(asset.file_size) }}</span>
                <span class="text-gray-400 text-xs">@{{ fmtDate(asset.updated_at) }}</span>
                <div class="flex gap-2">
                    @if (bouncer()->hasPermission('dam.asset.view'))
                    <button type="button" class="icon-dam-preview text-gray-400 hover:text-violet-600 text-base" @click="preview(asset.id)" title="@lang('dam::app.admin.dam.asset.edit.preview-modal.card.preview')"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.asset.edit'))
                    <button type="button" class="icon-edit text-gray-400 hover:text-violet-600 text-base" @click="edit(asset.id)" title="@lang('dam::app.admin.dam.index.directory.actions.edit')"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                    <button type="button" class="icon-delete text-gray-400 hover:text-red-500 text-base" @click="del(asset)" title="@lang('dam::app.admin.dam.index.directory.actions.delete')"></button>
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
app.component('v-dam-explorer-list', {
    template: '#v-dam-explorer-list-template',
    emits: ['navigate','open-new-tab','bookmark','sort-change','refresh','internal-drop','toggle-select'],

    props: {
        directories:          { type: Array, default: () => [] },
        assets:               { type: Array, default: () => [] },
        isLoading:            { type: Boolean, default: false },
        sortBy:               { type: String, default: 'name' },
        sortOrder:            { type: String, default: 'asc' },
        tabId:                { type: String, required: true },
        currentDirId:         { type: Number, default: null },
        clipboard:            { type: Object, default: null },
        canAccessCurrentDir:  { type: Boolean, default: true },
        selection:            { type: Object, default: () => ({ ids: [], mode: 'none' }) },
    },

    data() { return { ctx: { on: false, x: 0, y: 0, item: null, type: null }, ctxKey: 0, _ctxClose: null, _ctxScroll: null, dropTargetId: null }; },

    methods: {
        isSelectedById(id, type) {
            return this.selection.ids.some(i => i.id === id && i.type === type);
        },
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
        preview(id) { this.$emitter.emit('dam-open-preview', id); },
        edit(id)    { window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', id); },
        del(asset) {
            this.$emitter.emit('open-delete-modal', {
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', asset.id))
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.action-completed')" }); this.$emit('refresh'); this.$emitter.emit('dam:tree-reload'); })
                        .catch(err => this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message }));
                },
            });
        },
        _makeFolderDragGhost(name) {
            const ghost = document.createElement('div');
            ghost.style.cssText = 'position:fixed;top:-200px;left:-200px;width:96px;display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border-radius:12px;background:rgba(237,233,254,0.95);box-shadow:0 2px 8px rgba(0,0,0,0.12);pointer-events:none;';
            const icon = document.createElement('i');
            icon.className = 'icon-dam-folder';
            icon.style.cssText = 'font-size:60px;color:#a78bfa;line-height:1;';
            const label = document.createElement('span');
            label.style.cssText = 'font-size:11px;color:#374151;word-break:break-all;text-align:center;line-height:1.2;max-width:80px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;';
            label.textContent = name;
            ghost.appendChild(icon);
            ghost.appendChild(label);
            document.body.appendChild(ghost);
            setTimeout(() => ghost.remove(), 0);
            return ghost;
        },
        _makeAssetDragGhost(fileName, iconClass) {
            const ghost = document.createElement('div');
            ghost.style.cssText = 'position:fixed;top:-200px;left:-200px;width:96px;display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border-radius:12px;background:#f9fafb;border:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.12);pointer-events:none;';
            const icon = document.createElement('i');
            icon.className = iconClass;
            icon.style.cssText = 'font-size:60px;color:#a78bfa;line-height:1;';
            const label = document.createElement('span');
            label.style.cssText = 'font-size:11px;color:#374151;text-align:center;line-height:1.2;max-width:80px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;width:100%;';
            label.textContent = fileName;
            ghost.appendChild(icon);
            ghost.appendChild(label);
            document.body.appendChild(ghost);
            setTimeout(() => ghost.remove(), 0);
            return ghost;
        },
        onDirDragStart(e, dir) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.stringify({
                type: 'dam-folder', id: dir.id, name: dir.name,
                assetsCount: dir.assets_count ?? 0, tabId: this.tabId,
            }));
            e.dataTransfer.setDragImage(this._makeFolderDragGhost(dir.name), 48, 50);
            this._draggingPayload = { type: 'dam-folder', id: dir.id };
            e.currentTarget.style.opacity = '0.4';
        },
        onAssetDragStart(e, asset) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.stringify({
                type: 'dam-asset', id: asset.id, name: asset.file_name, tabId: this.tabId,
            }));
            const iconMap = { image: 'icon-dam-image', video: 'icon-dam-video', audio: 'icon-dam-audio', document: 'icon-dam-doc' };
            e.dataTransfer.setDragImage(this._makeAssetDragGhost(asset.file_name, iconMap[asset.file_type] ?? 'icon-dam-image'), 48, 50);
            this._draggingPayload = { type: 'dam-asset', id: asset.id };
            e.currentTarget.style.opacity = '0.4';
        },
        onDragEnd(e) {
            e.currentTarget.style.opacity = '';
            this.dropTargetId = null;
            this._draggingPayload = null;
            clearTimeout(this._springTimer?.id);
            this._springTimer = null;
        },
        onDirDragOver(e, dir) {
            if (this._draggingPayload?.type === 'dam-folder' && this._draggingPayload?.id === dir.id) return;
            this.dropTargetId = dir.id;
            if (this._springTimer?.dirId === dir.id) return;
            clearTimeout(this._springTimer?.id);
            const timerId = setTimeout(() => {
                if (this._springTimer?.dirId === dir.id) {
                    this.$emit('navigate', dir);
                    this._springTimer = null;
                    this.dropTargetId = null;
                }
            }, 1200);
            this._springTimer = { dirId: dir.id, id: timerId };
        },
        onDirDragLeave(e, dir) {
            if (this._springTimer?.dirId !== dir.id) return;
            if (! e.currentTarget.contains(e.relatedTarget)) {
                clearTimeout(this._springTimer?.id);
                this._springTimer = null;
                this.dropTargetId = null;
            }
        },
        onDirDrop(e, dir) {
            this.dropTargetId = null;
            clearTimeout(this._springTimer?.id);
            this._springTimer = null;
            let payload;
            try { payload = JSON.parse(e.dataTransfer.getData('application/json')); } catch { return; }
            if (! payload?.type) return;
            if (payload.type === 'dam-folder' && payload.id === dir.id) return;
            this.$emit('internal-drop', { payload, targetDir: dir });
        },
        onSpaceDrop(e) {
            if (e.dataTransfer?.types?.includes('Files')) return;
            if (! this.currentDirId) return;
            let payload;
            try { payload = JSON.parse(e.dataTransfer.getData('application/json')); } catch { return; }
            if (! payload?.type) return;
            if (payload.type === 'dam-folder' && this.directories.some(d => d.id === payload.id)) return;
            if (payload.type === 'dam-asset' && this.assets.some(a => a.id === payload.id)) return;
            this.$emit('internal-drop', { payload, targetDir: { id: this.currentDirId } });
        },
        renameDir(dir) { this.$emitter.emit('dam:open-rename-dir', { item: dir }); },
        delDir(dir) {
            const tabId = this.tabId;
            this.$emitter.emit('open-delete-modal', {
                message: "@lang('dam::app.admin.components.modal.confirm.message')",
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.directory.destroy', ':id') }}`.replace(':id', dir.id))
                        .then(({ data }) => {
                            this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.action-completed')" });
                            let attempts = 0;
                            const poll = () => {
                                if (++attempts > 15) return;
                                this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'delete_directory'))
                                    .then(({ data: d }) => {
                                        if (d.status === 'completed') {
                                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.action-completed')" });
                                            this.$emitter.emit('dam:directory-deleted', { id: dir.id });
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
