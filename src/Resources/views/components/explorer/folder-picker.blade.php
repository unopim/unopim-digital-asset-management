@once('v-dam-folder-picker')
@push('scripts')
<script type="text/x-template" id="v-dam-folder-picker-template">
    <div v-if="open" class="fixed inset-0 z-[10010] flex items-center justify-center" data-folder-picker>
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>

        {{-- Modal --}}
        <div class="relative bg-white dark:bg-cherry-900 rounded-xl shadow-2xl w-[360px] h-[520px] flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b dark:border-cherry-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white truncate">
                    @lang('dam::app.admin.explorer.mass-actions.pick-dest')
                </h3>
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Reuse the same grid/list toggle as the assets page --}}
                    <v-dam-explorer-view-toggle :model-value="viewMode" @update:model-value="setView"></v-dam-explorer-view-toggle>
                    <button @click="$emit('close')" class="icon-cancel text-xl text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white transition"></button>
                </div>
            </div>

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1 px-5 py-2.5 text-sm flex-wrap border-b dark:border-cherry-800 min-h-[40px]">
                <template v-for="(crumb, i) in breadcrumb" :key="crumb.id">
                    <button
                        class="text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition"
                        :class="{ 'font-semibold text-gray-800 dark:text-white': i === breadcrumb.length - 1 }"
                        @click="navigateTo(i)"
                    >@{{ crumb.name }}</button>
                    <span v-if="i < breadcrumb.length - 1" class="text-gray-300 dark:text-gray-600 mx-0.5">/</span>
                </template>
            </div>

            {{-- Search --}}
            <div class="px-3 py-2 border-b dark:border-cherry-800">
                <div class="relative">
                    <input
                        v-model="query"
                        type="text"
                        class="block w-full rounded-lg border dark:border-cherry-800 bg-white dark:bg-cherry-900 py-1.5 pl-3 pr-10 text-sm leading-6 text-gray-600 dark:text-gray-300 transition hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 outline-none"
                        placeholder="@lang('dam::app.admin.dam.index.directory.search.placeholder')"
                        autocomplete="off"
                        @keydown.esc="query = ''"
                    />
                    <span v-if="! searchLoading" class="icon-search pointer-events-none absolute right-2.5 top-2 flex items-center text-2xl text-gray-400"></span>
                    <svg v-else class="animate-spin h-4 w-4 text-violet-600 absolute right-3 top-2.5 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </div>
            </div>

            {{-- Folder list --}}
            <div class="flex-1 overflow-y-auto px-3 py-2">
                <div v-if="loading || searchLoading" class="flex items-center justify-center h-32">
                    <span class="icon-spinner animate-spin text-2xl text-violet-500"></span>
                </div>

                {{-- Search results --}}
                <template v-else-if="isSearching">
                    <p v-if="visibleSearchResults.length === 0" class="flex items-center justify-center h-32 text-sm text-gray-400 dark:text-gray-500">
                        @lang('dam::app.admin.dam.index.directory.search.no-matches')
                    </p>

                    {{-- List view --}}
                    <template v-else-if="viewMode === 'list'">
                        <button
                            v-for="result in visibleSearchResults"
                            :key="result.id"
                            class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-left hover:bg-violet-50 dark:hover:bg-cherry-800 transition"
                            @click="selectSearchResult(result)"
                        >
                            <i class="icon-dam-folder text-2xl text-violet-400 dark:text-violet-500 shrink-0"></i>
                            <span class="flex flex-col min-w-0">
                                <span class="text-sm text-gray-700 dark:text-gray-200 truncate">@{{ result.name }}</span>
                                <span v-if="result.breadcrumb" class="text-xs text-gray-400 dark:text-gray-500 break-all leading-tight">@{{ result.breadcrumb }}</span>
                            </span>
                        </button>
                    </template>

                    {{-- Grid view --}}
                    <div v-else class="grid grid-cols-3 gap-2">
                        <button
                            v-for="result in visibleSearchResults"
                            :key="result.id"
                            class="flex flex-col items-center gap-1.5 p-3 rounded-lg hover:bg-violet-50 dark:hover:bg-cherry-800 transition"
                            :title="result.breadcrumb || result.name"
                            @click="selectSearchResult(result)"
                        >
                            <i class="icon-dam-folder text-4xl text-violet-400 dark:text-violet-500"></i>
                            <span class="text-xs text-gray-700 dark:text-gray-200 truncate w-full text-center">@{{ result.name }}</span>
                        </button>
                    </div>
                </template>

                {{-- Browse mode --}}
                <template v-else>
                    {{-- Inline create folder (in the directory currently browsed) --}}
                    @if (bouncer()->hasPermission('dam.directory.store'))
                    <div v-if="currentDirId" class="mb-2">
                        <button
                            v-if="! creating.open"
                            type="button"
                            @click="openCreate"
                            class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-left text-sm font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-cherry-800 transition"
                        >
                            <i class="icon-dam-add-folder text-2xl shrink-0"></i>
                            <span class="truncate">@lang('dam::app.admin.explorer.mass-actions.new-folder')</span>
                        </button>

                        <div v-else class="flex items-center gap-2 px-2 py-1.5 rounded-lg border border-violet-300 dark:border-violet-600 bg-violet-50/50 dark:bg-cherry-800">
                            <i class="icon-dam-folder text-2xl text-violet-400 dark:text-violet-500 shrink-0"></i>
                            <input
                                ref="createInput"
                                v-model="creating.name"
                                type="text"
                                class="flex-1 min-w-0 bg-transparent text-sm text-gray-700 dark:text-gray-200 outline-none placeholder:text-gray-400"
                                placeholder="@lang('dam::app.admin.explorer.mass-actions.folder-name')"
                                autocomplete="off"
                                @keydown.enter.prevent="submitCreate"
                                @keydown.esc.prevent="cancelCreate"
                            />
                            <button
                                type="button"
                                @click="submitCreate"
                                :disabled="creating.loading || ! creating.name.trim()"
                                class="shrink-0 flex items-center justify-center w-7 h-7 rounded-md text-white bg-violet-600 hover:bg-violet-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                :aria-label="'@lang('dam::app.admin.explorer.mass-actions.new-folder')'"
                            >
                                <svg v-if="! creating.loading" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg v-else class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="cancelCreate"
                                :disabled="creating.loading"
                                class="shrink-0 flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white disabled:opacity-40 transition"
                            >
                                <span class="icon-cancel text-xl"></span>
                            </button>
                        </div>

                        <p v-if="creating.error" class="mt-1 px-1 text-xs text-red-500 dark:text-red-400" v-text="creating.error"></p>
                    </div>
                    @endif

                    <p v-if="visibleDirs.length === 0" class="flex items-center justify-center h-32 text-sm text-gray-400 dark:text-gray-500">
                        @lang('dam::app.admin.explorer.empty')
                    </p>

                    {{-- List view --}}
                    <template v-else-if="viewMode === 'list'">
                        <button
                            v-for="dir in visibleDirs"
                            :key="dir.id"
                            class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-violet-50 dark:hover:bg-cherry-800 transition"
                            :class="{ 'ring-2 ring-inset ring-violet-500 bg-violet-50 dark:bg-cherry-800': dir.id === selectedChildId }"
                            @click="navigateInto(dir)"
                        >
                            <i class="icon-dam-folder text-2xl text-violet-400 dark:text-violet-500 shrink-0"></i>
                            <span class="truncate flex-1">@{{ dir.name }}</span>
                            <i v-if="dir.id === selectedChildId" class="icon-checkbox-check text-violet-600 dark:text-violet-400 text-lg shrink-0"></i>
                            <i v-else class="icon-chevron-right text-gray-300 dark:text-gray-600 text-lg shrink-0"></i>
                        </button>
                    </template>

                    {{-- Grid view --}}
                    <div v-else class="grid grid-cols-3 gap-2">
                        <button
                            v-for="dir in visibleDirs"
                            :key="dir.id"
                            class="flex flex-col items-center gap-1.5 p-3 rounded-lg hover:bg-violet-50 dark:hover:bg-cherry-800 transition"
                            :class="{ 'ring-2 ring-inset ring-violet-500 bg-violet-50 dark:bg-cherry-800': dir.id === selectedChildId }"
                            :title="dir.name"
                            @click="navigateInto(dir)"
                        >
                            <i class="icon-dam-folder text-4xl text-violet-400 dark:text-violet-500"></i>
                            <span class="text-xs text-gray-700 dark:text-gray-200 truncate w-full text-center">@{{ dir.name }}</span>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-5 py-4 border-t dark:border-cherry-800">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-cherry-700 rounded-lg hover:bg-gray-50 dark:hover:bg-cherry-800 transition"
                >
                    @lang('dam::app.admin.explorer.dialog.cancel')
                </button>
                <button
                    @click="confirm()"
                    :disabled="! currentDirId"
                    class="px-4 py-2 text-sm font-medium bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    @lang('dam::app.admin.explorer.mass-actions.select-here')
                </button>
            </div>
        </div>
    </div>
