@once('v-dam-tab')
@push('scripts')
<script type="text/x-template" id="v-dam-tab-template">
    <div class="flex flex-col flex-1 min-h-0 overflow-hidden p-4 gap-3">

        {{-- Row 1: back/forward + breadcrumb + upload + new folder --}}
        <div class="flex items-center gap-2 flex-wrap">
            {{-- History navigation --}}
            <div class="flex items-center gap-0.5 shrink-0">
                <button
                    type="button"
                    class="w-7 h-7 flex items-center justify-center rounded-md transition-colors"
                    :class="canGoBack ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer' : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                    :disabled="!canGoBack"
                    @click="goBack"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button
                    type="button"
                    class="w-7 h-7 flex items-center justify-center rounded-md transition-colors"
                    :class="canGoForward ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer' : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                    :disabled="!canGoForward"
                    @click="goForward"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <nav class="flex items-center gap-1 text-sm flex-1 flex-wrap min-w-0">
                <template v-for="(crumb, i) in breadcrumb" :key="crumb.id">
                    <span v-if="i > 0" class="text-gray-300 dark:text-gray-600">/</span>
                    <button
                        type="button"
                        class="px-1 py-0.5 rounded transition-colors max-w-[120px] truncate"
                        :class="i === breadcrumb.length - 1
                            ? 'text-violet-700 dark:text-violet-300 font-semibold cursor-default'
                            : 'text-gray-500 dark:text-gray-300 hover:text-violet-700 hover:underline cursor-pointer'"
                        :disabled="i === breadcrumb.length - 1"
                        @click="i < breadcrumb.length - 1 ? goTo(crumb) : null"
                    >@{{ crumb.name }}</button>
                </template>
            </nav>

            @if (bouncer()->hasPermission('dam.asset.upload'))
            <template v-if="canUploadHere">
                <input
                    type="file" multiple name="files[]"
                    :id="`explorer-upload-${tabId}`" class="hidden"
                    :disabled="uploading"
                    @change="onFileChange"
                />
                <input
                    type="file" webkitdirectory multiple name="folder_files[]"
                    :id="`explorer-folder-upload-${tabId}`" class="hidden"
                    :disabled="folderUploading"
                    @change="onFolderChange"
                />
                <label
                    :for="`explorer-upload-${tabId}`"
                    class="secondary-button cursor-pointer"
                    :class="{ 'opacity-60 pointer-events-none': uploading || folderUploading }"
                >
                    <svg v-if="uploading || folderUploading" class="animate-spin inline-block h-4 w-4 text-violet-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="#8A2BE2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span v-else class="icon-dam-upload"></span>
                    <span v-if="uploading || folderUploading">@lang('dam::app.admin.dam.index.uploading')</span>
                    <span v-else>@lang('dam::app.admin.dam.index.upload')</span>
                </label>
                <button v-if="uploading || folderUploading" type="button" class="secondary-button" @click="uploading ? cancelUpload() : cancelFolderUpload()">
                    @lang('dam::app.admin.dam.index.cancel')
                </button>
            </template>
            @endif

            {{-- Grid / List view toggle --}}
            <div class="flex border border-gray-300 dark:border-cherry-600 rounded-lg overflow-hidden bg-white dark:bg-cherry-900 shrink-0 ml-auto">
                <button
                    type="button"
                    class="flex items-center px-2.5 py-2 transition-colors"
                    :class="viewMode==='grid' ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-white' : 'text-gray-500 dark:text-white hover:bg-gray-50 dark:hover:bg-cherry-800'"
                    @click="setView('grid')"
                    data-view="grid"
                >
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
                </button>
                <button
                    type="button"
                    class="flex items-center px-2.5 py-2 border-l border-gray-200 dark:border-cherry-700 transition-colors"
                    :class="viewMode==='list' ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-white' : 'text-gray-500 dark:text-white hover:bg-gray-50 dark:hover:bg-cherry-800'"
                    @click="setView('list')"
                    data-view="list"
                >
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="2" width="14" height="2.5" rx="1"/><rect x="1" y="6.75" width="14" height="2.5" rx="1"/><rect x="1" y="11.5" width="14" height="2.5" rx="1"/></svg>
                </button>
            </div>
        </div>

        @include('dam::components.explorer.toolbar')

        {{-- Inline dialog for create/rename operations (teleported to body to escape overflow-hidden ancestors) --}}
        <teleport to="body">
            <div
                v-if="dialog.on"
                class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50"
                style="z-index:10000;"
                @click.self="closeDialog"
            >
                <div class="bg-white dark:bg-cherry-900 rounded-xl border border-gray-200 dark:border-cherry-600 w-full mx-4 p-6" style="max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.25);">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4">@{{ dialogTitle }}</h3>
                    <input
                        ref="dialogInput"
                        v-model="dialog.value"
                        type="text"
                        class="w-full border border-gray-300 dark:border-cherry-600 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-cherry-800 outline-none mb-4"
                        :placeholder="dialogPlaceholder"
                        @keydown.enter.prevent="submitDialog"
                        @keydown.escape="closeDialog"
                    />
                    <div class="flex gap-2 justify-end">
                        <button
                            type="button"
                            class="secondary-button"
                            @click="closeDialog"
                        >@lang('dam::app.admin.explorer.dialog.cancel')</button>
                        <button
                            type="button"
                            class="primary-button"
                            :disabled="dialog.loading || !dialog.value.trim()"
                            @click="submitDialog"
                        >
                            <svg v-if="dialog.loading" class="animate-spin inline-block h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            @lang('dam::app.admin.explorer.dialog.save')
                        </button>
                    </div>
                </div>
            </div>
        </teleport>

        {{-- Clipboard banner --}}
        <div
            v-if="clipboard"
            class="flex items-center gap-2 px-3 py-1.5 text-xs bg-violet-50 dark:bg-violet-900/30 border border-violet-200 dark:border-violet-700 rounded-lg text-violet-700 dark:text-violet-300"
        >
            <span class="shrink-0">📋</span>
            <span class="flex-1 truncate">"@{{ clipboard.name }}" — @lang('dam::app.admin.explorer.clipboard.ready')</span>
            <button
                type="button"
                class="text-violet-400 hover:text-violet-700 dark:hover:text-violet-200 shrink-0"
                @click="clipboard = null"
            >@lang('dam::app.admin.explorer.clipboard.dismiss') ×</button>
        </div>

        {{-- Content area — v-dam-drop-upload handles OS file/folder drops --}}
        <v-dam-drop-upload
            class="flex-1 overflow-y-auto"
            :current-directory="currentDirId ? { id: currentDirId } : null"
            :can-upload="canUploadHere"
            @refresh-datagrid="fetch()"
        >
            {{-- Upload blocking overlay (button upload only) --}}
            <div
                v-if="uploading"
                class="absolute inset-0 z-40 bg-white/70 dark:bg-cherry-900/70 backdrop-blur-sm flex items-center justify-center rounded-lg pointer-events-all"
            >
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin h-8 w-8 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-violet-700 dark:text-violet-300">@lang('dam::app.admin.dam.index.uploading')</p>
                </div>
            </div>

            <v-dam-explorer-grid
                v-if="viewMode === 'grid'"
                :directories="page === 1 ? dirs : []"
                :assets="assets"
                :is-loading="loading"
                :tab-id="tabId"
                :current-dir-id="currentDirId"
                :clipboard="clipboard"
                @navigate="goTo"
                @open-new-tab="openNewTab"
                @bookmark="bookmark"
                @refresh="fetch"
                @internal-drop="onInternalDrop"
            ></v-dam-explorer-grid>
            <v-dam-explorer-list
                v-else
                :directories="page === 1 ? dirs : []"
                :assets="assets"
                :is-loading="loading"
                :sort-by="sortBy"
                :sort-order="sortOrder"
                :tab-id="tabId"
                :current-dir-id="currentDirId"
                :clipboard="clipboard"
                @navigate="goTo"
                @open-new-tab="openNewTab"
                @bookmark="bookmark"
                @sort-change="onSort"
                @refresh="fetch"
            ></v-dam-explorer-list>
        </v-dam-drop-upload>
    </div>
