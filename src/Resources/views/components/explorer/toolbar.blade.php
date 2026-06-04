{{--
    Explorer toolbar partial — search, filter drawer, pagination, view toggle.
    Rendered inside v-dam-tab-template via @include in tab.blade.php.
    All Vue bindings (searchInput, viewMode, meta, etc.) resolve to v-dam-tab's scope.
    @once guard prevents double-render when index.blade.php also includes this file.
--}}
@once('v-dam-explorer-toolbar')
        {{-- Row 2: search + filters button + pagination + view toggle --}}
        <div class="flex items-center gap-3 flex-wrap z-[10001]">
            <div class="min-w-[44px] flex-1 max-w-[260px] flex items-center gap-2 border border-gray-300 dark:border-cherry-600 rounded-lg px-3 py-2 bg-white dark:bg-cherry-900">
                <i class="icon-search text-gray-400 text-sm"></i>
                <input
                    type="text"
                    class="flex-1 min-w-0 bg-transparent text-sm text-gray-700 dark:text-gray-200 outline-none placeholder-gray-400"
                    :placeholder="'@lang('dam::app.admin.explorer.search.placeholder')'"
                    v-model="searchInput"
                    @input="onSearch"
                />
                <button v-if="searchInput" @click="clearSearch" class="text-gray-400 hover:text-gray-600 text-base leading-none">×</button>
            </div>

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
@endonce
