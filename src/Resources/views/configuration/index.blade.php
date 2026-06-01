<x-admin::layouts>
    <x-slot:title>
        @lang('dam::app.admin.configuration.title')
    </x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold dark:text-white text-gray-800">
            @lang('dam::app.admin.configuration.title')
        </p>
    </div>

    @php $canUpdate = bouncer()->hasPermission('dam.configuration.update'); @endphp

    <div class="mt-3.5 flex flex-col gap-3.5">
        <form method="POST" action="{{ route('admin.dam.configuration.update') }}">
            @csrf

            <div class="flex flex-col gap-3.5 bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4 {{ $canUpdate ? '' : 'opacity-60 pointer-events-none select-none' }}">
                <p class="text-base font-semibold dark:text-white text-gray-800">
                    @lang('dam::app.admin.configuration.general.title')
                </p>

                {{-- DAM_TREE_SHOW_ASSETS --}}
                <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-cherry-700">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.tree-show-assets.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.tree-show-assets.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center">
                        <input type="hidden" name="DAM_TREE_SHOW_ASSETS" value="0">
                        <input
                            type="checkbox"
                            name="DAM_TREE_SHOW_ASSETS"
                            id="dam_tree_show_assets"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_TREE_SHOW_ASSETS'] ? 'checked' : '' }}
                        >
                        <label
                            class="rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-violet-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-violet-700 dark:peer-checked:bg-violet-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800"
                            for="dam_tree_show_assets"
                        ></label>
                    </div>
                </div>

                {{-- DAM_EXPLORER_ENABLED --}}
                <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-cherry-700">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.explorer-enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.explorer-enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center">
                        <input type="hidden" name="DAM_EXPLORER_ENABLED" value="0">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_ENABLED"
                            id="dam_explorer_enabled"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_EXPLORER_ENABLED'] ? 'checked' : '' }}
                            onchange="
                                var row = document.getElementById('bookmarks-row');
                                var bm  = document.getElementById('dam_explorer_bookmarks_enabled');
                                if (this.checked) { row.classList.remove('hidden'); }
                                else { row.classList.add('hidden'); bm.checked = false; }
                            "
                        >
                        <label
                            class="rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-violet-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-violet-700 dark:peer-checked:bg-violet-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800"
                            for="dam_explorer_enabled"
                        ></label>
                    </div>
                </div>

                {{-- DAM_EXPLORER_BOOKMARKS_ENABLED — hidden unless explorer is enabled --}}
                <div id="bookmarks-row" class="flex items-center justify-between py-3 {{ $settings['DAM_EXPLORER_ENABLED'] ? '' : 'hidden' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.bookmarks-enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.bookmarks-enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center">
                        <input type="hidden" name="DAM_EXPLORER_BOOKMARKS_ENABLED" value="0">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_BOOKMARKS_ENABLED"
                            id="dam_explorer_bookmarks_enabled"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_EXPLORER_BOOKMARKS_ENABLED'] ? 'checked' : '' }}
                        >
                        <label
                            class="rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-violet-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-violet-700 dark:peer-checked:bg-violet-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800"
                            for="dam_explorer_bookmarks_enabled"
                        ></label>
                    </div>
                </div>
            </div>

            @if (bouncer()->hasPermission('dam.configuration.update'))
            <div class="mt-3.5 flex items-center justify-end gap-x-2.5">
                <button type="submit" class="primary-button">
                    @lang('dam::app.admin.configuration.save-btn')
                </button>
            </div>
            @endif
        </form>
    </div>

</x-admin::layouts>
