
@once('v-dam-explorer-toolbar')
        <template v-if="loading && ! meta">
            <x-admin::shimmer.datagrid.toolbar />
        </template>

        <div v-else class="relative flex flex-wrap items-center justify-between gap-x-4 gap-y-2">

            <div class="flex flex-1 min-w-[120px] items-center gap-x-1">
                <label class="flex items-center cursor-pointer shrink-0 mr-2" data-select-all>
                    <input
                        type="checkbox"
                        class="peer hidden"
                        :checked="['all', 'partial'].includes(selection.mode)"
                        @change="toggleSelectAll"
                    >
                    <span
                        class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                        :class="{
                            'peer-checked:icon-checkbox-check peer-checked:text-primary-700': selection.mode === 'all',
                            'peer-checked:icon-checkbox-partial peer-checked:text-primary-700': selection.mode === 'partial',
                        }"
                    ></span>
                </label>

                <template v-if="selection.ids.length > 0">
                    @if (bouncer()->hasPermission('dam.asset.mass_delete') || bouncer()->hasPermission('dam.asset.update'))
                    <x-admin::dropdown>
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="flex items-center gap-x-1.5 rounded-md border border-primary-300 bg-primary-50 dark:bg-primary-900/30 dark:border-primary-700 px-3 py-1.5 text-sm font-medium text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-900/50 transition"
                            >
                                @lang('dam::app.admin.explorer.mass-actions.select-action')
                                <span class="icon-chevron-down text-2xl"></span>
                            </button>
                        </x-slot:toggle>

                        <x-slot:menu class="shadow-md !p-0 z-10">
                            @if (bouncer()->hasPermission('dam.asset.mass_delete'))
                            <li
                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                                @click="openFolderPicker('move')"
                            >
                                @lang('dam::app.admin.explorer.mass-actions.move-to')
                            </li>

                            <li
                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                                @click="openFolderPicker('copy')"
                            >
                                @lang('dam::app.admin.explorer.mass-actions.copy-to')
                            </li>
                            @endif

                            @if (bouncer()->hasPermission('dam.asset.update'))
                            <li
                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                                @click="openAssignTagsModal"
                            >
                                @lang('dam::app.admin.dam.tag.mass-action.assign-tags')
                            </li>
                            @endif

                            @if (bouncer()->hasPermission('dam.asset.mass_delete'))
                            <li
                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-cherry-800 cursor-pointer text-sm text-gray-700 dark:text-gray-300"
                                @click="performMassDelete"
                            >
                                @lang('dam::app.admin.explorer.mass-actions.delete')
                            </li>
                            @endif
                        </x-slot:menu>
                    </x-admin::dropdown>
                    @endif

                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                        @{{ "@lang('dam::app.admin.explorer.mass-actions.select-count')".replace(':count', selection.ids.length) }}
                    </span>
                </template>

                <template v-else>
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
                                class="absolute ltr:right-2.5 rtl:left-2.5 top-2 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200 text-base leading-none"
                            >×</button>
                            <div v-else class="icon-search pointer-events-none absolute ltr:right-2.5 rtl:left-2.5 top-2 flex items-center text-2xl"></div>
                        </div>
                    </div>

                    <div class="ltr:pl-2.5 rtl:pr-2.5" v-if="meta">
                        <p class="text-sm font-light text-gray-800 dark:text-white">
                            @{{ "@lang('admin::app.components.datagrid.toolbar.results')".replace(':total', meta.total_assets) }}
                        </p>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-x-4">
                <x-admin::drawer width="350px" ref="explorerFilterDrawer">
                    <x-slot:toggle>
                        <div>
                            <div
                                class="relative inline-flex w-full max-w-max ltr:pl-3 rtl:pr-3 ltr:pr-5 rtl:pl-5 cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-1 py-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-2"
                                :class="{'[&>*]:text-primary-700 [&>*]:dark:text-white': hasAppliedFilters()}"
                            >
                                <span class="icon-filter text-2xl"></span>

                                <span>
                                    @lang('admin::app.components.datagrid.toolbar.filter.title')
                                </span>

                                <span
                                    class="ltr:ml-0.5 rtl:mr-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-100 px-1.5 text-xs font-medium text-primary-700 dark:bg-cherry-800 dark:text-primary-400"
                                    data-applied-filter-count
                                    v-if="hasAppliedFilters()"
                                    v-text="appliedFilterCount()"
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
                    </x-slot:content>

                    <x-slot:footer class="mt-auto border-t border-gray-100 bg-white p-5 dark:border-cherry-800 dark:bg-cherry-800">
                        <div class="flex flex-col gap-y-1">
                            <button
                                type="button"
                                class="primary-button w-full justify-center text-center"
                                @click="runFilters()"
                            >
                                @lang('admin::app.components.datagrid.filters.save')
                            </button>

                            <button
                                type="button"
                                class="transparent-button justify-center self-center text-sm disabled:cursor-not-allowed disabled:opacity-50"
                                data-clear-all-filters
                                :disabled="! hasAppliedFilters()"
                                @click="clearAllFilters()"
                            >
                                @lang('admin::app.components.datagrid.filters.custom-filters.clear-all')
                            </button>
                        </div>
                    </x-slot:footer>
                </x-admin::drawer>

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
