@include('dam::components.explorer.context-menu')

{{-- Ensure v-dam-drop-upload is registered before v-dam-tab mounts --}}
<x-dam::asset.drop-upload />

<v-dam-explorer
    :acl-bypass="{{ dam_acl_bypass() ? 'true' : 'false' }}"
    :accessible-ids='@json(dam_accessible_dir_ids())'
></v-dam-explorer>


{{-- v-dam-bookmarks is defined in bookmarks.blade.php — included below via asset/index.blade.php --}}

@once('v-dam-explorer')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-template">
    <div class="flex flex-col flex-1 overflow-hidden">

        {{-- Tab bar --}}
        <div
            class="flex items-end gap-0 bg-gray-100 dark:bg-cherry-800 border-b border-gray-200 dark:border-cherry-700 px-2 pt-1.5 overflow-x-auto overflow-y-hidden flex-shrink-0"
            style="scrollbar-width: thin;"
        >
            <div
                v-for="tab in tabs"
                :key="tab.id"
                class="flex items-center gap-1.5 px-3 py-1.5 min-w-[110px] max-w-[180px] flex-shrink-0 rounded-t-md border border-b-0 text-sm cursor-pointer select-none transition-colors"
                :class="[
                    tab.id === activeTabId
                        ? 'bg-white dark:bg-cherry-900 border-gray-200 dark:border-cherry-700 text-gray-800 dark:text-white font-semibold z-10 -mb-px'
                        : 'bg-transparent border-transparent text-gray-500 hover:bg-white/60 dark:hover:bg-cherry-800 dark:text-white',
                    tabDragHover === tab.id ? 'ring-2 ring-inset ring-violet-400' : ''
                ]"
                @click="setActive(tab.id)"
                @dragover.prevent="onTabDragOver($event, tab.id)"
                @dragleave="onTabDragLeave($event, tab.id)"
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
                v-if="tabs.length < 20"
                type="button"
                class="w-7 h-7 mb-0.5 ml-1 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-200 dark:hover:bg-cherry-800 hover:text-gray-700 text-lg font-light shrink-0"
                @click="newTabFromCurrent()"
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
        return { tabs: [], activeTabId: null, _kbHandler: null, tabDragHover: null };
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

        this.$emitter.on('dam:bookmarks-ready', () => {
            const tab = this.tabs.find(t => t.id === this.activeTabId);
            if (tab?.directoryId) {
                this.$emitter.emit('dam:explorer-navigate', { directoryId: tab.directoryId });
            }
        });

        this.$emitter.on('dam:directory-deleted', ({ id }) => {
            const matching = this.tabs.filter(t => t.directoryId === id);
            if (! matching.length) return;
            matching.forEach(t => {
                if (this.tabs.length === 1) {
                    const fresh = this.makeTab();
                    this.tabs.splice(0, 1, fresh);
                    this.activeTabId = fresh.id;
                } else {
                    this.closeTab(t.id);
                }
            });
            this.persistTabs();
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

        persistTabs() {
            try {
                const snapshot = this.tabs.map(t => ({
                    directoryId: t.directoryId,
                    label:       t.label,
                    search:      t.search,
                    viewMode:    t.viewMode,
                    page:        t.page,
                    perPage:     t.perPage,
                }));
                const activeIdx = this.tabs.findIndex(t => t.id === this.activeTabId);
                localStorage.setItem('dam_explorer_tabs', JSON.stringify({ tabs: snapshot, activeIdx }));
            } catch {}
        },

        restore(initialDirId = null) {
            if (initialDirId) {
                this.newTab(initialDirId);
                return;
            }
            try {
                const raw = localStorage.getItem('dam_explorer_tabs');
                if (raw) {
                    const { tabs, activeIdx } = JSON.parse(raw);
                    if (Array.isArray(tabs) && tabs.length) {
                        tabs.forEach(t => {
                            const tab = this.makeTab(t.directoryId, t.label ?? '…');
                            tab.search   = t.search   ?? '';
                            tab.viewMode = t.viewMode ?? 'grid';
                            tab.page     = t.page     ?? 1;
                            tab.perPage  = t.perPage  ?? 50;
                            this.tabs.push(tab);
                        });
                        const idx = Math.min(Math.max(activeIdx ?? 0, 0), this.tabs.length - 1);
                        this.activeTabId = this.tabs[idx].id;
                        return;
                    }
                }
            } catch {}
            this.newTab();
        },

        newTabFromCurrent() {
            const active = this.tabs.find(t => t.id === this.activeTabId);
            this.newTab(active?.directoryId ?? null, active?.label ?? '…');
        },

        newTab(directoryId = null, label = '…') {
            if (this.tabs.length >= 20) return;
            const tab = this.makeTab(directoryId, label);
            this.tabs.push(tab);
            this.activeTabId = tab.id;
            this.persistTabs();
        },

        closeTab(id) {
            if (this.tabs.length <= 1) return;
            const idx = this.tabs.findIndex(t => t.id === id);
            this.tabs = this.tabs.filter(t => t.id !== id);
            if (this.activeTabId === id) this.activeTabId = this.tabs[Math.max(0, idx - 1)].id;
            const active = this.tabs.find(t => t.id === this.activeTabId);
            if (active?.directoryId) this.$emitter.emit('dam:explorer-navigate', { directoryId: active.directoryId });
            this.persistTabs();
        },

        setActive(id) {
            this.activeTabId = id;
            const tab = this.tabs.find(t => t.id === id);
            if (tab?.directoryId) {
                this.$emitter.emit('dam:explorer-navigate', { directoryId: tab.directoryId });
            }
            this.persistTabs();
        },

        onStateChange(id, state) {
            const tab = this.tabs.find(t => t.id === id);
            if (tab) { Object.assign(tab, state); this.persistTabs(); }
        },

        onLabelChange(id, label) {
            const tab = this.tabs.find(t => t.id === id);
            if (tab) { tab.label = label; this.persistTabs(); }
        },

        onTabDragOver(e, tabId) {
            if (tabId === this.activeTabId) return;
            if (! e.dataTransfer.types.includes('application/json')) return;
            this.tabDragHover = tabId;
            if (this._tabSpringTimer?.tabId === tabId) return;
            clearTimeout(this._tabSpringTimer?.id);
            const timerId = setTimeout(() => {
                if (this._tabSpringTimer?.tabId === tabId) {
                    this.setActive(tabId);
                    this._tabSpringTimer = null;
                    this.tabDragHover = null;
                }
            }, 1200);
            this._tabSpringTimer = { tabId, id: timerId };
        },

        onTabDragLeave(e, tabId) {
            if (this._tabSpringTimer?.tabId !== tabId) return;
            if (! e.currentTarget.contains(e.relatedTarget)) {
                clearTimeout(this._tabSpringTimer?.id);
                this._tabSpringTimer = null;
                this.tabDragHover = null;
            }
        },
    },
});
</script>
@endpush
@endonce

@include('dam::components.explorer.tab')

{{-- NOTE: v-dam-tab is intentionally included via @include (not <x-dam::explorer.tab>) --}}
{{-- because the USAGE of <v-dam-tab> inside the Vue x-template above passes Vue runtime --}}
{{-- variables (tab.id, tab.directoryId) which Blade cannot resolve at render time. --}}

@include('dam::components.explorer.toolbar')

@include('dam::components.explorer.view-toggle')

@include('dam::components.explorer.breadcrumb')

@include('dam::components.explorer.input-dialog')

@include('dam::components.explorer.folder-card')

@include('dam::components.explorer.asset-card')

@include('dam::components.explorer.grid')

@include('dam::components.explorer.list')

@include('dam::components.explorer.pagination')
