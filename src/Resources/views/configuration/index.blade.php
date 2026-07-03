<x-admin::layouts>
    <x-slot:title>
        @lang('dam::app.admin.configuration.title')
    </x-slot:title>

    @php $canUpdate = bouncer()->hasPermission('dam.configuration.update'); @endphp

    <form method="POST" action="{{ route('admin.dam.configuration.update') }}">
        @csrf

        {{-- Sticky header --}}
        <div class="bg-white dark:bg-cherry-800 -mx-4 px-4 pb-2.5">
            <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl font-bold dark:text-white text-gray-800">
                    @lang('dam::app.admin.configuration.title')
                </p>
                @if ($canUpdate)
                <button type="submit" class="primary-button">
                    @lang('dam::app.admin.configuration.save-btn')
                </button>
                @endif
            </div>
        </div>

        {{-- 1fr description + 2fr form --}}
        <div class="grid grid-cols-[1fr_2fr] gap-10 mt-6 max-xl:grid-cols-1 {{ $canUpdate ? '' : 'opacity-60 pointer-events-none select-none' }}">

            {{-- Left: section label + description --}}
            <div class="grid gap-2.5 content-start">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    @lang('dam::app.admin.configuration.general.title')
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-[140%]">
                    @lang('dam::app.admin.configuration.general.description')
                </p>
            </div>

            {{-- Right: toggle rows --}}
            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow">

                {{-- DAM_TREE_SHOW_ASSETS --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-cherry-700">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.tree-show-assets.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.tree-show-assets.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0 ml-4">
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
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-cherry-700">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.explorer-enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.explorer-enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0 ml-4">
                        <input type="hidden" name="DAM_EXPLORER_ENABLED" value="0">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_ENABLED"
                            id="dam_explorer_enabled"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_EXPLORER_ENABLED'] ? 'checked' : '' }}
                            onchange="
                                var row  = document.getElementById('bookmarks-row');
                                var tree = document.getElementById('show-tree-row');
                                var bm   = document.getElementById('dam_explorer_bookmarks_enabled');
                                var st   = document.getElementById('dam_explorer_show_tree');
                                if (this.checked) { row.classList.remove('hidden'); tree.classList.remove('hidden'); }
                                else { row.classList.add('hidden'); bm.checked = false; tree.classList.add('hidden'); st.checked = false; }
                            "
                        >
                        <label
                            class="rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-violet-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-violet-700 dark:peer-checked:bg-violet-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800"
                            for="dam_explorer_enabled"
                        ></label>
                    </div>
                </div>

                {{-- DAM_EXPLORER_BOOKMARKS_ENABLED — hidden unless explorer is enabled --}}
                <div id="bookmarks-row" class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-cherry-700 {{ $settings['DAM_EXPLORER_ENABLED'] ? '' : 'hidden' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.bookmarks-enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.bookmarks-enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0 ml-4">
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

                {{-- DAM_EXPLORER_SHOW_TREE — hidden unless explorer is enabled --}}
                <div id="show-tree-row" class="flex items-center justify-between px-4 py-3 {{ $settings['DAM_EXPLORER_ENABLED'] ? '' : 'hidden' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.show-tree.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @lang('dam::app.admin.configuration.general.show-tree.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0 ml-4">
                        <input type="hidden" name="DAM_EXPLORER_SHOW_TREE" value="0">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_SHOW_TREE"
                            id="dam_explorer_show_tree"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_EXPLORER_SHOW_TREE'] ? 'checked' : '' }}
                        >
                        <label
                            class="rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-violet-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-violet-700 dark:peer-checked:bg-violet-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800"
                            for="dam_explorer_show_tree"
                        ></label>
                    </div>
                </div>

            </div>
        </div>

    </form>

</x-admin::layouts>
