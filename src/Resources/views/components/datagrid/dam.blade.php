@props(['isMultiRow' => false])

<v-datagrid {{ $attributes }}>
    <x-admin::shimmer.datagrid :isMultiRow="$isMultiRow" />

    {{ $slot }}
</v-datagrid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-template"
    >
        <div
            :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': gridLocked }"
            :aria-busy="gridLocked"
        >
            <!-- Action-in-flight overlay (mass delete / mass action) -->
            <div
                v-if="actionInFlight"
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
                    <span class="text-base font-semibold text-gray-900 dark:text-white text-center" v-text="actionStatusLabel"></span>
                </div>
            </div>

            <x-dam::datagrid.toolbar />

            <div class="flex mt-4">
                <x-dam::datagrid.gallery :isMultiRow="$isMultiRow">
                    <template #header>
                        <slot
                            name="header"
                            :columns="available.columns"
                            :actions="available.actions"
                            :mass-actions="available.massActions"
                            :records="available.records"
                            :meta="available.meta"
                            :sort-page="sortPage"
                            :selectAllRecords="selectAllRecords"
                            :available="available"
                            :applied="applied"
                            :is-loading="isLoading"
                        >
                        </slot>
                    </template>

                    <template #body>
                        <slot
                            name="body"
                            :columns="available.columns"
                            :actions="available.actions"
                            :mass-actions="available.massActions"
                            :records="available.records"
                            :meta="available.meta"
                            :setCurrentSelectionMode="setCurrentSelectionMode"
                            :performAction="performAction"
                            :available="available"
                            :applied="applied"
                            :is-loading="isLoading"
                        >
                        </slot>
                    </template>
                </x-dam::datagrid.gallery>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid', {
            template: '#v-datagrid-template',

            props: ['src'],

            data() {
                return {
                    isLoading: false,
                    actionInFlight: false,
                    actionStatusLabel: '',
                    treeBusy: false,
                    searchDebounceTimer: null,

                    defaultFilterIndices: [],

                    activeFilterIndices: [],

                    showFilterPicker: false,

                    filterPickerSearch: '',

                    available: {
                        id: null,

                        columns: [],

                        actions: [],

                        massActions: [],

                        records: [],

                        meta: {},
                    },

                    applied: {
                        massActions: {
                            meta: {
                                mode: 'none',

                                action: null,
                            },

                            indices: [],

                            value: null,
                        },

                        pagination: {
                            page: 1,

                            perPage: 50,
                        },

                        sort: {
                            column: null,

                            order: null,
                        },

                        filters: {
                            columns: [
                                {
                                    index: 'all',
                                    value: [],
                                },
                            ],
                        },
                    },
                };
            },

            computed: {
                // Freeze the directory tree only while a destructive request
                // is actually in flight (mass-delete / mass-action / per-row
                // delete). Selection alone does NOT lock — the user should be
                // free to click around until they confirm the action.
                gridBusy() {
                    return !! this.actionInFlight;
                },
                // Visual lock for the grid surface — true while this side is
                // mutating OR the tree side is mid-mutation. Keeps grid UI
                // non-interactive during in-flight mass-delete/mass-action,
                // matching the tree's own lockout.
                gridLocked() {
                    return this.actionInFlight || this.treeBusy;
                },
            },

            watch: {
                gridBusy(value) {
                    this.$emitter.emit('dam:grid-busy', value);
                },
            },

            mounted() {
                this.$emitter.on('data-grid:reset-all-filters', () => {
                    this.applied.filters.columns = [{
                        index: 'all',
                        value: []
                    }];
                    this.applied.pagination.page = 1;
                })

                this.$emitter.on('data-grid:refresh', () => this.get())

                this.$emitter.on('data-grid:filter', (data) => {
                    data.value.forEach( (value, index) => {
                        this.applyFilter(data.column, value);
                    });

                    this.get();
                })

                this.$emitter.on('dam:tree-busy', (busy) => {
                    this.treeBusy = !! busy;
                });

                this.boot();
            },

            methods: {
                /**
                 * Initialization: This function checks for any previously saved filters in local storage and applies them as needed.
                 *
                 * @returns {void}
                 */
                boot() {
                    let datagrids = this.getDatagrids();

                    const urlParams = new URLSearchParams(window.location.search);

                    if (urlParams.has('search')) {
                        let searchAppliedColumn = this.findAppliedColumn('all');

                        searchAppliedColumn.value = [urlParams.get('search')];
                    }

                    if (datagrids?.length) {
                        const currentDatagrid = datagrids.find(({
                            src
                        }) => src === this.src);

                        if (currentDatagrid) {
                            this.applied.pagination = currentDatagrid.applied.pagination;

                            this.applied.sort = currentDatagrid.applied.sort;

                            // this.applied.filters = currentDatagrid.applied.filters;

                            if (currentDatagrid.activeFilterIndices?.length) {
                                this.activeFilterIndices = currentDatagrid.activeFilterIndices;
                            }

                            if (currentDatagrid.defaultFilterIndices?.length) {
                                this.defaultFilterIndices = currentDatagrid.defaultFilterIndices;
                            }

                            if (urlParams.has('search')) {
                                let searchAppliedColumn = this.findAppliedColumn('all');

                                searchAppliedColumn.value = [urlParams.get('search')];
                            }

                            this.get();

                            return;
                        }
                    }

                    this.get();
                },

                /**
                 * Get. This will prepare params from the `applied` props and fetch the data from the backend.
                 *
                 * @returns {void}
                 */
                get(extraParams = {}) {
                    let params = {
                        pagination: {
                            page: this.applied.pagination.page,
                            per_page: this.applied.pagination.perPage,
                        },

                        sort: {},

                        filters: {},
                    };

                    if (
                        this.applied.sort.column &&
                        this.applied.sort.order
                    ) {
                        params.sort = this.applied.sort;
                    }

                    this.applied.filters.columns.forEach(column => {
                        params.filters[column.index] = column.value;
                    });

                    const focusedName = document.activeElement?.name ?? null;
                    const focusedSelectionStart = document.activeElement?.selectionStart ?? null;

                    this.isLoading = true;

                    this.$refs['filterDrawer']?.close();

                    this.$axios
                        .get(this.src, {
                            params: {
                                ...params,
                                ...extraParams
                            }
                        })
                        .then((response) => {
                            /**
                             * Precisely taking all the keys to the data prop to avoid adding any extra keys from the response.
                             */
                            const {
                                id,
                                columns,
                                actions,
                                mass_actions,
                                search_placeholder,
                                records,
                                meta
                            } = response.data;

                            this.available.id = id;

                            this.available.columns = columns;

                            this.available.actions = actions;

                            this.available.massActions = mass_actions;

                            this.available.records = records;

                            this.available.meta = meta;

                            this.available.searchPlaceholder = search_placeholder;

                            if (this.activeFilterIndices.length === 0) {
                                this.activeFilterIndices = this.available.columns
                                    .filter(col => col.filterable && col.visible !== false)
                                    .map(col => col.index);
                            }

                            if (this.defaultFilterIndices.length === 0) {
                                this.defaultFilterIndices = this.available.columns
                                    .filter(col => col.filterable && col.visible !== false)
                                    .map(col => col.index);
                            }

                            this.setCurrentSelectionMode();

                            this.updateDatagrids();

                            /**
                             * This event should be fired at the end, but only in the GET method. This allows the export feature to listen to it
                             * and update its properties accordingly.
                             */
                            this.$emitter.emit('change-datagrid', {
                                available: this.available,
                                applied: this.applied
                            });

                            this.isLoading = false;

                            if (focusedName) {
                                this.$nextTick(() => {
                                    const el = this.$el.querySelector(`input[name="${focusedName}"]`);
                                    if (el) {
                                        el.focus();
                                        if (focusedSelectionStart !== null) {
                                            el.setSelectionRange(focusedSelectionStart, focusedSelectionStart);
                                        }
                                    }
                                });
                            }
                        });
                },

                /**
                 * Change Page.
                 *
                 * The reason for choosing the numeric approach over the URL approach is to prevent any conflicts with our existing
                 * URLs. If we were to use the URL approach, it would introduce additional arguments in the `get` method, necessitating
                 * the addition of a `url` prop. Instead, by using the numeric approach, we can let Axios handle all the query parameters
                 * using the `applied` prop. This allows for a cleaner and more straightforward implementation.
                 *
                 * @param {string|integer} directionOrPageNumber
                 * @returns {void}
                 */
                changePage(directionOrPageNumber) {
                    let newPage;

                    if (typeof directionOrPageNumber === 'string') {
                        if (directionOrPageNumber === 'previous') {
                            newPage = this.available.meta.current_page - 1;
                        } else if (directionOrPageNumber === 'next') {
                            newPage = this.available.meta.current_page + 1;
                        } else {
                            console.warn('Invalid Direction Provided : ' + directionOrPageNumber);

                            return;
                        }
                    } else if (typeof directionOrPageNumber === 'number') {
                        newPage = directionOrPageNumber;
                    } else {
                        console.warn('Invalid Input Provided: ' + directionOrPageNumber);

                        return;
                    }

                    /**
                     * Check if the `newPage` is within the valid range.
                     */
                    if (newPage >= 1 && newPage <= this.available.meta.last_page) {
                        this.applied.pagination.page = newPage;

                        this.get();
                    } else {
                        console.warn('Invalid Page Provided: ' + newPage);
                    }
                },

                /**
                 * Change per page option.
                 *
                 * @param {integer} option
                 * @returns {void}
                 */
                changePerPageOption(option) {
                    this.applied.pagination.perPage = option;

                    /**
                     * When the total records are less than the number of data per page, we need to reset the page.
                     */
                    if (this.available.meta.last_page >= this.applied.pagination.page) {
                        this.applied.pagination.page = 1;
                    }

                    this.get();
                },

                /**
                 * Sort Page.
                 *
                 * @param {object} column
                 * @returns {void}
                 */
                sortPage(column) {
                    if (column.sortable) {
                        this.applied.sort = {
                            column: column.index,
                            order: this.applied.sort.order === 'asc' ? 'desc' : 'asc',
                        };

                        /**
                         * When the sorting changes, we need to reset the page.
                         */
                        this.applied.pagination.page = 1;

                        this.get();
                    }
                },

                /**
                 * Filter Page.
                 *
                 * @param {object} $event
                 * @param {object} column
                 * @param {object} additional
                 * @returns {void}
                 */
                filterPage($event, column = null, additional = {}) {
                    let quickFilter = additional?.quickFilter;

                    if (quickFilter?.isActive) {
                        let options = quickFilter.selectedFilter;

                        switch (column.type) {
                            case 'date_range':
                            case 'datetime_range':
                                this.applyFilter(column, options.from, {
                                    range: {
                                        name: 'from'
                                    }
                                });

                                this.applyFilter(column, options.to, {
                                    range: {
                                        name: 'to'
                                    }
                                });

                                break;

                            default:
                                break;
                        }
                    } else {
                        /**
                         * Here, either a real event will come or a string value. If a string value is present, then
                         * we create a similar event-like structure to avoid any breakage and make it easy to use.
                         */
                        if ($event?.target?.value === undefined) {
                            $event = {
                                target: {
                                    value: $event,
                                }
                            };
                        }

                        this.applyFilter(column, $event.target.value, additional);

                        if (column) {
                            $event.target.value = '';
                        }
                    }

                    /**
                     * We need to reset the page on filtering.
                     */
                    this.applied.pagination.page = 1;
                    if ('search' == $event.srcElement?.name) {
                        this.get();
                    }
                },

                debouncedFilterPage($event) {
                    clearTimeout(this.searchDebounceTimer);
                    this.searchDebounceTimer = setTimeout(() => this.filterPage($event), 500);
                },

                runFilters() {
                    this.get();
                },

                applyFilter(column, requestedValue, additional = {}) {
                    let appliedColumn = this.findAppliedColumn(column?.index);

                    /**
                     * If no column is found, it means that search from the toolbar have been
                     * activated. In this case, we will search for `all` indices and update the
                     * value accordingly.
                     */
                    if (! column) {
                        let appliedColumn = this.findAppliedColumn('all');

                        if (! requestedValue) {
                            appliedColumn.value = [];

                            return;
                        }

                        if (appliedColumn) {
                            appliedColumn.value = [requestedValue];
                        } else {
                            this.applied.filters.columns.push({
                                index: 'all',
                                value: [requestedValue]
                            });
                        }

                        /**
                         * Else, we will look into the sidebar filters and update the value accordingly.
                         */
                    } else {
                        /**
                         * Here if value already exists, we will not do anything.
                         */
                        if (
                            requestedValue === undefined ||
                            requestedValue === '' ||
                            appliedColumn?.value.includes(requestedValue)
                        ) {
                            return;
                        }

                        switch (column.type) {
                            case 'date_range':
                            case 'datetime_range':
                                let {
                                    range
                                } = additional;

                                if (appliedColumn) {
                                    let appliedRanges = appliedColumn.value[0];

                                    if (range.name == 'from') {
                                        appliedRanges[0] = requestedValue;
                                    }

                                    if (range.name == 'to') {
                                        appliedRanges[1] = requestedValue;
                                    }

                                    appliedColumn.value = [appliedRanges];
                                } else {
                                    let appliedRanges = ['', ''];

                                    if (range.name == 'from') {
                                        appliedRanges[0] = requestedValue;
                                    }

                                    if (range.name == 'to') {
                                        appliedRanges[1] = requestedValue;
                                    }

                                    this.applied.filters.columns.push({
                                        ...column,
                                        value: [appliedRanges]
                                    });
                                }

                                break;

                            default:
                                if (appliedColumn) {
                                    appliedColumn.value.push(requestedValue);
                                } else {
                                    this.applied.filters.columns.push({
                                        ...column,
                                        value: [requestedValue]
                                    });
                                }

                                break;
                        }
                    }
                },

                //================================================================
                // Filters logic, will move it from here once completed.
                //================================================================

                findAppliedColumn(columnIndex) {
                    return this.applied.filters.columns.find(column => column.index === columnIndex);
                },

                hasAnyAppliedColumnValues(columnIndex) {
                    let appliedColumn = this.findAppliedColumn(columnIndex);

                    return appliedColumn?.value.length > 0;
                },

                getAppliedColumnValues(columnIndex) {
                    let appliedColumn = this.findAppliedColumn(columnIndex);

                    return appliedColumn?.value ?? [];
                },

                removeAppliedColumnValue(columnIndex, appliedColumnValue) {
                    let appliedColumn = this.findAppliedColumn(columnIndex);

                    appliedColumn.value = appliedColumn?.value.filter(value => value !== appliedColumnValue);

                    /**
                     * Clean up is done here. If there are no applied values present, there is no point in including the applied column as well.
                     */
                    if (!appliedColumn.value.length) {
                        this.applied.filters.columns = this.applied.filters.columns.filter(column => column
                            .index !== columnIndex);
                    }
                },

                removeAppliedColumnAllValues(columnIndex) {
                    this.applied.filters.columns = this.applied.filters.columns.filter(column => column.index !==
                        columnIndex);

                    this.get();
                },

                //================================================================
                // Mass actions logic, will move it from here once completed.
                //================================================================

                setCurrentSelectionMode() {
                    this.applied.massActions.meta.mode = 'none';

                    if (! this.available.records.length) {
                        return;
                    }

                    let selectionCount = 0;

                    this.available.records.forEach(record => {
                        const id = record[this.available.meta.primary_column];

                        if (this.applied.massActions.indices.includes(id)) {
                            this.applied.massActions.meta.mode = 'partial';

                            ++selectionCount;
                        }
                    });

                    if (this.available.records.length === selectionCount) {
                        this.applied.massActions.meta.mode = 'all';
                    }
                },

                selectAllRecords() {
                    this.setCurrentSelectionMode();

                    if (['all', 'partial'].includes(this.applied.massActions.meta.mode)) {
                        this.available.records.forEach(record => {
                            const id = record[this.available.meta.primary_column];

                            this.applied.massActions.indices = this.applied.massActions.indices.filter(
                                selectedId => selectedId !== id);
                        });

                        this.applied.massActions.meta.mode = 'none';
                    } else {
                        this.available.records.forEach(record => {
                            const id = record[this.available.meta.primary_column];

                            let found = this.applied.massActions.indices.find(selectedId => selectedId ===
                                id);

                            if (!found) {
                                this.applied.massActions.indices.push(id);
                            }
                        });

                        this.applied.massActions.meta.mode = 'all';
                    }
                },

                validateMassAction() {
                    if (!this.applied.massActions.indices.length) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.datagrid.index.no-records-selected')"
                        });

                        return false;
                    }

                    if (!this.applied.massActions.meta.action) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.datagrid.index.must-select-a-mass-action')"
                        });

                        return false;
                    }

                    if (
                        this.applied.massActions.meta.action?.options?.length &&
                        this.applied.massActions.value === null
                    ) {
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: "@lang('admin::app.components.datagrid.index.must-select-a-mass-action-option')"
                        });

                        return false;
                    }

                    return true;
                },

                performMassAction(currentAction, currentOption = null) {
                    this.applied.massActions.meta.action = currentAction;

                    if (currentOption) {
                        this.applied.massActions.value = currentOption.value;
                    }

                    if (! this.validateMassAction()) {
                        return;
                    }

                    const {
                        action
                    } = this.applied.massActions.meta;

                    const method = action.method.toLowerCase();
                    const actionType = action?.options?.actionType?.toLowerCase() ?? '';

                    const selectedCount = this.applied.massActions.indices.length;
                    const startStatus = (label) => {
                        this.actionStatusLabel = label.replace(':count', selectedCount);
                        this.actionInFlight = true;
                    };
                    const stopStatus = () => {
                        this.actionInFlight = false;
                        this.actionStatusLabel = '';
                    };

                    this.$emitter.emit('delete' === actionType ? 'open-delete-modal': 'open-confirm-modal', {
                        agree: () => {
                            const statusLabel = 'delete' === actionType
                                ? `@lang('dam::app.admin.dam.index.mass-action.deleting')`
                                : `@lang('dam::app.admin.dam.index.mass-action.processing')`;

                            switch (method) {
                                case 'post':
                                case 'put':
                                case 'patch':
                                    startStatus(statusLabel);
                                    this.$axios[method](action.url, {
                                            indices: this.applied.massActions.indices,
                                            value: this.applied.massActions.value,
                                        })
                                        .then(response => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'success',
                                                message: response.data.message
                                            });
                                            this.$emitter.emit('delete-assets', {
                                                actionType: 'mass-action',
                                                count: selectedCount,
                                            });
                                            this.get();
                                        })
                                        .catch((error) => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'error',
                                                message: error.response.data.message
                                            });
                                        })
                                        .finally(() => {
                                            stopStatus();
                                        });

                                    break;

                                case 'delete':
                                    startStatus(statusLabel);
                                    this.$axios[method](action.url, {
                                            indices: this.applied.massActions.indices
                                        })
                                        .then(response => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'success',
                                                message: response.data.message
                                            });

                                            if ('delete' === actionType) {
                                                this.$emitter.emit('delete-assets', {
                                                    actionType: 'mass-action',
                                                    count: selectedCount,
                                                });
                                            }

                                            this.get();
                                        })
                                        .catch((error) => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'error',
                                                message: error.response.data.message
                                            });
                                        })
                                        .finally(() => {
                                            stopStatus();
                                        });

                                    break;

                                default:
                                    console.error('Method not supported.');

                                    break;
                            }

                            this.applied.massActions.indices = [];
                        }
                    });
                },

                //=======================================================================================
                // Support for previous applied values in datagrids. All code is based on local storage.
                //=======================================================================================

                updateDatagrids() {
                    let datagrids = this.getDatagrids();

                    if (datagrids?.length) {
                        const currentDatagrid = datagrids.find(({
                            src
                        }) => src === this.src);

                        if (currentDatagrid) {
                            datagrids = datagrids.map(datagrid => {
                                if (datagrid.src === this.src) {
                                    return {
                                        ...datagrid,
                                        requestCount: ++datagrid.requestCount,
                                        available: this.available,
                                        applied: this.applied,
                                        activeFilterIndices: this.activeFilterIndices,
                                        defaultFilterIndices: this.defaultFilterIndices,
                                    };
                                }

                                return datagrid;
                            });
                        } else {
                            datagrids.push(this.getDatagridInitialProperties());
                        }
                    } else {
                        datagrids = [this.getDatagridInitialProperties()];
                    }

                    this.setDatagrids(datagrids);
                },

                getActiveFilterColumns() {
                    return this.available.columns.filter(
                        col => col.filterable && this.activeFilterIndices.includes(col.index)
                    );
                },

                getInactiveFilterColumns() {
                    return this.available.columns.filter(
                        col => col.filterable && !this.activeFilterIndices.includes(col.index)
                    );
                },

                addActiveFilter(columnIndex) {
                    if (!this.activeFilterIndices.includes(columnIndex)) {
                        this.activeFilterIndices.push(columnIndex);
                    }

                    this.updateDatagrids();
                },

                removeActiveFilter(columnIndex) {
                    if (this.defaultFilterIndices.includes(columnIndex)) {
                        return;
                    }

                    this.activeFilterIndices = this.activeFilterIndices.filter(i => i !== columnIndex);

                    this.applied.filters.columns = this.applied.filters.columns.filter(
                        col => col.index !== columnIndex
                    );

                    this.updateDatagrids();
                },

                getDatagridInitialProperties() {
                    return {
                        src: this.src,
                        requestCount: 0,
                        available: this.available,
                        applied: this.applied,
                        activeFilterIndices: this.activeFilterIndices,
                        defaultFilterIndices: this.defaultFilterIndices,
                    };
                },

                getDatagridsStorageKey() {
                    return 'datagrids';
                },

                getDatagrids() {
                    let datagrids = localStorage.getItem(
                        this.getDatagridsStorageKey()
                    );

                    return JSON.parse(datagrids) ?? [];
                },

                setDatagrids(datagrids) {
                    localStorage.setItem(
                        this.getDatagridsStorageKey(),
                        JSON.stringify(datagrids)
                    );
                },

                //================================================================
                // Remaining logic, will check.
                //================================================================

                // refactor when not in that much use case...
                performAction(action) {
                    const method = action.method.toLowerCase();

                    switch (method) {
                        case 'get':
                            window.location.href = action.url;

                            break;

                        case 'post':
                        case 'put':
                        case 'patch':
                        case 'delete':
                            this.$emitter.emit('delete' === method ? 'open-delete-modal' : 'open-confirm-modal', {
                                agree: () => {
                                    this.actionInFlight = true;
                                    this.$axios[method](action.url)
                                        .then(response => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'success',
                                                message: response.data.message
                                            });

                                            this.get();
                                        })
                                        .catch((error) => {
                                            this.$emitter.emit('add-flash', {
                                                type: 'error',
                                                message: error.response.data.message
                                            });
                                        })
                                        .finally(() => {
                                            this.actionInFlight = false;
                                        });
                                }
                            });

                            break;

                        default:
                            console.error('Method not supported.');

                            break;
                    }
                },
            },
        });
    </script>
@endPushOnce