</script>

<script type="module">
app.component('v-dam-tab', {
    template: '#v-dam-tab-template',
    emits: ['tab-state-change', 'tab-label-change'],

    props: {
        tabId:              { type: String, required: true },
        initialDirectoryId: { type: Number, default: null },
        initialSearch:      { type: String, default: '' },
        initialViewMode:    { type: String, default: 'grid' },
        initialPage:        { type: Number, default: 1 },
        initialPerPage:     { type: Number, default: 50 },
        aclBypass:          { type: Boolean, default: false },
        accessibleIds:      { type: Array, default: () => [] },
    },

    data() {
        const storedView = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_explorer_view_mode') : null;
        const storedDir     = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_explorer_active_dir') : null;
        const storedFilters = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_explorer_filter_state') : null;
        return {
            currentDirId: this.initialDirectoryId ?? (storedDir ? Number(storedDir) : null),
            breadcrumb:   [],
            dirs:         [],
            assets:       [],
            meta:         null,
            loading:      false,
            searchInput:  this.initialSearch,
            search:       this.initialSearch,
            viewMode:     storedView || this.initialViewMode,
            sortBy:       'name',
            sortOrder:    'asc',
            page:         this.initialPage,
            perPage:      this.initialPerPage,
            uploading:          false,
            abort:              null,
            folderUploading:    false,
            folderAbort:        null,
            localAccessibleIds: [...(this.accessibleIds || [])],
            debounce:        null,
            navHistory:   [],
            navIdx:       -1,
            dialog: { on: false, type: null, value: '', loading: false, extra: null },
            clipboard:          null,
            ctxTarget:          null,
            available: {
                id: 'dam-explorer',
                columns: [
                    { index: 'file_name',  label: "@lang('dam::app.admin.dam.index.datagrid.file-name')", filterable: true, type: 'string', options: null, input_type: 'text' },
                    { index: 'tag',        label: "@lang('dam::app.admin.dam.index.datagrid.tags')",      filterable: true, type: 'string', options: null, input_type: 'text' },
                    { index: 'extension',  label: "@lang('dam::app.admin.dam.index.datagrid.extension')", filterable: true, type: 'string', options: null, input_type: 'text' },
                    { index: 'created_at', label: "@lang('dam::app.admin.dam.index.datagrid.created-at')", filterable: true, type: 'date_range', input_type: 'date', options: @json((new \Webkul\DataGrid\Column(index: 'created_at', label: '', type: 'date_range'))->getRangeOptions()) },
                    { index: 'updated_at', label: "@lang('dam::app.admin.dam.index.datagrid.updated-at')", filterable: true, type: 'date_range', input_type: 'date', options: @json((new \Webkul\DataGrid\Column(index: 'updated_at', label: '', type: 'date_range'))->getRangeOptions()) },
                ],
            },
            applied: {
                filters: {
                    columns: (() => {
                        try { return storedFilters ? JSON.parse(storedFilters) : []; } catch { return []; }
                    })(),
                },
            },
        };
    },

    computed: {
        canUploadHere() {
            if (this.aclBypass) return true;
            if (! this.currentDirId) return false;
            return this.localAccessibleIds.map(Number).includes(Number(this.currentDirId));
        },
        canGoBack()    { return this.navIdx > 0; },
        canGoForward() { return this.navIdx < this.navHistory.length - 1; },
        dialogTitle()       { return "@lang('dam::app.admin.explorer.dialog.rename-asset.title')"; },
        dialogPlaceholder() { return "@lang('dam::app.admin.explorer.dialog.rename-asset.placeholder')"; },
        activeFilterCount() {
            return this.applied.filters.columns.filter(c =>
                c.value && c.value.length > 0 && c.value.some(v => Array.isArray(v) ? v.some(Boolean) : Boolean(v))
            ).length;
        },
    },

    mounted() {
        this.$axios.get("{{ route('admin.dam.explorer.filter-options') }}")
            .then(({ data }) => {
                (data.properties ?? []).forEach(prop => {
                    this.available.columns.push({
                        index: `prop_${prop}`,
                        label: prop,
                        filterable: true,
                        type: 'string',
                        options: null,
                        input_type: 'text',
                    });
                });
            })
            .catch(() => {});

        this.$emitter.on(`dam:explorer-ctx-refresh:${this.tabId}`, () => this.fetch());
        this.$emitter.on(`dam:dir-deleted:${this.tabId}`, () => {
            this.navHistory = this.navHistory.slice(0, this.navIdx + 1);
        });
        this.$emitter.on('dam:directory-mutated', () => this.fetch());

        // Tree "Upload files" → emit dam:upload-files with pre-built FormData
        this.$emitter.on('dam:upload-files', (formData) => {
            if (this.uploading) return;
            this.uploading = true;
            this.abort = new AbortController();
            this.$axios.post("{{ route('admin.dam.assets.upload') }}", formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                signal: this.abort.signal,
            }).then(() => {
                this.fetch();
                this.$emitter.emit('dam:tree-reload');
                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.dam.index.upload-complete')" });
            }).catch(err => {
                if (! (this.$axios.isCancel?.(err) || err.code === 'ERR_CANCELED')) {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.asset.datagrid.files-upload-failed')" });
                }
            }).finally(() => { this.uploading = false; this.abort = null; });
        });

        // Tree folder upload state → mirror on explorer breadcrumb button
        this.$emitter.on('dam:folder-upload-start', () => { this.folderUploading = true; });
        this.$emitter.on('dam:folder-upload-end',   () => { this.folderUploading = false; });
        this.$emitter.on('dam:directory-granted', (id) => {
            const numId = Number(id);
            if (! this.localAccessibleIds.map(Number).includes(numId)) {
                this.localAccessibleIds.push(numId);
            }
        });

        this.$emitter.on(`dam:tab-navigate:${this.tabId}`, ({ directoryId, name, fromTree }) => {
            this.goTo({ id: directoryId, name }, true, false, fromTree);
        });

        this.$emitter.on(`dam:explorer-upload-here:${this.tabId}`, ({ directoryId }) => {
            if (directoryId !== this.currentDirId) {
                this.goTo({ id: directoryId });
            }
            this.$nextTick(() => {
                document.getElementById(`explorer-upload-${this.tabId}`)?.click();
            });
        });

        this.$emitter.on(`dam:explorer-folder-upload-here:${this.tabId}`, ({ directoryId }) => {
            if (directoryId !== this.currentDirId) {
                this.goTo({ id: directoryId });
            }
            this.$nextTick(() => {
                document.getElementById(`explorer-folder-upload-${this.tabId}`)?.click();
            });
        });

        this.$emitter.on(`dam:explorer-rename-asset:${this.tabId}`, ({ asset }) => {
            this.openDialog('rename-asset', asset.file_name, { asset });
        });


        this.$emitter.on(`dam:ctx-open:${this.tabId}`, ({ item, type }) => {
            this.ctxTarget = { item, type };
        });

        this.$emitter.on(`dam:explorer-copy:${this.tabId}`, ({ item, type }) => {
            this.clipboard = {
                type: type === 'directory' ? 'directory' : 'asset',
                id:   item.id,
                name: item.name ?? item.file_name,
            };
            this.ctxTarget = { item, type };
        });

        this.$emitter.on(`dam:explorer-paste:${this.tabId}`, ({ targetDirId }) => {
            this.executePaste(targetDirId ?? this.currentDirId);
        });

        this.$emitter.on(`dam:explorer-kb-copy:${this.tabId}`, () => {
            if (this.ctxTarget) {
                this.clipboard = {
                    type: this.ctxTarget.type === 'directory' ? 'directory' : 'asset',
                    id:   this.ctxTarget.item.id,
                    name: this.ctxTarget.item.name ?? this.ctxTarget.item.file_name,
                };
            }
        });

        this.$emitter.on(`dam:explorer-kb-paste:${this.tabId}`, () => {
            this.executePaste(this.currentDirId);
        });

        if (this.currentDirId) {
            // Sync tree to the current directory before it loads so that
            // setDefaultSeletedItem (which navigates to root) is suppressed.
            this.$emitter.emit('dam:explorer-tree-sync', { id: this.currentDirId });
            this.fetch();
        } else {
            this.loadRoot();
        }
    },

    methods: {
        loadRoot() {
            this.$axios.get("{{ route('admin.dam.directory.index') }}")
                .then(({ data }) => {
                    const root = Array.isArray(data.data) ? data.data[0] : null;
                    if (root) this.goTo({ id: root.id, name: root.name }, true);
                });
        },

        fetch() {
            if (! this.currentDirId) return;
            this.loading = true;
            const filterParams = {};
            this.applied.filters.columns.forEach(col => {
                switch (col.index) {
                    case 'file_name': filterParams.filter_file_name = col.value[0] || undefined; break;
                    case 'extension': filterParams.filter_extension = col.value[0] || undefined; break;
                    case 'tag':       filterParams.filter_tag = col.value[0] || undefined; break;
                    case 'created_at':
                        if (col.value[0]) {
                            filterParams.filter_created_from = col.value[0][0] || undefined;
                            filterParams.filter_created_to   = col.value[0][1] || undefined;
                        }
                        break;
                    case 'updated_at':
                        if (col.value[0]) {
                            filterParams.filter_updated_from = col.value[0][0] || undefined;
                            filterParams.filter_updated_to   = col.value[0][1] || undefined;
                        }
                        break;
                    default:
                        if (col.index.startsWith('prop_')) {
                            filterParams[`filter_${col.index}`] = col.value[0] || undefined;
                        }
                }
            });
            this.$axios.get("{{ route('admin.dam.explorer.index') }}", {
                params: {
                    directory_id: this.currentDirId,
                    search:       this.search || undefined,
                    page:         this.page,
                    per_page:     this.perPage,
                    sort_by:      this.sortBy,
                    sort_order:   this.sortOrder,
                    ...filterParams,
                },
            }).then(({ data }) => {
                this.dirs   = data.directories;
                this.assets = data.assets;
                this.meta   = data.meta;

                if (Array.isArray(data.breadcrumb) && data.breadcrumb.length) {
                    this.breadcrumb = data.breadcrumb;
                    if (this.navHistory[this.navIdx]) {
                        this.navHistory[this.navIdx].breadcrumb = this.breadcrumb.map(c => ({ ...c }));
                    }
                    const label = this.breadcrumb[this.breadcrumb.length - 1]?.name ?? 'Root';
                    this.$emit('tab-label-change', label);
                }

                try {
                    const snapshot = this.applied.filters.columns.map(c => ({ index: c.index, value: c.value }));
                    localStorage.setItem('dam_explorer_filter_state', JSON.stringify(snapshot));
                } catch {}

                this.sync();
            }).catch(err => {
                const status = err?.response?.status;
                if (status === 404 || status === 403) {
                    this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('dam::app.admin.explorer.folder.deleted')" });
                    this.navHistory = [];
                    this.navIdx = -1;
                    this.loadRoot();
                }
            }).finally(() => { this.loading = false; });
        },

        goTo(dir, isRoot = false, skipHistory = false, fromTree = false) {
            if (! dir?.id) return;
            this.currentDirId = dir.id;
            try { localStorage.setItem('dam_explorer_active_dir', dir.id); } catch {}
            this.search       = '';
            this.searchInput  = '';
            this.page         = 1;

            if (isRoot || this.breadcrumb.length === 0) {
                // Show "… / folder" placeholder — API will replace with full ancestor path
                this.breadcrumb = isRoot
                    ? [{ id: null, name: '…' }, { id: dir.id, name: dir.name ?? '…' }]
                    : [{ id: dir.id, name: dir.name ?? '…' }];
            } else {
                const idx = this.breadcrumb.findIndex(c => c.id === dir.id);
                if (idx >= 0) {
                    this.breadcrumb = this.breadcrumb.slice(0, idx + 1);
                } else {
                    this.breadcrumb.push({ id: dir.id, name: dir.name ?? '…' });
                }
            }

            if (!skipHistory) {
                this.navHistory = this.navHistory.slice(0, this.navIdx + 1);
                this.navHistory.push({ dirId: dir.id, breadcrumb: this.breadcrumb.map(c => ({ ...c })) });
                this.navIdx = this.navHistory.length - 1;
            }

            const label = this.breadcrumb[this.breadcrumb.length - 1]?.name ?? 'Root';
            this.$emit('tab-label-change', label);
            this.$emitter.emit('dam:explorer-navigate', { directoryId: dir.id });
            this.$emitter.emit('dam:explorer-tree-sync', { id: dir.id, fromTree });
            this.fetch();
        },

        goBack() {
            if (! this.canGoBack) return;
            this.navIdx--;
            const entry = this.navHistory[this.navIdx];
            this.currentDirId = entry.dirId;
            this.breadcrumb   = entry.breadcrumb.map(c => ({ ...c }));
            this.search       = '';
            this.searchInput  = '';
            this.page         = 1;
            const label = this.breadcrumb[this.breadcrumb.length - 1]?.name ?? 'Root';
            this.$emit('tab-label-change', label);
            this.$emitter.emit('dam:explorer-navigate', { directoryId: entry.dirId });
            this.$emitter.emit('dam:explorer-tree-sync', { id: entry.dirId });
            this.fetch();
        },

        goForward() {
            if (! this.canGoForward ) return;
            this.navIdx++;
            const entry = this.navHistory[this.navIdx];
            this.currentDirId = entry.dirId;
            this.breadcrumb   = entry.breadcrumb.map(c => ({ ...c }));
            this.search       = '';
            this.searchInput  = '';
            this.page         = 1;
            const label = this.breadcrumb[this.breadcrumb.length - 1]?.name ?? 'Root';
            this.$emit('tab-label-change', label);
            this.$emitter.emit('dam:explorer-navigate', { directoryId: entry.dirId });
            this.$emitter.emit('dam:explorer-tree-sync', { id: entry.dirId });
            this.fetch();
        },

        openNewTab(dir) {
            this.$emitter.emit('dam:open-in-new-tab', { directoryId: dir.id, name: dir.name });
        },

        bookmark(dir) {
            this.$emitter.emit('dam:add-bookmark', { id: dir.id, name: dir.name });
        },

        openDialog(type, value, extra) {
            this.dialog = { on: true, type, value, loading: false, extra };
            this.$nextTick(() => { this.$refs.dialogInput?.focus(); });
        },

        closeDialog() {
            this.dialog = { on: false, type: null, value: '', loading: false, extra: null };
        },

        submitDialog() {
            const name = this.dialog.value.trim();
            if (! name || this.dialog.loading) return;

            this.dialog.loading = true;
            const asset = this.dialog.extra?.asset;
            this.$axios.post("{{ route('admin.dam.assets.rename') }}", { id: asset.id, file_name: name })
                .then(({ data }) => {
                    this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? 'Done.' });
                    this.closeDialog();
                    this.fetch();
                })
                .catch(err => {
                    const msg = err?.response?.data?.errors?.file_name?.[0] ?? err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')";
                    this.$emitter.emit('add-flash', { type: 'error', message: msg });
                    this.dialog.loading = false;
                });
        },

        onSearch() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => { this.search = this.searchInput; this.page = 1; this.fetch(); }, 300);
        },

        clearSearch() {
            this.searchInput = '';
            this.search      = '';
            this.page        = 1;
            this.fetch();
        },

        setView(mode) {
            this.viewMode = mode;
            try { localStorage.setItem('dam_explorer_view_mode', mode); } catch {}
            this.sync();
        },

        findAppliedColumn(columnIndex) {
            return this.applied.filters.columns.find(c => c.index === columnIndex);
        },
        hasAnyAppliedColumnValues(columnIndex) {
            return (this.findAppliedColumn(columnIndex)?.value?.length ?? 0) > 0;
        },
        getAppliedColumnValues(columnIndex) {
            return this.findAppliedColumn(columnIndex)?.value ?? [];
        },
        removeAppliedColumnValue(columnIndex, appliedColumnValue) {
            const col = this.findAppliedColumn(columnIndex);
            if (! col) return;
            col.value = col.value.filter(v => v !== appliedColumnValue);
            if (! col.value.length) {
                this.applied.filters.columns = this.applied.filters.columns.filter(c => c.index !== columnIndex);
            }
        },
        removeAppliedColumnAllValues(columnIndex) {
            this.applied.filters.columns = this.applied.filters.columns.filter(c => c.index !== columnIndex);
            this.page = 1;
            this.fetch();
        },
        filterPage($event, column = null, additional = {}) {
            const quickFilter = additional?.quickFilter;
            if (quickFilter?.isActive) {
                const options = quickFilter.selectedFilter;
                if (column.type === 'date_range' || column.type === 'datetime_range') {
                    this.applyFilter(column, options.from, { range: { name: 'from' } });
                    this.applyFilter(column, options.to,   { range: { name: 'to' }   });
                }
            } else {
                if ($event?.target?.value === undefined) {
                    $event = { target: { value: $event } };
                }
                this.applyFilter(column, $event.target.value, additional);
                if (column) $event.target.value = '';
            }
        },
        applyFilter(column, requestedValue, additional = {}) {
            if (! column) return;
            const appliedColumn = this.findAppliedColumn(column.index);
            if (requestedValue === undefined || requestedValue === '' || appliedColumn?.value.includes(requestedValue)) return;
            if (column.type === 'date_range' || column.type === 'datetime_range') {
                const { range } = additional;
                if (appliedColumn) {
                    const ranges = appliedColumn.value[0] ? [...appliedColumn.value[0]] : ['', ''];
                    if (range.name === 'from') ranges[0] = requestedValue;
                    if (range.name === 'to')   ranges[1] = requestedValue;
                    appliedColumn.value = [ranges];
                } else {
                    const ranges = ['', ''];
                    if (range.name === 'from') ranges[0] = requestedValue;
                    if (range.name === 'to')   ranges[1] = requestedValue;
                    this.applied.filters.columns.push({ ...column, value: [ranges] });
                }
            } else {
                if (appliedColumn) {
                    appliedColumn.value.push(requestedValue);
                } else {
                    this.applied.filters.columns.push({ ...column, value: [requestedValue] });
                }
            }
        },
        runFilters() {
            this.$refs.explorerFilterDrawer?.close();
            this.page = 1;
            this.fetch();
        },
        clearFilters() {
            this.applied.filters.columns = [];
            try { localStorage.removeItem('dam_explorer_filter_state'); } catch {}
            this.page = 1;
            this.fetch();
        },

        onSort({ sortBy, sortOrder }) { this.sortBy = sortBy; this.sortOrder = sortOrder; this.page = 1; this.fetch(); },

        onPage(p) { this.page = p; this.fetch(); },

        onPerPage(pp) { this.perPage = pp; this.page = 1; this.fetch(); },

        sync() {
            this.$emit('tab-state-change', {
                directoryId: this.currentDirId,
                search:      this.search,
                viewMode:    this.viewMode,
                page:        this.page,
                perPage:     this.perPage,
            });
        },

        onFileChange(e) {
            const files = e.target.files;
            if (! files?.length) return;
            const fd = new FormData();
            Array.from(files).forEach(f => fd.append('files[]', f));
            fd.append('directory_id', this.currentDirId);
            this.uploading = true;
            this.abort     = new AbortController();
            this.$axios.post("{{ route('admin.dam.assets.upload') }}", fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
                signal: this.abort.signal,
            }).then(() => {
                this.fetch();
                this.$emitter.emit('dam:tree-reload');
                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.dam.index.upload-complete')" });
            }).catch(err => {
                if (! (this.$axios.isCancel?.(err) || err.code === 'ERR_CANCELED')) {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.asset.datagrid.files-upload-failed')" });
                }
            }).finally(() => { this.uploading = false; this.abort = null; e.target.value = ''; });
        },

        cancelUpload() { this.abort?.abort(); },

        cancelFolderUpload() { this.folderAbort?.abort(); this.$emitter.emit('dam:cancel-folder-upload'); },

        onInternalDrop({ payload, targetDir }) {
            if (! this.aclBypass && ! this.accessibleIds.map(Number).includes(Number(targetDir.id))) {
                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('dam::app.admin.explorer.access-denied')" });
                return;
            }
            if (payload.type === 'dam-asset') {
                this.$axios.post("{{ route('admin.dam.assets.moved') }}", {
                    move_item_id:  payload.id,
                    new_parent_id: targetDir.id,
                }).then(() => {
                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.move-progress')" });
                    this.fetch();
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" });
                });
            } else if (payload.type === 'dam-folder') {
                this.$axios.post("{{ route('admin.dam.directory.moved') }}", {
                    move_item_id:  payload.id,
                    new_parent_id: targetDir.id,
                }).then(() => {
                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.move-progress')" });
                    this.fetch();
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" });
                });
            }
        },

        onFolderChange(e) {
            const files = Array.from(e.target.files ?? []);
            if (! files.length) return;

            const hasFiles = files.some(f => f.size > 0);

            if (hasFiles) {
                const fd = new FormData();
                files.forEach(f => {
                    fd.append('files[]', f);
                    fd.append('relative_paths[]', f.webkitRelativePath || f.name);
                });
                fd.append('directory_id', this.currentDirId);
                fd.append('preserve_root', '1');

                this.folderUploading = true;
                this.folderAbort     = new AbortController();

                this.$axios.post("{{ route('admin.dam.assets.upload_folder') }}", fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    signal: this.folderAbort.signal,
                }).then(() => {
                    this.fetch();
                    this.$emitter.emit('dam:tree-reload');
                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.dam.index.upload-complete')" });
                }).catch(err => {
                    if (! (this.$axios.isCancel?.(err) || err.code === 'ERR_CANCELED')) {
                        this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.asset.datagrid.files-upload-failed')" });
                    }
                }).finally(() => { this.folderUploading = false; this.folderAbort = null; e.target.value = ''; });
            } else {
                const paths = [...new Set(
                    files.map(f => f.webkitRelativePath || f.name)
                         .map(p => p.split('/').slice(0, -1).join('/'))
                         .filter(Boolean)
                )];

                if (! paths.length) { e.target.value = ''; return; }

                this.$axios.post("{{ route('admin.dam.directory.create_structure') }}", {
                    directory_id: this.currentDirId,
                    paths,
                }).then(() => {
                    this.fetch();
                    this.$emitter.emit('dam:tree-reload');
                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.dam.index.upload-complete')" });
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" });
                }).finally(() => { e.target.value = ''; });
            }
        },

        executePaste(targetDirId) {
            if (! this.clipboard || ! targetDirId || ! this.canUploadHere) return;
            const cb = this.clipboard;

            if (cb.type === 'asset') {
                this.$axios.post("{{ route('admin.dam.explorer.copy.asset') }}", {
                    asset_id:            cb.id,
                    target_directory_id: targetDirId,
                }).then(({ data }) => {
                    this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.context.copy-done')" });
                    this.fetch();
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message });
                });
            } else {
                this.$axios.post("{{ route('admin.dam.explorer.copy.directory') }}", {
                    directory_id:        cb.id,
                    target_directory_id: targetDirId,
                }).then(({ data }) => {
                    this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.context.copy-progress')" });
                    this.fetch();
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message });
                });
            }
        },
    },
});
</script>
@endpush
@endonce
