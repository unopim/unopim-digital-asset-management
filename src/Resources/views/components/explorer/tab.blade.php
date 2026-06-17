@once('v-dam-tab')
@include('dam::components.explorer.folder-picker')
@push('scripts')
<script type="text/x-template" id="v-dam-tab-template">
    <div class="flex flex-col flex-1 min-h-0 overflow-hidden p-4 gap-3">

        {{-- Operation overlay (directory copy / move in-flight) --}}
        <div
            v-if="operationOverlay.show"
            class="fixed inset-0 flex items-center justify-center bg-black/50 dark:bg-black/70 backdrop-blur-sm"
            style="z-index: 99998;"
            role="status"
            aria-live="polite"
        >
            <div
                class="flex flex-col items-center gap-4 bg-white dark:bg-cherry-800 rounded-xl px-12 py-8 shadow-2xl border border-gray-200 dark:border-cherry-600 w-96 max-w-[90vw] relative"
                style="min-width: 360px; z-index: 99999;"
            >
                <svg class="animate-spin h-12 w-12 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-base font-semibold text-gray-900 dark:text-white text-center break-words" v-text="operationOverlay.label"></span>
            </div>
        </div>

        {{-- Row 1: sidebar toggle + back/forward + breadcrumb + actions --}}
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Sidebar collapse toggle — only when at least one sidebar component is enabled --}}
            @if (config('dam.explorer.show_tree') || config('dam.explorer.bookmarks_enabled'))
            <button
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md transition-colors shrink-0"
                :class="sidebarVisible ? 'text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30' : 'text-zinc-600 dark:text-white hover:bg-gray-100 dark:hover:bg-cherry-800'"
                title="@lang('dam::app.admin.explorer.toolbar.toggle-sidebar')"
                @click="toggleSidebar"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                    class="transition-transform duration-200"
                    :style="sidebarVisible ? '' : 'transform:scaleX(-1)'"
                >
                    <polyline points="11 17 6 12 11 7"></polyline>
                    <polyline points="18 17 13 12 18 7"></polyline>
                </svg>
            </button>
            @endif

            <v-dam-explorer-breadcrumb
                :breadcrumbs="breadcrumb"
                :can-go-back="canGoBack"
                :can-go-forward="canGoForward"
                :current-dir-id="currentDirId"
                :loading="loading"
                @back="goBack"
                @forward="goForward"
                @navigate="goTo"
                @open-new-tab="openNewTab"
                @drop="onInternalDrop"
            ></v-dam-explorer-breadcrumb>

            {{-- Current directory actions: Download Zip + Share --}}
            @if (bouncer()->hasPermission('dam.directory.download_zip'))
            <button
                v-if="currentDirId"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md transition-colors text-zinc-600 dark:text-white hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer shrink-0"
                title="@lang('dam::app.admin.dam.index.directory.actions.download-zip')"
                @click="downloadCurrentDir"
            >
                <i class="icon-dam-download text-xl"></i>
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.share'))
            <button
                v-if="currentDirId"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md transition-colors text-zinc-600 dark:text-white hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer shrink-0"
                title="@lang('dam::app.admin.dam.index.directory.actions.share')"
                @click="shareCurrentDir"
            >
                <i class="icon-dam-link text-xl"></i>
            </button>
            @endif
            @if (config('dam.explorer.bookmarks_enabled'))
            <button
                v-if="currentDirId"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md transition-colors text-zinc-600 dark:text-white hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer shrink-0"
                title="@lang('dam::app.admin.explorer.context.bookmark')"
                @click="bookmarkCurrentDir"
            >
                <i class="icon-star text-xl"></i>
            </button>
            @endif

            @if (bouncer()->hasPermission('dam.asset.upload'))
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
            <!-- <template v-if="canUploadHere">
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
            </template> -->
            @endif

            {{-- Grid / List view toggle --}}
            <v-dam-explorer-view-toggle :model-value="viewMode" @update:model-value="setView($event)"></v-dam-explorer-view-toggle>
        </div>

        @include('dam::components.explorer.toolbar')

        {{-- Rename / create dialog --}}
        <v-dam-input-dialog
            :is-open="dialog.on"
            :title="dialogTitle"
            :placeholder="dialogPlaceholder"
            :initial-value="dialog.value"
            :is-loading="dialog.loading"
            @submit="onDialogSubmit"
            @close="closeDialog"
        ></v-dam-input-dialog>

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
            class="flex-1 overflow-y-auto flex flex-col"
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
                class="flex-1"
                :directories="page === 1 ? dirs : []"
                :assets="assets"
                :is-loading="loading"
                :tab-id="tabId"
                :current-dir-id="currentDirId"
                :clipboard="clipboard"
                :can-access-current-dir="canAccessCurrentDir"
                :selection="selection"
                @toggle-select="toggleSelect"
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
                :can-access-current-dir="canAccessCurrentDir"
                :selection="selection"
                @toggle-select="toggleSelect"
                @navigate="goTo"
                @open-new-tab="openNewTab"
                @bookmark="bookmark"
                @sort-change="onSort"
                @refresh="fetch"
                @internal-drop="onInternalDrop"
            ></v-dam-explorer-list>
        </v-dam-drop-upload>

        <v-dam-folder-picker
            :open="folderPicker.open"
            :tab-id="tabId"
            :excluded-dir-ids="selection.ids.filter(i => i.type === 'directory').map(i => i.id)"
            @picked="onFolderPickerPicked"
            @close="closeFolderPicker"
        ></v-dam-folder-picker>
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
        const storedView    = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_explorer_view_mode') : null;
        const storedDir     = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_explorer_active_dir') : null;
        const storedFilters = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_explorer_filter_state') : null;
        const storedSidebar = typeof localStorage !== 'undefined' ? localStorage.getItem('dam_show_sidebar') : null;
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
            uploadTargetDirId:  null,
            localAccessibleIds: [...(this.accessibleIds || [])],
            debounce:        null,
            navHistory:   [],
            navIdx:       -1,
            dialog: { on: false, type: null, value: '', loading: false, extra: null },
            clipboard:          null,
            pasting:            false,
            operationOverlay:   { show: false, label: '' },
            selection:          { ids: [], mode: 'none' },
            folderPicker:       { open: false, mode: null },
            ctxTarget:          null,
            sidebarVisible:     storedSidebar !== null ? storedSidebar !== 'false' : true,
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
        canAccessCurrentDir() {
            return this.aclBypass || !!(this.meta?.can_access_current);
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

        this._onSidebarVisibility = (visible) => { this.sidebarVisible = visible; };
        this.$emitter.on('dam:sidebar-visibility-changed', this._onSidebarVisibility);

        this.$emitter.on(`dam:explorer-ctx-refresh:${this.tabId}`, () => this.fetch());
        this.$emitter.on(`dam:operation-overlay:show:${this.tabId}`, ({ label }) => {
            this.operationOverlay = { show: true, label: label ?? '' };
        });
        this.$emitter.on(`dam:operation-overlay:hide:${this.tabId}`, () => {
            this.operationOverlay = { show: false, label: '' };
        });
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
            this.uploadTargetDirId = directoryId ?? this.currentDirId;
            this.$nextTick(() => {
                document.getElementById(`explorer-upload-${this.tabId}`)?.click();
            });
        });

        this.$emitter.on(`dam:explorer-folder-upload-here:${this.tabId}`, ({ directoryId }) => {
            this.uploadTargetDirId = directoryId ?? this.currentDirId;
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
            // Seed history so the first navigation after reload has a valid Back state.
            // fetch() will backfill the breadcrumb once the API responds.
            this.navHistory.push({ dirId: this.currentDirId, breadcrumb: [] });
            this.navIdx = 0;
            this.fetch();
        } else {
            this.loadRoot();
        }
    },

    beforeUnmount() {
        if (this._onSidebarVisibility) this.$emitter.off('dam:sidebar-visibility-changed', this._onSidebarVisibility);
    },

    methods: {
        toggleSelect(id, type) {
            const idx = this.selection.ids.findIndex(i => i.id === id && i.type === type);
            if (idx === -1) {
                this.selection.ids.push({ id, type });
            } else {
                this.selection.ids.splice(idx, 1);
            }
            this.computeSelectionMode();
        },

        toggleSelectAll() {
            if (this.selection.mode === 'all' || this.selection.mode === 'partial') {
                this.clearSelection();
            } else {
                this.selection.ids = [
                    ...this.dirs.map(d => ({ id: d.id, type: 'directory' })),
                    ...this.assets.map(a => ({ id: a.id, type: 'asset' })),
                ];
                this.computeSelectionMode();
            }
        },

        clearSelection() {
            this.selection.ids  = [];
            this.selection.mode = 'none';
        },

        performMassDelete() {
            const count = this.selection.ids.length;

            this.$emitter.emit('open-delete-modal', {
                message: "@lang('dam::app.admin.explorer.mass-actions.confirm')".replace(':count', count),
                agree: async () => {
                    const assetIds = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
                    const dirIds   = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);

                    this.$emitter.emit(`dam:operation-overlay:show:${this.tabId}`, {
                        label: "@lang('dam::app.admin.explorer.mass-actions.deleting')".replace(':count', count),
                    });

                    if (assetIds.length) {
                        try {
                            await this.$axios.post('{{ route("admin.dam.assets.mass_delete") }}', { indices: assetIds });
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: "@lang('dam::app.admin.explorer.mass-actions.deleted-assets')".replace(':count', assetIds.length),
                            });
                        } catch (e) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.error-operation')",
                            });
                        }
                    }

                    if (dirIds.length) {
                        try {
                            await this.$axios.post('{{ route("admin.dam.directory.mass_destroy") }}', { indices: dirIds });
                            await new Promise((resolve) => {
                                let attempts = 0;
                                const poll = () => {
                                    if (++attempts > 30) { resolve(); return; }
                                    this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'delete_directory'))
                                        .then(({ data: d }) => {
                                            if (d.status === 'completed') {
                                                dirIds.forEach(id => this.$emitter.emit('dam:directory-deleted', { id }));
                                                this.$emitter.emit('dam:tree-reload');
                                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.mass-actions.deleted-dirs')".replace(':count', dirIds.length) });
                                                resolve();
                                            } else if (d.status === 'failed') {
                                                this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                                resolve();
                                            } else { setTimeout(poll, 2000); }
                                        }).catch(() => { setTimeout(poll, 2000); });
                                };
                                setTimeout(poll, 1000);
                            });
                        } catch (e) {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.error-operation')",
                            });
                        }
                    }

                    this.$emitter.emit(`dam:operation-overlay:hide:${this.tabId}`);
                    this.clearSelection();
                    this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`);
                },
            });
        },

        openFolderPicker(mode) {
            this.folderPicker = { open: true, mode };
        },

        closeFolderPicker() {
            this.folderPicker = { open: false, mode: null };
        },

        onFolderPickerPicked(targetDirId) {
            const mode = this.folderPicker.mode;
            this.folderPicker = { open: false, mode: null };
            if (mode === 'move') {
                this.performMassMove(targetDirId);
            } else {
                this.performMassCopy(targetDirId);
            }
        },

        performMassMove(targetDirId) {
            const assetIds = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
            const dirIds   = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);
            const count    = this.selection.ids.length;

            this.$emitter.emit(`dam:operation-overlay:show:${this.tabId}`, {
                label: "@lang('dam::app.admin.explorer.mass-actions.moving')".replace(':count', count),
            });

            (async () => {
                try {
                    await this.$axios.post('{{ route("admin.dam.explorer.mass_move") }}', {
                        asset_ids:           assetIds,
                        directory_ids:       dirIds,
                        target_directory_id: targetDirId,
                    });

                    await new Promise((resolve) => {
                        let attempts = 0;
                        const poll = () => {
                            if (++attempts > 150) { resolve(); return; }
                            this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'mass_move'))
                                .then(({ data: d }) => {
                                    if (d.status === 'completed') {
                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: "@lang('dam::app.admin.explorer.mass-actions.move-done')",
                                        });
                                        dirIds.forEach(id => this.$emitter.emit('dam:directory-deleted', { id }));
                                        this.$emitter.emit('dam:tree-reload');
                                        resolve();
                                    } else if (d.status === 'failed') {
                                        this.$emitter.emit('add-flash', { type: 'error', message: d.message || "@lang('dam::app.admin.dam.index.directory.error-operation')" });
                                        resolve();
                                    } else { setTimeout(poll, 2000); }
                                }).catch(() => { setTimeout(poll, 2000); });
                        };
                        setTimeout(poll, 1000);
                    });
                } catch (e) {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.error-operation')",
                    });
                }

                this.$emitter.emit(`dam:operation-overlay:hide:${this.tabId}`);
                this.clearSelection();
                this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`);
            })();
        },

        performMassCopy(targetDirId) {
            const assetIds = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
            const dirIds   = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);
            const count    = this.selection.ids.length;

            this.$emitter.emit(`dam:operation-overlay:show:${this.tabId}`, {
                label: "@lang('dam::app.admin.explorer.mass-actions.copying')".replace(':count', count),
            });

            (async () => {
                try {
                    await this.$axios.post('{{ route("admin.dam.explorer.mass_copy") }}', {
                        asset_ids:           assetIds,
                        directory_ids:       dirIds,
                        target_directory_id: targetDirId,
                    });

                    await new Promise((resolve) => {
                        let attempts = 0;
                        const poll = () => {
                            if (++attempts > 150) { resolve(); return; }
                            this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'mass_copy'))
                                .then(({ data: d }) => {
                                    if (d.status === 'completed') {
                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: "@lang('dam::app.admin.explorer.mass-actions.copy-done')",
                                        });
                                        this.$emitter.emit('dam:tree-reload');
                                        resolve();
                                    } else if (d.status === 'failed') {
                                        this.$emitter.emit('add-flash', { type: 'error', message: d.message || "@lang('dam::app.admin.dam.index.directory.error-operation')" });
                                        resolve();
                                    } else { setTimeout(poll, 2000); }
                                }).catch(() => { setTimeout(poll, 2000); });
                        };
                        setTimeout(poll, 1000);
                    });
                } catch (e) {
                    this.$emitter.emit('add-flash', {
                        type: 'error',
                        message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.error-operation')",
                    });
                }

                this.$emitter.emit(`dam:operation-overlay:hide:${this.tabId}`);
                this.clearSelection();
                this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`);
            })();
        },

        computeSelectionMode() {
            const total = this.dirs.length + this.assets.length;
            const count = this.selection.ids.length;
            this.selection.mode = count === 0 ? 'none' : count === total ? 'all' : 'partial';
        },

        toggleSidebar() {
            this.$emitter.emit('dam:toggle-sidebar');
        },

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
            this.clearSelection();
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

        downloadCurrentDir() {
            if (! this.currentDirId) return;
            window.open(`{{ route('admin.dam.directory.zip_download', ':id') }}`.replace(':id', this.currentDirId), '_self');
        },

        shareCurrentDir() {
            if (! this.currentDirId) return;
            this.$emitter.emit('open-share-modal', { targetType: 'directory', targetId: this.currentDirId });
        },

        bookmarkCurrentDir() {
            if (! this.currentDirId) return;
            const label = this.breadcrumb[this.breadcrumb.length - 1]?.name ?? 'Root';
            this.$emitter.emit('dam:add-bookmark', { id: this.currentDirId, name: label });
        },

        openDialog(type, value, extra) {
            this.dialog = { on: true, type, value, loading: false, extra };
        },

        closeDialog() {
            this.dialog = { on: false, type: null, value: '', loading: false, extra: null };
        },

        onDialogSubmit(name) {
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
            const targetDirId = this.uploadTargetDirId ?? this.currentDirId;
            this.uploadTargetDirId = null;
            const fd = new FormData();
            Array.from(files).forEach(f => fd.append('files[]', f));
            fd.append('directory_id', targetDirId);
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
                const originTabId = payload.tabId;
                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.move-asset-started')" });
                this.$axios.post("{{ route('admin.dam.assets.moved') }}", {
                    move_item_id:  payload.id,
                    new_parent_id: targetDir.id,
                }).then(() => {
                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.move-asset-done')" });
                    this.fetch();
                    if (originTabId && originTabId !== this.tabId) {
                        this.$emitter.emit(`dam:explorer-ctx-refresh:${originTabId}`);
                    }
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" });
                });
            } else if (payload.type === 'dam-folder') {
                const tabId = this.tabId;
                const originTabId = payload.tabId;
                const folderName = payload.name ?? '';
                this.operationOverlay = { show: true, label: `@lang('dam::app.admin.dam.index.move.directory')`.replace(':name', folderName) };
                this.$axios.post("{{ route('admin.dam.directory.moved') }}", {
                    move_item_id:  payload.id,
                    new_parent_id: targetDir.id,
                }).then(() => {
                    let attempts = 0;
                    const poll = () => {
                        if (++attempts > 30) {
                            this.operationOverlay = { show: false, label: '' };
                            return;
                        }
                        this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'move_directory_structure'))
                            .then(({ data: d }) => {
                                if (d.status === 'completed') {
                                    this.operationOverlay = { show: false, label: '' };
                                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.move-done')" });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                    if (originTabId && originTabId !== tabId) {
                                        this.$emitter.emit(`dam:explorer-ctx-refresh:${originTabId}`);
                                    }
                                } else if (d.status === 'failed') {
                                    this.operationOverlay = { show: false, label: '' };
                                    this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                    if (originTabId && originTabId !== tabId) {
                                        this.$emitter.emit(`dam:explorer-ctx-refresh:${originTabId}`);
                                    }
                                } else { setTimeout(poll, 2000); }
                            }).catch(() => { setTimeout(poll, 2000); });
                    };
                    setTimeout(poll, 1000);
                }).catch(err => {
                    this.operationOverlay = { show: false, label: '' };
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" });
                });
            }
        },

        onFolderChange(e) {
            const files = Array.from(e.target.files ?? []);
            if (! files.length) return;
            const targetDirId = this.uploadTargetDirId ?? this.currentDirId;
            this.uploadTargetDirId = null;

            const hasFiles = files.some(f => f.size > 0);

            if (hasFiles) {
                const fd = new FormData();
                files.forEach(f => {
                    fd.append('files[]', f);
                    fd.append('relative_paths[]', f.webkitRelativePath || f.name);
                });
                fd.append('directory_id', targetDirId);
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
                    directory_id: targetDirId,
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
            if (! this.clipboard || ! targetDirId || ! this.canUploadHere || this.pasting || this.operationOverlay.show) return;
            const cb = this.clipboard;
            this.pasting = true;

            if (cb.type === 'asset') {
                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.paste-file-started')" });
                this.$axios.post("{{ route('admin.dam.explorer.copy.asset') }}", {
                    asset_id:            cb.id,
                    target_directory_id: targetDirId,
                }).then(() => {
                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.paste-done')" });
                    this.fetch();
                }).catch(err => {
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message });
                }).finally(() => { this.pasting = false; });
            } else {
                const dirName = cb.name ?? '';
                this.operationOverlay = { show: true, label: `@lang('dam::app.admin.dam.index.copy.directory')`.replace(':name', dirName) };
                this.$axios.post("{{ route('admin.dam.explorer.copy.directory') }}", {
                    directory_id:        cb.id,
                    target_directory_id: targetDirId,
                }).then(() => {
                    let attempts = 0;
                    const poll = () => {
                        if (++attempts > 30) {
                            this.operationOverlay = { show: false, label: '' };
                            return;
                        }
                        this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'copy_directory'))
                            .then(({ data: d }) => {
                                if (d.status === 'completed') {
                                    this.operationOverlay = { show: false, label: '' };
                                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.paste-done')" });
                                    this.fetch();
                                } else if (d.status === 'failed') {
                                    this.operationOverlay = { show: false, label: '' };
                                    this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                } else { setTimeout(poll, 2000); }
                            }).catch(() => { setTimeout(poll, 2000); });
                    };
                    setTimeout(poll, 1000);
                }).catch(err => {
                    this.operationOverlay = { show: false, label: '' };
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message });
                }).finally(() => { this.pasting = false; });
            }
        },
    },
});
</script>
@endpush
@endonce