</script>

<script type="module">
app.component('v-dam-folder-picker', {
    template: '#v-dam-folder-picker-template',
    emits: ['picked', 'close'],

    props: {
        open:           { type: Boolean, default: false },
        tabId:          { type: String, required: true },
        excludedDirIds: { type: Array, default: () => [] },
    },

    data() {
        let savedView = 'list';
        try { savedView = localStorage.getItem('dam_picker_view') || 'list'; } catch (e) {}

        return {
            viewMode:        savedView,
            currentDirId:    null,
            breadcrumb:      [],
            dirs:            [],
            loading:         false,
            query:           '',
            searchResults:   [],
            searchLoading:   false,
            debounceTimer:   null,
            creating:        { open: false, name: '', loading: false, error: '' },
            // A new folder pre-selected as the destination, kept separate from the
            // browsed directory so the breadcrumb stays put.
            selectedChildId: null,
        };
    },

    computed: {
        isSearching() {
            return this.query.trim().length >= 2;
        },
        visibleDirs() {
            if (! this.excludedDirIds.length) return this.dirs;
            return this.dirs.filter(d => ! this.excludedDirIds.includes(d.id));
        },
        visibleSearchResults() {
            if (! this.excludedDirIds.length) return this.searchResults;
            return this.searchResults.filter(r => ! this.excludedDirIds.includes(r.id));
        },
    },

    watch: {
        open(val) {
            if (val) {
                this.reset();
                this.loadRoot();
            }
        },
        query(val) {
            clearTimeout(this.debounceTimer);
            this.searchResults = [];
            const q = (val || '').trim();
            if (q.length < 2) { this.searchLoading = false; return; }
            // Search hides the browse-only create UI; drop its transient state.
            this.selectedChildId = null;
            this.cancelCreate();
            this.searchLoading = true;
            this.debounceTimer = setTimeout(() => this.fetchSearch(q), 300);
        },
    },

    methods: {
        reset() {
            this.currentDirId   = null;
            this.breadcrumb     = [];
            this.dirs           = [];
            this.query          = '';
            this.searchResults  = [];
            this.searchLoading  = false;
            this.selectedChildId = null;
            this.cancelCreate();
            clearTimeout(this.debounceTimer);
        },

        fetchSearch(q) {
            this.$axios.get("{{ route('admin.dam.directory.search') }}", { params: { q, offset: 0 } })
                .then(({ data }) => {
                    this.searchResults = (data.data || []).map(r => ({
                        id:        r.id,
                        name:      r.name,
                        parent_id: r.parent_id,
                        breadcrumb: (r.path_names || []).join(' › '),
                        path_names: r.path_names || [],
                        path_ids:   r.path_ids   || [],
                    }));
                    this.searchLoading = false;
                })
                .catch(() => { this.searchResults = []; this.searchLoading = false; });
        },

        selectSearchResult(result) {
            this.selectedChildId = null;
            this.cancelCreate();
            this.query         = '';
            this.searchResults = [];
            this.currentDirId  = result.id;
            // Build full navigable breadcrumb from ancestor chain returned by search API
            this.breadcrumb = result.path_ids.map((id, i) => ({
                id,
                name: result.path_names[i] ?? '',
            }));
            this.loadChildren(result.id);
        },

        loadRoot() {
            this.loading = true;
            this.$axios.get("{{ route('admin.dam.directory.index') }}")
                .then(({ data }) => {
                    const root = Array.isArray(data.data) ? data.data[0] : null;
                    if (root) {
                        this.currentDirId = root.id;
                        this.breadcrumb   = [{ id: root.id, name: root.name }];
                        this.loadChildren(root.id);
                    }
                })
                .catch(() => { this.loading = false; });
        },

        loadChildren(dirId) {
            this.loading = true;
            this.$axios.get("{{ route('admin.dam.explorer.index') }}", {
                params: { directory_id: dirId, per_page: 250 },
            }).then(({ data }) => {
                this.dirs    = data.directories ?? [];
                this.loading = false;
            }).catch(() => { this.loading = false; });
        },

        navigateInto(dir) {
            this.selectedChildId = null;
            this.cancelCreate();
            this.currentDirId = dir.id;
            this.breadcrumb.push({ id: dir.id, name: dir.name });
            this.loadChildren(dir.id);
        },

        navigateTo(index) {
            if (index === this.breadcrumb.length - 1) return;
            this.selectedChildId = null;
            this.cancelCreate();
            this.breadcrumb   = this.breadcrumb.slice(0, index + 1);
            const crumb       = this.breadcrumb[index];
            this.currentDirId = crumb.id;
            this.loadChildren(crumb.id);
        },

        openCreate() {
            this.creating = { open: true, name: '', loading: false, error: '' };
            this.$nextTick(() => {
                if (this.$refs.createInput) this.$refs.createInput.focus();
            });
        },

        cancelCreate() {
            this.creating = { open: false, name: '', loading: false, error: '' };
        },

        submitCreate() {
            const name = (this.creating.name || '').trim();
            if (! name || this.creating.loading) return;

            this.creating.loading = true;
            this.creating.error   = '';

            this.$axios.post("{{ route('admin.dam.directory.store') }}", {
                name,
                parent_id: this.currentDirId,
            }).then(({ data }) => {
                const dir = data && data.data;
                this.creating = { open: false, name: '', loading: false, error: '' };

                if (dir && dir.id != null) {
                    // Surface the new folder and pre-select it as the destination.
                    if (! this.dirs.some(d => d.id === dir.id)) {
                        this.dirs.unshift({ id: dir.id, name: dir.name });
                    }
                    this.selectedChildId = dir.id;
                }
            }).catch((error) => {
                this.creating.loading = false;
                let msg = "@lang('dam::app.admin.explorer.mass-actions.create-error')";
                const resp = error && error.response;
                if (resp && resp.data) {
                    if (resp.data.errors && resp.data.errors.name && resp.data.errors.name.length) {
                        msg = resp.data.errors.name[0];
                    } else if (resp.data.message) {
                        msg = resp.data.message;
                    }
                }
                this.creating.error = msg;
            });
        },

        confirm() {
            const targetId = this.selectedChildId != null ? this.selectedChildId : this.currentDirId;
            if (! targetId) return;

            let name = '';
            if (this.selectedChildId != null) {
                const selected = this.dirs.find(d => d.id === this.selectedChildId);
                name = selected ? selected.name : '';
            } else {
                const crumb = this.breadcrumb[this.breadcrumb.length - 1];
                name = crumb ? crumb.name : '';
            }

            // Emit the destination name alongside the id so callers can show it
            // in the "moved/copied … to <destination>" success alert.
            this.$emit('picked', { id: targetId, name });
        },

        setView(mode) {
            this.viewMode = mode;
            try { localStorage.setItem('dam_picker_view', mode); } catch (e) {}
        },
    },
});
</script>
@endpush
@endonce
