<v-directory-search></v-directory-search>

@pushOnce('scripts')
<script type="text/x-template" id="v-directory-search-template">
    <div class="relative">
        <div class="relative w-full">
            <input
                ref="searchInput"
                v-model="query"
                type="text"
                class="block w-full rounded-lg border dark:border-cherry-800 bg-white dark:bg-cherry-900 py-1.5 ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 outline-none"
                :placeholder="placeholder"
                autocomplete="off"
                @keydown.esc="clearAndClose"
                @focus="maybeReopen"
            />

            <div
                v-if="!isLoading"
                class="icon-search pointer-events-none absolute ltr:right-2.5 rtl:left-2.5 top-2 flex items-center text-2xl text-gray-400"
                aria-hidden="true"
            ></div>
            <svg
                v-else
                class="animate-spin h-4 w-4 text-violet-600 absolute ltr:right-3 rtl:left-3 top-3 pointer-events-none"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </div>

        <div
            v-if="isOpen"
            ref="dropdown"
            class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-cherry-800 border border-gray-200 dark:border-cherry-700 rounded-md shadow-lg max-h-80 overflow-y-auto"
            @scroll.passive="onScroll"
        >
            <div
                v-if="errorMessage"
                class="px-3 py-2 text-sm text-red-600 dark:text-red-400"
                v-text="errorMessage"
            ></div>
            <div
                v-else-if="annotatedResults.length === 0 && !isLoading"
                class="px-3 py-2 text-sm text-zinc-500 dark:text-slate-400"
                v-text="noMatchesLabel"
            ></div>
            <div
                v-if="annotatedResults.length > 0"
                class="sticky top-0 px-3 py-1 text-xs text-zinc-500 dark:text-slate-300 bg-gray-50 dark:bg-cherry-700 border-b border-gray-200 dark:border-cherry-600"
                v-text="countLabel"
            ></div>
            <div
                v-for="(result, idx) in annotatedResults"
                :key="result.id"
                class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-cherry-700"
                :class="idx > 0 ? 'border-t border-gray-100 dark:border-cherry-700' : ''"
                @mousedown.prevent="selectResult(result)"
            >
                <div class="text-sm font-semibold text-zinc-700 dark:text-white">
                    @{{ result.display_name }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-slate-400">
                    @{{ result.breadcrumb }}
                </div>
            </div>
            <div
                v-if="isLoadingMore"
                class="px-3 py-2 text-xs text-center text-zinc-500 dark:text-slate-400"
            >
                <svg class="inline-block animate-spin h-4 w-4 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
        </div>
    </div>
</script>
<script type="module">
    app.component('v-directory-search', {
        template: '#v-directory-search-template',
        data() {
            return {
                query: '',
                results: [],
                total: 0,
                limit: 20,
                offset: 0,
                isLoading: false,
                isLoadingMore: false,
                isOpen: false,
                errorMessage: '',
                placeholder: "@lang('dam::app.admin.dam.index.directory.search.placeholder')",
                noMatchesLabel: "@lang('dam::app.admin.dam.index.directory.search.no-matches')",
                errorLabel: "@lang('dam::app.admin.dam.index.directory.search.error')",
                countCappedTpl: "@lang('dam::app.admin.dam.index.directory.search.count')",
                countTotalTpl: "@lang('dam::app.admin.dam.index.directory.search.count-total')",
                debounceTimer: null,
            };
        },
        computed: {
            annotatedResults() {
                const breadcrumbs = this.results.map(r => (r.path_names || []).join(' › '));
                const counts = breadcrumbs.reduce((acc, b) => {
                    acc[b] = (acc[b] || 0) + 1;
                    return acc;
                }, {});

                return this.results.map((r, i) => ({
                    id: r.id,
                    parent_id: r.parent_id,
                    display_name: counts[breadcrumbs[i]] > 1 ? `${r.name} (#${r.id})` : r.name,
                    breadcrumb: breadcrumbs[i],
                }));
            },
            countLabel() {
                return this.countCappedTpl
                    .replace(':shown', this.results.length)
                    .replace(':total', this.total);
            },
        },
        watch: {
            query(value) {
                clearTimeout(this.debounceTimer);
                this.errorMessage = '';

                const trimmed = (value || '').trim();
                if (trimmed.length < 2) {
                    this.results = [];
                    this.total = 0;
                    this.offset = 0;
                    this.isOpen = false;
                    this.isLoading = false;
                    this.isLoadingMore = false;
                    return;
                }

                this.debounceTimer = setTimeout(() => this.fetchResults(trimmed), 300);
            },
        },
        mounted() {
            document.addEventListener('mousedown', this.handleOutsideClick);
        },
        beforeUnmount() {
            document.removeEventListener('mousedown', this.handleOutsideClick);
        },
        methods: {
            handleOutsideClick(event) {
                if (! this.$el.contains(event.target)) {
                    this.isOpen = false;
                }
            },
            maybeReopen() {
                if (this.results.length > 0 || this.errorMessage) {
                    this.isOpen = true;
                }
            },
            fetchResults(q) {
                this.isLoading = true;
                this.offset = 0;
                this.$axios
                    .get("{{ route('admin.dam.directory.search') }}", { params: { q, offset: 0 } })
                    .then((response) => {
                        this.results = response.data.data || [];
                        this.total = response.data.meta?.total ?? this.results.length;
                        this.limit = response.data.meta?.limit ?? 20;
                        this.offset = this.results.length;
                        this.isLoading = false;
                        this.isOpen = true;
                    })
                    .catch(() => {
                        this.results = [];
                        this.total = 0;
                        this.offset = 0;
                        this.errorMessage = this.errorLabel;
                        this.isLoading = false;
                        this.isOpen = true;
                    });
            },
            fetchMore() {
                if (this.isLoading || this.isLoadingMore) return;
                if (this.results.length >= this.total) return;
                const q = (this.query || '').trim();
                if (q.length < 2) return;

                this.isLoadingMore = true;
                this.$axios
                    .get("{{ route('admin.dam.directory.search') }}", { params: { q, offset: this.offset } })
                    .then((response) => {
                        const next = response.data.data || [];
                        this.results = this.results.concat(next);
                        this.offset = this.results.length;
                        this.total = response.data.meta?.total ?? this.total;
                        this.isLoadingMore = false;
                    })
                    .catch(() => {
                        this.isLoadingMore = false;
                        this.errorMessage = this.errorLabel;
                    });
            },
            onScroll(event) {
                const el = event.target;
                if (! el) return;
                const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
                if (distanceFromBottom < 40) {
                    this.fetchMore();
                }
            },
            selectResult(result) {
                this.$emitter.emit('dam:reveal-directory', { id: result.id });
                this.query = '';
                this.results = [];
                this.total = 0;
                this.offset = 0;
                this.isOpen = false;
                this.errorMessage = '';
            },
            clearAndClose() {
                this.query = '';
                this.results = [];
                this.total = 0;
                this.offset = 0;
                this.isOpen = false;
                this.errorMessage = '';
            },
        },
    });
</script>
@endPushOnce
