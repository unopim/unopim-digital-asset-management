{{--
    Explorer toolbar partial — search, filter drawer, pagination.
    Rendered inside v-dam-tab-template via @include in tab.blade.php.
    All Vue bindings (searchInput, meta, etc.) resolve to v-dam-tab's scope.
    @once guard prevents double-render when index.blade.php also includes this file.
--}}
@once('v-dam-explorer-toolbar')
        {{-- Row 2: search + filters + pagination --}}
        <div class="relative flex flex-wrap items-center justify-between gap-x-4 gap-y-2">

            {{-- Left: search + results count --}}
            <div class="flex flex-1 min-w-[120px] items-center gap-x-1">
                <div class="flex w-full max-w-[445px] min-w-0 items-center max-sm:max-w-full">
                    <div class="relative w-full min-w-0">
                        <input
                            type="text"
                            class="block w-full rounded-lg border dark:border-cherry-800 bg-white dark:bg-cherry-900 py-1.5 ltr:pl-3 rtl:pr-3 ltr:pr-10 rtl:pl-10 leading-6 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                            :placeholder="'@lang('dam::app.admin.explorer.search.placeholder')'"
                            v-model="searchInput"
                            @input="onSearch"
                            autocomplete="off"
                        />
                        <button
                            v-if="searchInput"
                            @click="clearSearch"
                            class="absolute ltr:right-2.5 rtl:left-2.5 top-2 text-gray-400 hover:text-gray-600 text-base leading-none"
                        >×</button>
                        <div v-else class="icon-search pointer-events-none absolute ltr:right-2.5 rtl:left-2.5 top-2 flex items-center text-2xl"></div>
                    </div>
                </div>

                {{-- Asset count --}}
                <div class="ltr:pl-2.5 rtl:pr-2.5" v-if="meta">
                    <p class="text-sm font-light text-gray-800 dark:text-white">
                        @{{ "@lang('admin::app.components.datagrid.toolbar.results')".replace(':total', meta.total_assets) }}
                    </p>
                </div>
            </div>

            {{-- Right: filter drawer + pagination --}}
            <div class="flex items-center gap-x-4">
                {{-- Filters drawer --}}
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

                {{-- Pagination --}}
                <v-dam-explorer-pager
                    v-if="meta"
                    :current-page="meta.current_page ?? 1"
                    :last-page="meta.last_page ?? 1"
                    :per-page="perPage"
                    @page-change="onPage"
                    @per-page-change="onPerPage"
                ></v-dam-explorer-pager>
            </div>
        </div>
@endonce
