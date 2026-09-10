<x-admin::layouts>
    <x-slot:title>
        @lang('dam::app.admin.configuration.title')
    </x-slot:title>

    @php
        $canUpdate = bouncer()->hasPermission('dam.configuration.update');

        $treeEffectiveOn = ! $settings['DAM_EXPLORER_ENABLED'] || $settings['DAM_EXPLORER_SHOW_TREE'];
        $toggleClass = "rounded-full w-9 h-5 bg-gray-200 cursor-pointer peer-focus:ring-primary-300 after:bg-white dark:after:bg-white after:border-gray-300 dark:after:border-white peer-checked:bg-primary-700 dark:peer-checked:bg-primary-700 peer peer-checked:after:border-white peer-checked:after:ltr:translate-x-full peer-checked:after:rtl:-translate-x-full after:content-[''] after:absolute after:top-0.5 after:ltr:left-0.5 after:rtl:right-0.5 peer-focus:outline-none after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:bg-cherry-800";
    @endphp

    <x-admin::form
        method="POST"
        :action="route('admin.dam.configuration.update')"
        ajax
    >
        <x-admin::layouts.page-header
            :title="trans('dam::app.admin.configuration.title')"
            :description="trans('dam::app.admin.configuration.description')"
        >
            @if ($canUpdate)
                <x-slot:actions>
                    <button type="submit" class="primary-button">
                        @lang('dam::app.admin.configuration.save-btn')
                    </button>
                </x-slot:actions>
            @endif
        </x-admin::layouts.page-header>

        <div class="grid grid-cols-[1fr_2fr] gap-10 mt-6 max-xl:grid-cols-1 {{ $canUpdate ? '' : 'opacity-60 pointer-events-none select-none' }}">

            <div class="grid gap-2.5 content-start">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    @lang('dam::app.admin.configuration.general.title')
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-[140%]">
                    @lang('dam::app.admin.configuration.general.description')
                </p>
            </div>

            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow divide-y divide-gray-100 dark:divide-cherry-800">
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.explorer-enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                            @lang('dam::app.admin.configuration.general.explorer-enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0">
                        <input type="hidden" name="DAM_EXPLORER_ENABLED" value="0">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_ENABLED"
                            id="dam_explorer_enabled"
                            value="1"
                            class="sr-only peer"
                            onchange="window.damConfigSync && window.damConfigSync('explorer')"
                            {{ $settings['DAM_EXPLORER_ENABLED'] ? 'checked' : '' }}
                        >
                        <label class="{{ $toggleClass }}" for="dam_explorer_enabled"></label>
                    </div>
                </div>

                <div id="bookmarks-row" class="flex items-center justify-between gap-4 px-5 py-4 {{ $settings['DAM_EXPLORER_ENABLED'] ? '' : 'hidden' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.general.bookmarks-enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                            @lang('dam::app.admin.configuration.general.bookmarks-enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0">
                        <input type="hidden" name="DAM_EXPLORER_BOOKMARKS_ENABLED" value="0">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_BOOKMARKS_ENABLED"
                            id="dam_explorer_bookmarks_enabled"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_EXPLORER_BOOKMARKS_ENABLED'] ? 'checked' : '' }}
                        >
                        <label class="{{ $toggleClass }}" for="dam_explorer_bookmarks_enabled"></label>
                    </div>
                </div>
            </div>

            <div class="grid gap-2.5 content-start">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    @lang('dam::app.admin.configuration.directory.title')
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-[140%]">
                    @lang('dam::app.admin.configuration.directory.description')
                </p>
            </div>

            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow divide-y divide-gray-100 dark:divide-cherry-800">
                <div id="show-tree-row" class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.directory.show-tree.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                            @lang('dam::app.admin.configuration.directory.show-tree.hint')
                        </p>
                        <p id="show-tree-locked-hint" class="text-xs text-primary-600 dark:text-primary-400 mt-1 {{ $settings['DAM_EXPLORER_ENABLED'] ? 'hidden' : '' }}">
                            @lang('dam::app.admin.configuration.directory.show-tree.locked-hint')
                        </p>
                    </div>
                    <div id="show-tree-toggle" class="relative inline-flex items-center shrink-0 {{ $settings['DAM_EXPLORER_ENABLED'] ? '' : 'opacity-60 pointer-events-none' }}">
                        <input type="hidden" id="dam_explorer_show_tree_hidden" name="DAM_EXPLORER_SHOW_TREE" value="{{ $settings['DAM_EXPLORER_ENABLED'] ? '0' : '1' }}">
                        <input
                            type="checkbox"
                            name="DAM_EXPLORER_SHOW_TREE"
                            id="dam_explorer_show_tree"
                            value="1"
                            class="sr-only peer"
                            onchange="window.damConfigSync && window.damConfigSync('tree')"
                            {{ $treeEffectiveOn ? 'checked' : '' }}
                            {{ $settings['DAM_EXPLORER_ENABLED'] ? '' : 'disabled' }}
                        >
                        <label class="{{ $toggleClass }}" for="dam_explorer_show_tree"></label>
                    </div>
                </div>

                <div id="tree-show-assets-row" class="flex items-center justify-between gap-4 px-5 py-4 {{ $treeEffectiveOn ? '' : 'hidden' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.directory.tree-show-assets.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                            @lang('dam::app.admin.configuration.directory.tree-show-assets.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0">
                        <input type="hidden" name="DAM_TREE_SHOW_ASSETS" value="0">
                        <input
                            type="checkbox"
                            name="DAM_TREE_SHOW_ASSETS"
                            id="dam_tree_show_assets"
                            value="1"
                            class="sr-only peer"
                            {{ $settings['DAM_TREE_SHOW_ASSETS'] ? 'checked' : '' }}
                        >
                        <label class="{{ $toggleClass }}" for="dam_tree_show_assets"></label>
                    </div>
                </div>
            </div>

            <div class="grid gap-2.5 content-start">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    @lang('dam::app.admin.configuration.ai-tagging.title')
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-[140%]">
                    @lang('dam::app.admin.configuration.ai-tagging.description')
                </p>
            </div>

            <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow divide-y divide-gray-100 dark:divide-cherry-800">
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">
                            @lang('dam::app.admin.configuration.ai-tagging.enabled.label')
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                            @lang('dam::app.admin.configuration.ai-tagging.enabled.hint')
                        </p>
                    </div>
                    <div class="relative inline-flex items-center shrink-0">
                        <input type="hidden" name="DAM_AI_TAGGING_ENABLED" value="0">
                        <input
                            type="checkbox"
                            name="DAM_AI_TAGGING_ENABLED"
                            id="dam_ai_tagging_enabled"
                            value="1"
                            class="sr-only peer"
                            onchange="window.damConfigSync && window.damConfigSync('ai-tagging')"
                            {{ $settings['DAM_AI_TAGGING_ENABLED'] ? 'checked' : '' }}
                        >
                        <label class="{{ $toggleClass }}" for="dam_ai_tagging_enabled"></label>
                    </div>
                </div>

                <div id="ai-tagging-platform-row" class="grid gap-2 px-5 py-4 {{ $settings['DAM_AI_TAGGING_ENABLED'] ? '' : 'hidden' }}">
                    <label for="dam_ai_tagging_platform_id" class="text-sm font-medium text-gray-800 dark:text-white">
                        @lang('dam::app.admin.configuration.ai-tagging.platform.label')
                    </label>

                    <x-admin::form.control-group class="max-w-sm">
                        <x-admin::form.control-group.control
                            type="select"
                            id="dam_ai_tagging_platform_id"
                            name="DAM_AI_TAGGING_PLATFORM_ID"
                            async="true"
                            list-route="{{ route('admin.dam.configuration.ai-tagging.platforms') }}"
                            track-by="id"
                            label-by="label"
                            :value="$selectedAiTaggingPlatform ? json_encode(['id' => (string) $selectedAiTaggingPlatform['id'], 'label' => $selectedAiTaggingPlatform['label']]) : 'null'"
                            :placeholder="trans('dam::app.admin.configuration.ai-tagging.platform.use-default')"
                            :disabled="! $hasAiTaggingPlatforms"
                        />
                    </x-admin::form.control-group>

                    @unless ($hasAiTaggingPlatforms)
                        <p class="text-xs text-primary-600 dark:text-primary-400 leading-relaxed">
                            @lang('dam::app.admin.configuration.ai-tagging.platform.none-configured-hint')
                        </p>
                    @endunless

                    <label for="dam_ai_tagging_max_tags" class="text-sm font-medium text-gray-800 dark:text-white mt-2">
                        @lang('dam::app.admin.configuration.ai-tagging.max-tags.label')
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 -mt-1.5">
                        @lang('dam::app.admin.configuration.ai-tagging.max-tags.hint')
                    </p>

                    <x-admin::form.control-group class="max-w-[8rem]">
                        <x-admin::form.control-group.control
                            type="number"
                            id="dam_ai_tagging_max_tags"
                            name="DAM_AI_TAGGING_MAX_TAGS"
                            min="1"
                            max="20"
                            :value="$settings['DAM_AI_TAGGING_MAX_TAGS']"
                        />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>

    </x-admin::form>

    @push('scripts')
    <script>
        window.damConfigSync = function (source) {
            var explorer          = document.getElementById('dam_explorer_enabled');
            var showTree          = document.getElementById('dam_explorer_show_tree');
            var showTreeHidden    = document.getElementById('dam_explorer_show_tree_hidden');
            var showTreeToggle    = document.getElementById('show-tree-toggle');
            var lockedHint        = document.getElementById('show-tree-locked-hint');
            var treeShowAssetsRow = document.getElementById('tree-show-assets-row');
            var bookmarksRow      = document.getElementById('bookmarks-row');
            var showAssets        = document.getElementById('dam_tree_show_assets');
            var bookmarks         = document.getElementById('dam_explorer_bookmarks_enabled');

            if (! explorer || ! showTree) return;

            if (source === 'explorer' && explorer.checked && bookmarks) bookmarks.checked = true;
            if (source === 'tree'     && showTree.checked && showAssets) showAssets.checked = true;

            if (source === 'ai-tagging') {
                var aiTaggingEnabled = document.getElementById('dam_ai_tagging_enabled');
                var aiTaggingPlatformRow = document.getElementById('ai-tagging-platform-row');
                if (aiTaggingEnabled && aiTaggingPlatformRow) {
                    aiTaggingPlatformRow.classList.toggle('hidden', ! aiTaggingEnabled.checked);
                }
            }

            var LOCK = ['opacity-60', 'pointer-events-none'];
            function setLocked(el, locked) {
                if (! el) return;
                LOCK.forEach(function (c) { el.classList.toggle(c, locked); });
            }

            var explorerOn = explorer.checked;

            if (bookmarksRow) bookmarksRow.classList.toggle('hidden', ! explorerOn);

            if (! explorerOn) {

                showTree.checked  = true;
                showTree.disabled = true;
                if (showTreeHidden) showTreeHidden.value = '1';
                setLocked(showTreeToggle, true);
                if (lockedHint) lockedHint.classList.remove('hidden');
            } else {
                showTree.disabled = false;
                if (showTreeHidden) showTreeHidden.value = '0';
                setLocked(showTreeToggle, false);
                if (lockedHint) lockedHint.classList.add('hidden');
            }

            if (treeShowAssetsRow) treeShowAssetsRow.classList.toggle('hidden', ! showTree.checked);
        };
    </script>
    @endpush

</x-admin::layouts>
