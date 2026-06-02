{{-- Ensure v-dam-drop-upload is registered before v-dam-tab mounts --}}
<x-dam::asset.drop-upload />

<v-dam-explorer
    :acl-bypass="{{ dam_acl_bypass() ? 'true' : 'false' }}"
    :accessible-ids='@json(dam_accessible_dir_ids())'
></v-dam-explorer>

@once('explorer-styles')
@push('styles')
<style>
.explorer-folder-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
@media (min-width: 640px)  { .explorer-asset-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 1024px) { .explorer-asset-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (min-width: 1280px) { .explorer-asset-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
/* Grid card hover-action buttons: non-interactive until the card is hovered */
.explorer-asset-actions button { pointer-events: none; }
.group:hover .explorer-asset-actions button { pointer-events: auto; }
</style>
@endpush
@endonce

{{-- v-dam-bookmarks is defined in bookmarks.blade.php — included below via asset/index.blade.php --}}

@once('v-dam-explorer')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-template">
    <div class="flex flex-col flex-1 min-h-0 overflow-hidden">

        {{-- Tab bar --}}
        <div
            class="flex items-end gap-0 bg-gray-100 dark:bg-cherry-800 border-b border-gray-200 dark:border-cherry-700 px-2 pt-1.5 overflow-x-auto overflow-y-hidden flex-shrink-0"
            style="scrollbar-width: thin;"
        >
            <div
                v-for="tab in tabs"
                :key="tab.id"
                class="flex items-center gap-1.5 px-3 py-1.5 min-w-[110px] max-w-[180px] flex-shrink-0 rounded-t-md border border-b-0 text-sm cursor-pointer select-none transition-colors"
                :class="tab.id === activeTabId
                    ? 'bg-white dark:bg-cherry-900 border-gray-200 dark:border-cherry-700 text-gray-800 dark:text-white font-semibold z-10 -mb-px'
                    : 'bg-transparent border-transparent text-gray-500 hover:bg-white/60 dark:hover:bg-cherry-800 dark:text-white'"
                @click="setActive(tab.id)"
            >
                <i class="icon-dam-folder text-base shrink-0" :class="tab.id === activeTabId ? 'text-violet-500' : 'text-gray-400'"></i>
                <span class="truncate flex-1 text-sm">@{{ tab.label }}</span>
                <span
                    v-if="tabs.length > 1"
                    class="tab-close-btn shrink-0 w-4 h-4 flex items-center justify-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 text-base leading-none"
                    @click.stop="closeTab(tab.id)"
                    :title="'@lang('dam::app.admin.explorer.tab.close')'"
                >×</span>
            </div>

            <button
                v-if="tabs.length < 8"
                type="button"
                class="w-7 h-7 mb-0.5 ml-1 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-200 dark:hover:bg-cherry-800 hover:text-gray-700 text-lg font-light shrink-0"
                @click="newTab()"
                :title="'@lang('dam::app.admin.explorer.tab.new')'"
            >+</button>
        </div>

        {{-- Tab content panes — all mounted, only active is shown --}}
        {{-- NOTE: v-dam-tab is used directly (not x-dam::explorer.tab) because --}}
        {{-- this is inside a <script type="text/x-template"> — Blade cannot resolve --}}
        {{-- Blade component props that reference Vue runtime variables (tab.id etc.). --}}
        <template v-for="tab in tabs" :key="tab.id">
            <div v-show="tab.id === activeTabId" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <v-dam-tab
                    :tab-id="tab.id"
                    :initial-directory-id="tab.directoryId"
                    :initial-search="tab.search"
                    :initial-view-mode="tab.viewMode"
                    :initial-page="tab.page"
                    :initial-per-page="tab.perPage"
                    :acl-bypass="aclBypass"
                    :accessible-ids="accessibleIds"
                    @tab-state-change="onStateChange(tab.id, $event)"
                    @tab-label-change="onLabelChange(tab.id, $event)"
                ></v-dam-tab>
            </div>
        </template>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer', {
    template: '#v-dam-explorer-template',

    props: {
        aclBypass:     { type: Boolean, default: false },
        accessibleIds: { type: Array, default: () => [] },
    },

    data() {
        return { tabs: [], activeTabId: null, _kbHandler: null };
    },

    mounted() {
        // Read the return-directory set by the asset edit page (stored in
        // sessionStorage to avoid polluting the URL). Consumed once and cleared.
        let dirId = null;
        try { dirId = sessionStorage.getItem('dam_return_dir'); } catch {}
        this.restore(dirId ? Number(dirId) : null);

        this.$emitter.on('dam:explorer-navigate', ({ directoryId, name, source }) => {
            if (source === 'bookmark') {
                this.$emitter.emit(`dam:tab-navigate:${this.activeTabId}`, { directoryId, name });
            }
        });

        this.$emitter.on('dam:suppress-nav-once', () => {
            this._suppressNextNav = true;
        });

        this.$emitter.on('current-directory', (item) => {
            if (this._suppressNextNav) { this._suppressNextNav = false; return; }
            if (item && item.id != null) {
                this.$emitter.emit(`dam:tab-navigate:${this.activeTabId}`, { directoryId: item.id, name: item.name, fromTree: true });
            }
        });

        this.$emitter.on('dam:open-in-new-tab', ({ directoryId, name }) => {
            this.newTab(directoryId, name ?? '…');
        });

        this._kbHandler = (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'c' && ! e.target.matches('input, textarea')) {
                e.preventDefault();
                this.$emitter.emit(`dam:explorer-kb-copy:${this.activeTabId}`);
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'v' && ! e.target.matches('input, textarea')) {
                e.preventDefault();
                this.$emitter.emit(`dam:explorer-kb-paste:${this.activeTabId}`);
            }
        };
        window.addEventListener('keydown', this._kbHandler);
    },

    beforeUnmount() {
        if (this._kbHandler) window.removeEventListener('keydown', this._kbHandler);
    },

    methods: {
        uid() {
            return (crypto.randomUUID ?? (() => Math.random().toString(36).slice(2)))();
        },

        makeTab(directoryId = null, label = '…') {
            return { id: this.uid(), directoryId, label, search: '', viewMode: 'grid', page: 1, perPage: 50 };
        },

        restore(initialDirId = null) {
            this.newTab(initialDirId);
        },

        newTab(directoryId = null, label = '…') {
            if (this.tabs.length >= 8) return;
            const tab = this.makeTab(directoryId, label);
            this.tabs.push(tab);
            this.activeTabId = tab.id;
        },

        closeTab(id) {
            if (this.tabs.length <= 1) return;
            const idx = this.tabs.findIndex(t => t.id === id);
            this.tabs = this.tabs.filter(t => t.id !== id);
            if (this.activeTabId === id) this.activeTabId = this.tabs[Math.max(0, idx - 1)].id;
        },

        setActive(id) {
            this.activeTabId = id;
        },

        onStateChange(id, state) {
            const tab = this.tabs.find(t => t.id === id);
            if (tab) Object.assign(tab, state);
        },

        onLabelChange(id, label) {
            const tab = this.tabs.find(t => t.id === id);
            if (tab) tab.label = label;
        },
    },
});
</script>
@endpush
@endonce

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
        </div>

        {{-- Row 2: search + filters button + view toggle --}}
        <div class="flex items-center gap-3">
            <div class="flex-1 flex items-center gap-2 border border-gray-300 dark:border-cherry-600 rounded-lg px-3 py-2 bg-white dark:bg-cherry-900">
                <i class="icon-search text-gray-400 text-sm"></i>
                <input
                    type="text"
                    class="flex-1 bg-transparent text-sm text-gray-700 dark:text-gray-200 outline-none placeholder-gray-400"
                    :placeholder="'@lang('dam::app.admin.explorer.search.placeholder')'"
                    v-model="searchInput"
                    @input="onSearch"
                />
                <button v-if="searchInput" @click="clearSearch" class="text-gray-400 hover:text-gray-600 text-base leading-none">×</button>
            </div>

            {{-- Filters drawer (same as DAM's filter sidebar) --}}
            <x-admin::drawer width="350px" ref="explorerFilterDrawer">
                <x-slot:toggle>
                    <div>
                        <div
                            class="relative inline-flex w-full max-w-max ltr:pl-3 rtl:pr-3 ltr:pr-5 rtl:pl-5 cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-1 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-2"
                            :class="{'[&>*]:text-violet-700 [&>*]:dark:text-white': activeFilterCount > 0}"
                        >
                            <span class="icon-filter text-2xl"></span>

                            <span>
                                @lang('admin::app.components.datagrid.toolbar.filter.title')
                            </span>

                            <span
                                class="icon-dot absolute top-0.5 right-1 text-2xl font-bold"
                                v-if="activeFilterCount > 0"
                            ></span>
                        </div>
                    </div>
                </x-slot:toggle>

                <x-slot:header>
                    <div class="flex justify-between items-center p-3">
                        <p class="text-base font-semibold dark:text-white text-gray-800">
                            @lang('admin::app.components.datagrid.filters.title')
                        </p>
                    </div>
                </x-slot:header>

                <x-slot:content class="!p-5">
                    <x-dam::datagrid.filters />

                    <div
                        class="primary-button block text-center mt-4"
                        @click="runFilters()"
                    >
                        @lang('admin::app.components.datagrid.filters.save')
                    </div>
                </x-slot:content>
            </x-admin::drawer>

            <v-dam-explorer-pager
                v-if="meta"
                :current-page="meta.current_page ?? 1"
                :last-page="meta.last_page ?? 1"
                :per-page="perPage"
                @page-change="onPage"
                @per-page-change="onPerPage"
            ></v-dam-explorer-pager>

            <div class="flex border border-gray-300 dark:border-cherry-600 rounded-lg overflow-hidden bg-white dark:bg-cherry-900 shrink-0">
                <button
                    type="button"
                    class="flex items-center px-2.5 py-2 transition-colors"
                    :class="viewMode==='grid' ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-cherry-800'"
                    @click="setView('grid')"
                    data-view="grid"
                >
                    <svg width="13" height="13" viewBox="0 0 16 16" :fill="viewMode==='grid'?'#6d28d9':'#9ca3af'"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
                </button>
                <button
                    type="button"
                    class="flex items-center px-2.5 py-2 border-l border-gray-200 dark:border-cherry-700 transition-colors"
                    :class="viewMode==='list' ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-cherry-800'"
                    @click="setView('list')"
                    data-view="list"
                >
                    <svg width="13" height="13" viewBox="0 0 16 16" :fill="viewMode==='list'?'#6d28d9':'#9ca3af'"><rect x="1" y="2" width="14" height="2.5" rx="1"/><rect x="1" y="6.75" width="14" height="2.5" rx="1"/><rect x="1" y="11.5" width="14" height="2.5" rx="1"/></svg>
                </button>
            </div>
        </div>

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
                :directories="dirs"
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
                :directories="dirs"
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
            @if (config('dam.explorer.bookmarks_enabled'))
            <button class="ctx-item" @click="doBookmark">
                <span class="text-sm">🔖</span>
                @lang('dam::app.admin.explorer.context.bookmark')
            </button>
            @endif
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
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.dam.index.directory.actions.delete')" }); this.ctxRefresh(); this.$emitter.emit('dam:tree-reload'); })
                        .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
                },
            });
        },
        renameDir()   { this.$emitter.emit('dam:open-rename-dir', { item: this.item }); this.close(); },
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
        createHere()       { this.close(); this.$emitter.emit('dam:open-create-dir', { item: this.item }); },
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
                        .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
                },
            });
        },
    },
});
</script>
@endpush
@endonce

