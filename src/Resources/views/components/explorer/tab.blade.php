@once('v-dam-tab')
@include('dam::components.explorer.folder-picker')
@push('scripts')
<script type="text/x-template" id="v-dam-tab-template">
    <div class="relative flex flex-col flex-1 min-h-0 overflow-hidden p-4 gap-3">

        {{-- Row 1: sidebar toggle + back/forward + breadcrumb + actions --}}
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Sidebar collapse toggle — only when at least one sidebar component is enabled --}}
            @if (config('dam.explorer.show_tree') || config('dam.explorer.bookmarks_enabled'))
            <button
                type="button"
                data-sidebar-toggle
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
            {{-- Star glows violet when the current directory is bookmarked; acts as an add/remove toggle. --}}
            <button
                v-if="currentDirId"
                type="button"
                data-bookmark-toggle
                :data-bookmarked="currentDirBookmarked"
                class="w-8 h-8 flex items-center justify-center rounded-md transition-colors cursor-pointer shrink-0"
                :class="currentDirBookmarked
                    ? 'text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30'
                    : 'text-zinc-600 dark:text-white hover:bg-gray-100 dark:hover:bg-cherry-800'"
                :title="bookmarkTitle"
                @click="toggleBookmarkCurrentDir"
            >
                <i class="icon-star text-xl"></i>
            </button>
            @endif

            @if (bouncer()->hasPermission('dam.asset.upload'))
            <input
                type="file" multiple name="files[]"
                :id="`explorer-upload-${tabId}`" class="hidden"
                @change="onFileChange"
            />
            <input
                type="file" webkitdirectory multiple name="folder_files[]"
                :id="`explorer-folder-upload-${tabId}`" class="hidden"
                @change="onFolderChange"
            />
            @endif

            {{-- Grid / List view toggle --}}
            <v-dam-explorer-view-toggle :model-value="viewMode" @update:model-value="setView($event)"></v-dam-explorer-view-toggle>

            @if (bouncer()->hasPermission('dam.asset.upload') || bouncer()->hasPermission('dam.directory.store'))
            <div v-if="canUploadHere" class="shrink-0">
                <x-admin::dropdown position="bottom-right">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="primary-button h-8 !py-0 flex items-center gap-x-1.5 whitespace-nowrap"
                        >
                            <span class="text-xl leading-none">+</span>
                            @lang('dam::app.admin.dam.index.new')
                        </button>
                    </x-slot:toggle>

                    <x-slot:menu class="shadow-md !p-0 z-10">
                        @if (bouncer()->hasPermission('dam.asset.upload'))
                        <li
                            class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                            @click="triggerNewUpload"
                        >
                            <i class="icon-dam-upload text-base"></i>
                            @lang('dam::app.admin.dam.index.directory.actions.upload-files')
                        </li>
                        <li
                            class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                            @click="triggerNewFolderUpload"
                        >
                            <i class="icon-dam-add-folder text-base"></i>
                            @lang('dam::app.admin.explorer.context.folder-upload')
                        </li>
                        @endif
                        @if (bouncer()->hasPermission('dam.directory.store'))
                        <li
                            class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                            @click="createDirHere"
                        >
                            <i class="icon-dam-add-folder text-base"></i>
                            @lang('dam::app.admin.dam.index.directory.actions.add-directory')
                        </li>
                        @endif
                        <li
                            v-if="clipboard"
                            class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300 border-t border-gray-100 dark:border-cherry-700"
                            @click="pasteHere"
                        >
                            <i class="icon-copy text-base"></i>
                            @lang('dam::app.admin.explorer.context.paste')
                        </li>
                    </x-slot:menu>
                </x-admin::dropdown>
            </div>
            @endif
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

        {{-- Another tab has a selection in this folder — offer to select the same items here --}}
        <div
            v-if="showForeignSelectionOffer"
            class="flex items-center gap-2 px-3 py-1.5 text-xs bg-violet-50 dark:bg-violet-900/30 border border-violet-200 dark:border-violet-700 rounded-lg text-violet-700 dark:text-violet-300"
        >
            <span class="flex-1">@{{ "@lang('dam::app.admin.explorer.foreign-selection.notice')".replace(':count', foreignSelectionCount) }}</span>
            <button
                type="button"
                class="shrink-0 font-semibold underline hover:no-underline"
                @click="adoptForeignSelection"
            >@lang('dam::app.admin.explorer.foreign-selection.action')</button>
        </div>

        {{-- Content area — v-dam-drop-upload handles OS file/folder drops and is
             the single upload manager; toolbar uploads enqueue into it via $refs. --}}
        <v-dam-drop-upload
            ref="dropUpload"
            class="flex-1 overflow-y-auto flex flex-col"
            :current-directory="currentDirId ? { id: currentDirId } : null"
            :can-upload="canUploadHere"
            @refresh-datagrid="fetch()"
        >
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

        {{-- Operation progress bar (matches drag-and-drop panel style) --}}
        <div
            v-if="operationOverlay.show"
            class="fixed bottom-4 ltr:right-8 rtl:left-8 z-[10005] w-[460px] rounded-xl shadow-2xl overflow-hidden border border-gray-300 dark:border-cherry-600"
            role="status"
            aria-live="polite"
        >
            <!-- Violet header — same as drag-drop panel -->
            <div class="flex items-center justify-between px-4 py-2.5 bg-violet-600 dark:bg-violet-700">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <svg class="animate-spin h-3.5 w-3.5 text-white/80 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-white truncate" v-text="operationOverlay.label"></span>
                </div>
                <span
                    v-if="operationOverlay.fileCount != null"
                    class="text-xs text-white/70 flex-shrink-0 ml-2"
                    v-text="'@lang('dam::app.admin.explorer.mass-actions.total-files')'.replace(':count', operationOverlay.fileCount)"
                ></span>
            </div>

            <!-- Progress footer — same style as drag-drop footer -->
            <div v-if="operationOverlay.progress != null" class="px-4 py-2.5 bg-white dark:bg-cherry-800 border-t border-gray-100 dark:border-cherry-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                    <span>@lang('dam::app.admin.explorer.mass-actions.progress')</span>
                    <span v-text="operationOverlay.progress + '%'"></span>
                </div>
                <div class="h-1.5 bg-gray-200 dark:bg-cherry-600 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-violet-600 dark:bg-violet-500 rounded-full transition-all duration-300"
                        :style="{ width: operationOverlay.progress + '%' }"
                    ></div>
                </div>
            </div>
        </div>
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
            uploadTargetDirId:  null,
            localAccessibleIds: [...(this.accessibleIds || [])],
            debounce:        null,
            navHistory:   [],
            navIdx:       -1,
            dialog: { on: false, type: null, value: '', loading: false, extra: null },
            clipboard:          null,
            pasting:            false,
            operationOverlay:   { show: false, label: '', progress: null, fileCount: null },
            selection:          { ids: [], mode: 'none' },
            foreignSelections:  {},
            folderPicker:       { open: false, mode: null },
            ctxTarget:          null,
            sidebarVisible:     storedSidebar !== null ? storedSidebar !== 'false' : true,
            bookmarkedDirIds:   [],
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
        // Another tab has a selection in the directory shown here — offer to select the
        // same items in this tab (shown only while this tab has no selection of its own).
        foreignSelectionCount() {
            const f = this.currentDirId != null ? this.foreignSelections[this.currentDirId] : null;
            return f ? f.ids.length : 0;
        },
        showForeignSelectionOffer() {
            return this.foreignSelectionCount > 0 && this.selection.ids.length === 0;
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
        currentDirBookmarked() {
            if (! this.currentDirId) return false;
            return this.bookmarkedDirIds.map(Number).includes(Number(this.currentDirId));
        },
        bookmarkTitle() {
            return this.currentDirBookmarked
                ? "@lang('dam::app.admin.explorer.bookmarks.remove')"
                : "@lang('dam::app.admin.explorer.context.bookmark')";
        },
    },

    watch: {
        // Ask other tabs whether they have a selection in the directory this tab just opened.
        currentDirId(id) {
            if (id != null) this.$emitter.emit('dam:selection-query', { directoryId: id, requesterId: this.tabId });
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

        // Keep the toolbar star's bookmarked-state in sync with the bookmarks
        // panel (the source of truth), whichever way a bookmark is added/removed.
        this._onBookmarksChanged = (ids) => { this.bookmarkedDirIds = ids ?? []; };
        this.$emitter.on('dam:bookmarks-changed', this._onBookmarksChanged);

        this.$emitter.on(`dam:explorer-ctx-refresh:${this.tabId}`, () => this.fetch());

        // Track other tabs' selections in the same directory so this tab can offer to
        // select the same items too.
        this._onForeignSelectionActive = ({ tabId, directoryId, ids }) => {
            if (tabId === this.tabId) return;
            this.foreignSelections[directoryId] = { tabId, ids: ids ?? [] };
        };
        this._onForeignSelectionCleared = ({ tabId, directoryId }) => {
            if (this.foreignSelections[directoryId]?.tabId === tabId) delete this.foreignSelections[directoryId];
        };
        this._onSelectionQuery = ({ directoryId, requesterId }) => {
            if (requesterId === this.tabId) return;
            if (this._heldSelectionDir === directoryId && this.selection.ids.length) {
                this.$emitter.emit('dam:selection-active', { tabId: this.tabId, directoryId, ids: this.selection.ids });
            }
        };
        this.$emitter.on('dam:selection-active', this._onForeignSelectionActive);
        this.$emitter.on('dam:selection-cleared', this._onForeignSelectionCleared);
        this.$emitter.on('dam:selection-query', this._onSelectionQuery);

        // Shared tag modal finished assigning tags to assets selected in THIS tab —
        // clear the selection and refresh so the change is reflected.
        this.$emitter.on('dam:tag-assign:done', ({ context } = {}) => {
            if (context !== `explorer:${this.tabId}`) return;
            this.clearSelection();
            this.fetch();
        });

        this.$emitter.on(`dam:operation-overlay:show:${this.tabId}`, ({ label, progress = null, fileCount = null }) => {
            this.operationOverlay = { show: true, label: label ?? '', progress, fileCount };
        });
        this.$emitter.on(`dam:operation-overlay:progress:${this.tabId}`, ({ progress }) => {
            this.operationOverlay = { ...this.operationOverlay, progress };
        });
        this.$emitter.on(`dam:operation-overlay:hide:${this.tabId}`, () => {
            this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
        });
        this.$emitter.on(`dam:dir-deleted:${this.tabId}`, () => {
            this.navHistory = this.navHistory.slice(0, this.navIdx + 1);
        });
        this.$emitter.on('dam:directory-mutated', () => this.fetch());

        // Tree "Upload files" → route the pre-built FormData through the unified
        // upload manager so it shows the same progress panel as every other upload.
        this.$emitter.on('dam:upload-files', (formData) => {
            const files = formData.getAll('files[]');
            if (! files.length) return;
            const targetDirId = formData.get('directory_id') ?? this.currentDirId;
            this.$refs.dropUpload?.enqueueUpload({
                items: files.map(f => ({ file: f, relativePath: f.name, preserveRoot: false })),
                folderPaths: [],
                targetDirId,
            });
        });

        // Refresh the listing whenever the upload manager finishes a batch that
        // targeted this tab's current directory.
        this.$emitter.on('dam:uploads-refresh', ({ directoryId } = {}) => {
            if (! directoryId || Number(directoryId) === Number(this.currentDirId)) this.fetch();
        });

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
            // The watch only fires on change, so discover existing selections for the initial dir.
            this.$emitter.emit('dam:selection-query', { directoryId: this.currentDirId, requesterId: this.tabId });
        } else {
            this.loadRoot();
        }
    },

    beforeUnmount() {
        if (this._onSidebarVisibility) this.$emitter.off('dam:sidebar-visibility-changed', this._onSidebarVisibility);
        if (this._onBookmarksChanged) this.$emitter.off('dam:bookmarks-changed', this._onBookmarksChanged);

        // Tell other tabs this tab's selection is gone before it unmounts.
        if (this._heldSelectionDir != null) {
            this.$emitter.emit('dam:selection-cleared', { tabId: this.tabId, directoryId: this._heldSelectionDir });
        }
        this.$emitter.off('dam:selection-active', this._onForeignSelectionActive);
        this.$emitter.off('dam:selection-cleared', this._onForeignSelectionCleared);
        this.$emitter.off('dam:selection-query', this._onSelectionQuery);
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

        // Select the same items another tab has selected in this directory.
        adoptForeignSelection() {
            const f = this.currentDirId != null ? this.foreignSelections[this.currentDirId] : null;
            if (! f) return;
            this.selection.ids = f.ids.map(i => ({ ...i }));
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
            this.broadcastSelection();
        },

        // Broadcast this tab's selection (with its items) so other tabs viewing the same
        // directory can offer to select the same items — or drop the offer when cleared.
        broadcastSelection() {
            if (this.selection.ids.length && this.currentDirId != null) {
                this._heldSelectionDir = this.currentDirId;
                this.$emitter.emit('dam:selection-active', { tabId: this.tabId, directoryId: this.currentDirId, ids: this.selection.ids });
            } else if (this._heldSelectionDir != null) {
                this.$emitter.emit('dam:selection-cleared', { tabId: this.tabId, directoryId: this._heldSelectionDir });
                this._heldSelectionDir = null;
            }
        },

        openAssignTagsModal() {
            // Tag the explicitly selected assets AND every asset inside the selected folders
            // (recursively, resolved server-side) — so picking folders tags their contents too.
            const assetIds     = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
            const directoryIds = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);

            if (! assetIds.length && ! directoryIds.length) {
                this.$emitter.emit('add-flash', {
                    type: 'warning',
                    message: "@lang('dam::app.admin.dam.tag.mass-action.no-items')",
                });
                return;
            }

            this.$emitter.emit('dam:open-tag-assign-modal', {
                assetIds,
                directoryIds,
                context: `explorer:${this.tabId}`,
            });
        },

        performMassDelete() {
            const count = this.selection.ids.length;

            this.$emitter.emit('open-delete-modal', {
                message: "@lang('dam::app.admin.explorer.mass-actions.confirm')".replace(':count', count),
                agree: async () => {
                    const assetIds = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
                    const dirIds   = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);
                    const sourceName = this.currentFolderName();

                    this.$emitter.emit(`dam:operation-overlay:show:${this.tabId}`, {
                        label: "@lang('dam::app.admin.explorer.mass-actions.deleting')".replace(':count', count),
                    });

                    if (assetIds.length) {
                        try {
                            await this.$axios.post('{{ route("admin.dam.assets.mass_delete") }}', { indices: assetIds });
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: "@lang('dam::app.admin.explorer.mass-actions.deleted-assets')"
                                    .replace(':count', assetIds.length)
                                    .replace(':source', sourceName),
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
                                                this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.mass-actions.deleted-dirs')".replace(':count', dirIds.length).replace(':source', sourceName) });
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

        onFolderPickerPicked(payload) {
            // Picker emits { id, name }; tolerate a bare id for safety.
            const targetDirId   = payload?.id ?? payload;
            const targetDirName = payload?.name ?? '';
            const mode = this.folderPicker.mode;
            this.folderPicker = { open: false, mode: null };
            if (mode === 'move') {
                this.performMassMove(targetDirId, targetDirName);
            } else {
                this.performMassCopy(targetDirId, targetDirName);
            }
        },

        // Name of the folder currently open in this tab — used as the "source"
        // in move/copy/delete success alerts.
        currentFolderName() {
            return this.breadcrumb[this.breadcrumb.length - 1]?.name ?? 'Root';
        },

        performMassMove(targetDirId, targetDirName = '') {
            const assetIds = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
            const dirIds   = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);
            const sourceName = this.currentFolderName();

            // Show bar immediately with 0% progress so bar is visible from the start
            this.operationOverlay = { show: true, label: "@lang('dam::app.admin.explorer.mass-actions.moving')", progress: 0, fileCount: null };

            // Fetch actual file count in background; update bar when ready
            this.$axios.post('{{ route("admin.dam.explorer.count_items") }}', {
                asset_ids: assetIds, directory_ids: dirIds,
            }).then(({ data }) => {
                this.operationOverlay = { ...this.operationOverlay, fileCount: data.file_count ?? (assetIds.length + dirIds.length) };
            }).catch(() => {
                this.operationOverlay = { ...this.operationOverlay, fileCount: assetIds.length + dirIds.length };
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
                            if (++attempts > 150) {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: "@lang('dam::app.admin.explorer.mass-actions.still-running')",
                                });
                                resolve();
                                return;
                            }
                            this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'mass_move'))
                                .then(({ data: d }) => {
                                    if (d.status === 'completed') {
                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: "@lang('dam::app.admin.explorer.mass-actions.move-done')"
                                                .replace(':count', this.operationOverlay.fileCount ?? (assetIds.length + dirIds.length))
                                                .replace(':source', sourceName)
                                                .replace(':destination', targetDirName),
                                        });
                                        dirIds.forEach(id => this.$emitter.emit('dam:directory-deleted', { id }));
                                        this.$emitter.emit('dam:tree-reload');
                                        resolve();
                                    } else if (d.status === 'failed') {
                                        this.$emitter.emit('add-flash', { type: 'error', message: d.message || "@lang('dam::app.admin.dam.index.directory.error-operation')" });
                                        resolve();
                                    } else {
                                        if (d.progress != null) {
                                            this.$emitter.emit(`dam:operation-overlay:progress:${this.tabId}`, { progress: d.progress });
                                        }
                                        setTimeout(poll, 2000);
                                    }
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

        performMassCopy(targetDirId, targetDirName = '') {
            const assetIds = this.selection.ids.filter(i => i.type === 'asset').map(i => i.id);
            const dirIds   = this.selection.ids.filter(i => i.type === 'directory').map(i => i.id);
            const sourceName = this.currentFolderName();

            // Show bar immediately with 0% progress so bar is visible from the start
            this.operationOverlay = { show: true, label: "@lang('dam::app.admin.explorer.mass-actions.copying')", progress: 0, fileCount: null };

            // Fetch actual file count in background; update bar when ready
            this.$axios.post('{{ route("admin.dam.explorer.count_items") }}', {
                asset_ids: assetIds, directory_ids: dirIds,
            }).then(({ data }) => {
                this.operationOverlay = { ...this.operationOverlay, fileCount: data.file_count ?? (assetIds.length + dirIds.length) };
            }).catch(() => {
                this.operationOverlay = { ...this.operationOverlay, fileCount: assetIds.length + dirIds.length };
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
                            if (++attempts > 150) {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: "@lang('dam::app.admin.explorer.mass-actions.still-running')",
                                });
                                resolve();
                                return;
                            }
                            this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'mass_copy'))
                                .then(({ data: d }) => {
                                    if (d.status === 'completed') {
                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: "@lang('dam::app.admin.explorer.mass-actions.copy-done')"
                                                .replace(':count', this.operationOverlay.fileCount ?? (assetIds.length + dirIds.length))
                                                .replace(':source', sourceName)
                                                .replace(':destination', targetDirName),
                                        });
                                        this.$emitter.emit('dam:tree-reload');
                                        resolve();
                                    } else if (d.status === 'failed') {
                                        this.$emitter.emit('add-flash', { type: 'error', message: d.message || "@lang('dam::app.admin.dam.index.directory.error-operation')" });
                                        resolve();
                                    } else {
                                        if (d.progress != null) {
                                            this.$emitter.emit(`dam:operation-overlay:progress:${this.tabId}`, { progress: d.progress });
                                        }
                                        setTimeout(poll, 2000);
                                    }
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
            this.broadcastSelection();
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

                this.computeSelectionMode();

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

        toggleBookmarkCurrentDir() {
            if (! this.currentDirId) return;
            if (this.currentDirBookmarked) {
                this.$emitter.emit('dam:remove-bookmark', { directoryId: this.currentDirId });
                return;
            }
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
                    this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.action-completed')" });
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
            if (this.searchInput.length > 0 && this.searchInput.length < 2) return;
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

        {{-- Changing the page size keeps the current selection — the same rows are
             still there, just paginated differently. --}}
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
            if (! files?.length) { e.target.value = ''; return; }
            const targetDirId = this.uploadTargetDirId ?? this.currentDirId;
            this.uploadTargetDirId = null;
            this.$refs.dropUpload?.enqueueUpload({
                items: Array.from(files).map(f => ({ file: f, relativePath: f.name, preserveRoot: false })),
                folderPaths: [],
                targetDirId,
            });
            e.target.value = '';
        },

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
                this.operationOverlay = { show: true, label: `@lang('dam::app.admin.dam.index.move.directory')`.replace(':name', folderName), progress: null, fileCount: null };
                this.$axios.post("{{ route('admin.dam.directory.moved') }}", {
                    move_item_id:  payload.id,
                    new_parent_id: targetDir.id,
                }).then(() => {
                    let attempts = 0;
                    const poll = () => {
                        if (++attempts > 30) {
                            this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                            return;
                        }
                        this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'move_directory_structure'))
                            .then(({ data: d }) => {
                                if (d.status === 'completed') {
                                    this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.move-done')" });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                    if (originTabId && originTabId !== tabId) {
                                        this.$emitter.emit(`dam:explorer-ctx-refresh:${originTabId}`);
                                    }
                                } else if (d.status === 'failed') {
                                    this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
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
                    this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" });
                });
            }
        },

        onFolderChange(e) {
            const files = Array.from(e.target.files ?? []);
            if (! files.length) { e.target.value = ''; return; }
            const targetDirId = this.uploadTargetDirId ?? this.currentDirId;
            this.uploadTargetDirId = null;

            // Every directory level along each path becomes a folder to create;
            // files with content become upload jobs. Both flow through the manager.
            const folderPaths = new Set();
            files.forEach(f => {
                const rel  = f.webkitRelativePath || f.name;
                const segs = rel.split('/');
                for (let i = 1; i < segs.length; i++) folderPaths.add(segs.slice(0, i).join('/'));
            });

            this.$refs.dropUpload?.enqueueUpload({
                items: files.filter(f => f.size > 0)
                            .map(f => ({ file: f, relativePath: f.webkitRelativePath || f.name, preserveRoot: true })),
                folderPaths: [...folderPaths],
                targetDirId,
            });
            e.target.value = '';
        },

        triggerNewUpload() {
            this.uploadTargetDirId = this.currentDirId;
            this.$nextTick(() => document.getElementById(`explorer-upload-${this.tabId}`)?.click());
        },
        triggerNewFolderUpload() {
            this.uploadTargetDirId = this.currentDirId;
            this.$nextTick(() => document.getElementById(`explorer-folder-upload-${this.tabId}`)?.click());
        },
        createDirHere() {
            if (! this.currentDirId) return;
            this.$emitter.emit('dam:open-create-dir', { item: { id: this.currentDirId } });
        },
        pasteHere() {
            this.executePaste(this.currentDirId);
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
                this.operationOverlay = { show: true, label: `@lang('dam::app.admin.dam.index.copy.directory')`.replace(':name', dirName), progress: null, fileCount: null };
                this.$axios.post("{{ route('admin.dam.explorer.copy.directory') }}", {
                    directory_id:        cb.id,
                    target_directory_id: targetDirId,
                }).then(() => {
                    let attempts = 0;
                    const poll = () => {
                        if (++attempts > 30) {
                            this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                            return;
                        }
                        this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'copy_directory'))
                            .then(({ data: d }) => {
                                if (d.status === 'completed') {
                                    this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                                    this.$emitter.emit('add-flash', { type: 'success', message: "@lang('dam::app.admin.explorer.context.paste-done')" });
                                    this.fetch();
                                } else if (d.status === 'failed') {
                                    this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                                    this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                } else { setTimeout(poll, 2000); }
                            }).catch(() => { setTimeout(poll, 2000); });
                    };
                    setTimeout(poll, 1000);
                }).catch(err => {
                    this.operationOverlay = { show: false, label: '', progress: null, fileCount: null };
                    this.$emitter.emit('add-flash', { type: 'error', message: err?.response?.data?.message });
                }).finally(() => { this.pasting = false; });
            }
        },
    },
});
</script>
@endpush
@endonce
