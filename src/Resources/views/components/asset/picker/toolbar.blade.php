<template v-if="isLoading">
    <x-admin::shimmer.datagrid.toolbar />
</template>

<template v-else>
    <div class="mt-7 flex items-center justify-between gap-4 max-md:flex-wrap">
        <!-- Left Toolbar -->
        <div class="flex gap-x-1">
            <!-- Search Panel -->
            <div class="flex w-full items-center gap-x-1">
                <!-- Search Panel -->
                <div class="flex max-w-[445px] items-center max-sm:w-full max-sm:max-w-full">
                    <div class="relative w-full">
                        <input
                            type="text"
                            name="search"
                            :value="getAppliedColumnValues('all')"
                            class="block w-full rounded-lg border dark:border-cherry-800 bg-white dark:bg-cherry-900 py-1.5 ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400  dark:focus:border-gray-400"
                            :placeholder="available.searchPlaceholder"
                            autocomplete="off"
                            @input="debouncedFilterPage"
                        >

                        <div class="icon-search pointer-events-none absolute ltr:right-2.5 rtl:left-2.5 top-2 flex items-center text-2xl">
                        </div>
                    </div>
                </div>

                <!-- Information Panel -->
                <div class="ltr:pl-2.5 rtl:pr-2.5">
                    <p class="text-sm font-light text-gray-800 dark:text-white">
                        <!-- Need to manage this translation. -->
                        @{{ "@lang('admin::app.components.datagrid.toolbar.results')".replace(':total', available.meta.total) }}
                    </p>
                </div>

                <div
                    class="ltr:pl-2.5 rtl:pr-2.5" 
                    v-if="applied.massActions.indices.length"
                >
                    <p class="text-sm font-light text-gray-800 dark:text-white">
                        <!-- Need to manage this translation. -->
                        @{{ "@lang('admin::app.components.datagrid.toolbar.length-of')".replace(':length', applied.massActions.indices.length) }}

                        @{{ "@lang('admin::app.components.datagrid.toolbar.selected')".replace(':total', available.meta.total) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Toolbar -->
        <div class="flex gap-x-4">
            <!-- Filters Activation Button -->
            <x-admin::drawer width="350px" ref="filterDrawer">
                <x-slot:toggle>
                    <div>
                        <div
                            class="relative inline-flex w-full max-w-max ltr:pl-3 rtl:pr-3 ltr:pr-5 rtl:pl-5 cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-1 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-2"
                            :class="{'[&>*]:text-violet-700 [&>*]:dark:text-white': applied.filters.columns.filter(c => !['all', 'directory_id', 'directory_asset_id'].includes(c.index)).length > 0}"
                        >
                            <span class="icon-filter text-2xl"></span>

                            <span>
                                @lang('admin::app.components.datagrid.toolbar.filter.title')
                            </span>

                            <span
                                class="icon-dot absolute top-0.5 right-1 text-2xl font-bold"
                                v-if="applied.filters.columns.filter(c => !['all', 'directory_id', 'directory_asset_id'].includes(c.index)).length > 0"
                            ></span>
                        </div>

                        <div class="z-10 hidden w-full divide-y divide-gray-100 rounded bg-white dark:bg-cherry-800 shadow">
                        </div>
                    </div>
                </x-slot>

                <!-- Drawer Header -->
                <x-slot:header>
                    <div class="flex justify-between items-center p-3">
                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.components.datagrid.filters.title')
                        </p>
                    </div>
                </x-slot>

                <!-- Drawer Content -->
                <x-slot:content class="!p-5">
                    <x-admin::datagrid.filters />

                    <!-- Add Filter -->
                    <div class="mb-4" v-if="getInactiveFilterColumns().length">
                        <button
                            type="button"
                            class="flex items-center gap-1.5 w-full justify-center rounded-md border border-dashed border-gray-300 dark:border-cherry-800 px-3 py-2 text-sm font-medium text-violet-700 dark:text-violet-400 transition-all hover:border-violet-400 hover:bg-violet-50 dark:hover:border-violet-400 dark:hover:bg-cherry-800"
                            @click="showFilterPicker = !showFilterPicker; filterPickerSearch = ''"
                        >
                            <span class="icon-add text-lg"></span>

                            @lang('admin::app.components.datagrid.filters.add-filter')
                        </button>

                        <div
                            v-if="showFilterPicker"
                            class="mt-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-800 overflow-hidden"
                        >
                            <div class="sticky top-0 p-2 bg-white dark:bg-cherry-800 border-b dark:border-cherry-900">
                                <div class="relative">
                                    <input
                                        type="text"
                                        class="w-full rounded-md border dark:border-cherry-900 bg-white dark:bg-cherry-900 ltr:pl-3 rtl:pr-3 ltr:pr-8 rtl:pl-8 py-1.5 text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400"
                                        placeholder="@lang('admin::app.components.datagrid.filters.search-filter')"
                                        v-model="filterPickerSearch"
                                    />
                                    <span class="icon-search text-xl absolute ltr:right-2 rtl:left-2 top-1.5 text-gray-400 pointer-events-none"></span>
                                </div>
                            </div>

                            <div class="max-h-48 overflow-auto">
                                <p
                                    v-for="column in getInactiveFilterColumns().filter(c => !filterPickerSearch || c.label.toLowerCase().includes(filterPickerSearch.toLowerCase()))"
                                    class="cursor-pointer px-3 py-2 text-sm text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-900"
                                    v-text="column.label"
                                    @click="addActiveFilter(column.index); showFilterPicker = false"
                                ></p>

                                <p
                                    v-if="getInactiveFilterColumns().filter(c => !filterPickerSearch || c.label.toLowerCase().includes(filterPickerSearch.toLowerCase())).length === 0"
                                    class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500 text-center"
                                >
                                    @lang('admin::app.components.datagrid.filters.dropdown.searchable.no-results')
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="primary-button block text-center"
                        @click="runFilters()"
                    >
                        @lang('admin::app.components.datagrid.filters.save')
                    </div>
                </x-slot>
            </x-admin::drawer>

            <div class="flex items-center gap-x-2">
                <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                        >
                            <span v-text="applied.pagination.perPage"></span>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:menu>
                        <x-admin::dropdown.menu.item
                            v-for="perPageOption in available.meta.per_page_options"
                            v-text="perPageOption"
                            @click="changePerPageOption(perPageOption)"
                        >
                        </x-admin::dropdown.menu.item>
                    </x-slot>
                </x-admin::dropdown>

                <p class="whitespace-nowrap text-gray-600 dark:text-gray-300 max-sm:hidden">
                    @lang('admin::app.components.datagrid.toolbar.per-page')
                </p>

                <input
                    type="text"
                    class="inline-flex min-h-[38px] max-w-[60px] appearance-none items-center justify-center gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-3 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                    :value="available.meta.current_page"
                    @change="changePage(parseInt($event.target.value))"
                >

                <div class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                    <span> @lang('admin::app.components.datagrid.toolbar.of') </span>

                    <span v-text="available.meta.last_page"></span>
                </div>

                <!-- Pagination -->
                <div class="flex items-center gap-1">
                    <div
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                        @click="changePage('previous')"
                    >
                        <span class="icon-chevron-left text-2xl"></span>
                    </div>

                    <div
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                        @click="changePage('next')"
                    >
                        <span class="icon-chevron-right text-2xl"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
