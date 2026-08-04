<div
    class="group border-b border-gray-100 last:border-b-0 dark:border-cherry-800"
    v-for="column in getActiveFilterColumns()"
    :key="column.index"
    :data-datagrid-filter="column.index"
>
    <div class="flex items-center">
        <button
            type="button"
            class="flex min-w-0 flex-1 flex-col gap-y-0.5 py-3 text-left ltr:pr-1 rtl:pl-1"
            data-filter-toggle
            :aria-expanded="isFilterExpanded(column.index) ? 'true' : 'false'"
            @click="toggleFilterEditor(column.index)"
        >
            <span
                class="truncate text-xs font-semibold uppercase tracking-wide"
                :class="isFilterExpanded(column.index) || filterHasValue(column) ? 'text-gray-800 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
                data-filter-name
                v-text="filterLabel(column)"
            >
            </span>

            <span
                v-show="!isFilterExpanded(column.index)"
                class="truncate text-sm"
                :class="filterHasValue(column) ? 'text-primary-700 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'"
                data-filter-summary
                :title="collapsedSummary(column)"
                v-text="collapsedSummary(column)"
            >
            </span>
        </button>
    </div>

    <div class="pb-3" v-show="isFilterExpanded(column.index)">
        <div v-if="column.type === 'boolean'">
            <x-admin::dropdown>
                <x-slot:toggle>
                    <div
                        class="flex min-h-[39px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-md border bg-white px-2.5 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                    >
                        <template v-if="hasAnyAppliedColumnValues(column.index)">
                            <span
                                class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                                v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                            >
                                <span v-text="column.options.find((option => option.value == appliedColumnValue))?.label"></span>

                                <span
                                    class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-400"
                                    @click.stop="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                ></span>
                            </span>
                        </template>

                        <span
                            v-else
                            class="text-sm text-gray-400 dark:text-gray-400"
                            v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                        >
                        </span>

                        <span class="icon-chevron-down text-2xl ltr:ml-auto rtl:mr-auto"></span>
                    </div>
                </x-slot>

                <x-slot:menu>
                    <x-admin::dropdown.menu.item
                        v-for="option in column.options"
                        v-text="option.label"
                        @click="filterPage(option.value, column)"
                    >
                    </x-admin::dropdown.menu.item>
                </x-slot>
            </x-admin::dropdown>
        </div>

        <div v-else-if="column.type === 'dropdown'">
            <div v-if="column.options.type === 'basic'">
                <x-admin::dropdown>
                    <x-slot:toggle>
                        <div
                            class="flex min-h-[39px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-md border bg-white px-2.5 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-600 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                        >
                            <template v-if="hasAnyAppliedColumnValues(column.index)">
                                <span
                                    class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                                >
                                    <span v-text="column.options.params.options.find((option => option.value == appliedColumnValue))?.label"></span>

                                    <span
                                        class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-400"
                                        @click.stop="removeAppliedColumnValue(column.index, appliedColumnValue)"
                                    ></span>
                                </span>
                            </template>

                            <span
                                v-else
                                class="text-sm text-gray-400 dark:text-gray-400"
                                v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                            >
                            </span>

                            <span class="icon-chevron-down text-2xl ltr:ml-auto rtl:mr-auto"></span>
                        </div>
                    </x-slot>

                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="option in column.options.params.options"
                            v-text="option.label"
                            @click="filterPage(option.value, column)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>
            </div>

            <div v-else-if="column.options.type === 'searchable'">
                <v-dam-datagrid-searchable-dropdown
                    :datagrid-id="available.id"
                    :column="column"
                    @select-option="filterPage($event, column)"
                >
                </v-dam-datagrid-searchable-dropdown>

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-400"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div v-else-if="column.type === 'date_range'">
            <div class="grid grid-cols-2 gap-1.5">
                <p
                    class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:text-gray-300"
                    v-for="option in column.options"
                    v-text="option.label"
                    @click="filterPage(
                        $event,
                        column,
                        { quickFilter: { isActive: true, selectedFilter: option } }
                    )"
                >
                </p>

                <x-admin::flat-picker.date ::allow-input="false">
                    <input
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :type="column.input_type"
                        :name="`${column.index}[from]`"
                        :placeholder="filterLabel(column)"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'from' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.date>

                <x-admin::flat-picker.date ::allow-input="false">
                    <input
                        type="column.input_type"
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :name="`${column.index}[to]`"
                        :placeholder="filterLabel(column)"
                        :ref="`${column.index}[to]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.date>

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="col-span-2 mt-1.5 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' – ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-400"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div v-else-if="column.type === 'datetime_range'">
            <div class="grid grid-cols-2 gap-1.5">
                <p
                    class="cursor-pointer rounded-md border px-3 py-2 text-center text-sm font-medium leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:text-gray-300"
                    v-for="option in column.options"
                    v-text="option.label"
                    @click="filterPage(
                        $event,
                        column,
                        { quickFilter: { isActive: true, selectedFilter: option } }
                    )"
                >
                </p>

                <x-admin::flat-picker.datetime ::allow-input="false">
                    <input
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :type="column.input_type"
                        :name="`${column.index}[from]`"
                        :placeholder="filterLabel(column)"
                        :ref="`${column.index}[from]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'from' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.datetime>

                <x-admin::flat-picker.datetime ::allow-input="false">
                    <input
                        type="column.input_type"
                        value=""
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300"
                        :name="`${column.index}[to]`"
                        :placeholder="filterLabel(column)"
                        :ref="`${column.index}[to]`"
                        @change="filterPage(
                            $event,
                            column,
                            { range: { name: 'to' }, quickFilter: { isActive: false } }
                        )"
                    />
                </x-admin::flat-picker.datetime>

                <div v-if="hasAnyAppliedColumnValues(column.index)" class="col-span-2 mt-1.5 flex gap-2 flex-wrap">
                    <p
                        class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                        v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                    >
                        <span v-text="appliedColumnValue.join(' – ')"></span>

                        <span
                            class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-400"
                            @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                        >
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div v-else>
            <input
                type="text"
                class="flex min-h-[39px] w-full rounded-md border px-3 py-1.5 text-sm leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-cherry-800 dark:bg-cherry-800 dark:text-gray-300 dark:hover:border-gray-400"
                :name="column.index"
                :placeholder="filterLabel(column)"
                @change="filterPage($event, column)"
                @keyup.enter="filterPage($event, column)"
            />

            <div v-if="hasAnyAppliedColumnValues(column.index)" class="mt-1.5 flex gap-2 flex-wrap">
                <p
                    class="flex items-center rounded bg-primary-100 px-2 py-0.5 text-sm font-semibold text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                    v-for="appliedColumnValue in getAppliedColumnValues(column.index)"
                >
                    <span v-text="appliedColumnValue"></span>

                    <span
                        class="icon-cancel cursor-pointer text-lg text-primary-700 ltr:ml-1 rtl:mr-1 dark:!text-primary-400"
                        @click="removeAppliedColumnValue(column.index, appliedColumnValue)"
                    >
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-dam-datagrid-searchable-dropdown-template">
        <x-admin::dropdown ::close-on-click="false">
            <x-slot:toggle>
                <button
                    type="button"
                    class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                >
                    <span
                        class="text-sm text-gray-400 dark:text-gray-400"
                        v-text="'@lang('admin::app.components.datagrid.filters.select')'"
                    >
                    </span>

                    <span class="icon-chevron-down text-2xl"></span>
                </button>
            </x-slot>

            <x-slot:menu>
                <div class="relative">
                    <div class="relative rounded">
                        <ul class="list-reset">
                            <li class="p-2">
                                <input
                                    class="block w-full rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 px-2 py-1.5 text-sm leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                                    @keyup="lookUp($event)"
                                >
                            </li>

                            <ul class="p-2">
                                <li v-if="!isMinimumCharacters">
                                    <p
                                        class="block p-2 text-gray-600 dark:text-gray-300"
                                        v-text="'@lang('admin::app.components.datagrid.filters.dropdown.searchable.atleast-two-chars')'"
                                    >
                                    </p>
                                </li>

                                <li v-else-if="!searchedOptions.length">
                                    <p
                                        class="block p-2 text-gray-600 dark:text-gray-300"
                                        v-text="'@lang('admin::app.components.datagrid.filters.dropdown.searchable.no-results')'"
                                    >
                                    </p>
                                </li>

                                <li
                                    v-for="option in searchedOptions"
                                    v-else
                                >
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-300 p-2 cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800"
                                        v-text="option.label"
                                        @click="selectOption(option)"
                                    >
                                    </p>
                                </li>
                            </ul>
                        </ul>
                    </div>
                </div>
            </x-slot>
        </x-admin::dropdown>
    </script>

    <script type="module">
        app.component('v-dam-datagrid-searchable-dropdown', {
            template: '#v-dam-datagrid-searchable-dropdown-template',

            props: ['datagridId', 'column'],

            data() {
                return {
                    isMinimumCharacters: false,

                    searchedOptions: [],
                };
            },

            methods: {
                lookUp($event) {
                    let params = {
                        datagrid_id: this.datagridId,
                        column: this.column.index,
                        search: $event.target.value,
                    };

                    if (!(params['search'].length > 1)) {
                        this.searchedOptions = [];

                        this.isMinimumCharacters = false;

                        return;
                    }

                    this.$axios
                        .get('{{ route('admin.datagrid.look_up') }}', {
                            params
                        })
                        .then(({
                            data
                        }) => {
                            this.isMinimumCharacters = true;

                            this.searchedOptions = data;
                        });
                },

                selectOption(option) {
                    this.searchedOptions = [];

                    this.$emit('select-option', {
                        target: {
                            value: option.value
                        }
                    });
                },
            }
        });
    </script>
@endpushOnce