@once('v-dam-explorer-pager')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-pager-template">
    <div class="flex items-center gap-x-2">
        <x-admin::dropdown>
            <x-slot:toggle>
                <button
                    type="button"
                    class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                >
                    <span v-text="perPage"></span>
                    <span class="icon-chevron-down text-2xl"></span>
                </button>
            </x-slot>
            <x-slot:menu>
                <x-admin::dropdown.menu.item v-for="opt in [50, 100, 150, 200, 250]" v-text="opt" @click="$emit('per-page-change', opt)"></x-admin::dropdown.menu.item>
            </x-slot>
        </x-admin::dropdown>

        <p class="whitespace-nowrap text-gray-600 dark:text-gray-300 max-sm:hidden">
            @lang('admin::app.components.datagrid.toolbar.per-page')
        </p>

        <input
            type="text"
            class="inline-flex min-h-[38px] max-w-[60px] appearance-none items-center justify-center gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-3 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
            :value="currentPage"
            @change="$emit('page-change', Math.max(1, Math.min(lastPage, parseInt($event.target.value) || 1)))"
        >

        <div class="flex items-center gap-1 whitespace-nowrap text-gray-600 dark:text-gray-300">
            <span>@lang('admin::app.components.datagrid.toolbar.of')</span>
            <span v-text="lastPage"></span>
        </div>

        <div class="flex items-center gap-1">
            <div
                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                :class="{ 'opacity-40 pointer-events-none': currentPage <= 1 }"
                @click="currentPage > 1 && $emit('page-change', currentPage - 1)"
            >
                <span class="icon-chevron-left text-2xl"></span>
            </div>
            <div
                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                :class="{ 'opacity-40 pointer-events-none': currentPage >= lastPage }"
                @click="currentPage < lastPage && $emit('page-change', currentPage + 1)"
            >
                <span class="icon-chevron-right text-2xl"></span>
            </div>
        </div>
    </div>
</script>
<script type="module">
app.component('v-dam-explorer-pager', {
    template: '#v-dam-explorer-pager-template',
    emits: ['page-change','per-page-change'],
    props: { currentPage: Number, lastPage: Number, perPage: Number },
});
</script>
@endpush
@endonce
