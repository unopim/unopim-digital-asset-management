<v-tree-view
    :acl-bypass="{{ dam_acl_bypass() ? 'true' : 'false' }}"
    :accessible-ids='@json(dam_accessible_dir_ids())'
>
    <x-admin::shimmer.tree />
</v-tree-view>

@pushOnce('scripts')
<!-- asset name template -->
<script type="text/x-template" id="v-asset-item-template">
    <div
        class="tree-container-assets-details"
    >
        <div
            class="flex gap-1 w-full p-1"
            :class="treeLocked ? 'cursor-not-allowed opacity-60 pointer-events-none' : 'cursor-pointer'"
            :aria-disabled="treeLocked"
            @click.stop="treeLocked ? null : setFilters(item)"
            @contextmenu.prevent.stop="treeLocked ? null : showContextMenu($event, item)"
        >
            <span>
                <i 
                    class="text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"
                    :class="getFileTypeIcon(item)"
                ></i>
            </span>
            <span
                class="text-sm"
                :class="selectedItem && selectedItem.file_name && item.id == selectedItem.id ? 'text-violet-700 dark:text-violet-400 font-semibold' : 'text-zinc-600 dark:text-white'"
            >@{{ formatFileName(item.file_name) }}</span>
        </div>
    </div>
</script>
<script type="module">
    app.component('v-asset-item', {
        template: "#v-asset-item-template",
        props: {
            item: Object,
            selectedItem: Object,
            treeLocked: {
                type: Boolean,
                default: false,
            },
        },
        mounted() {
            this.$emitter.on('update-current-item', (data) => {
                if (data.id === this.item.id) {
                    this.item = data;
                }
            });
        },
        methods: {
            setFilters(item) {
                this.$emit("set-filters", item, 'asset');
            },

            getFileTypeIcon(item) {
                switch (item.file_type) {
                    case 'image':
                        return 'icon-dam-image';
                    case 'video':
                        return 'icon-dam-video';
                    case 'audio':
                        return 'icon-dam-audio';
                    case 'document':
                        return 'icon-dam-doc';
                    default:
                        return 'icon-dam-image';
                }
            },

            formatFileName(fileName) {
                if (fileName.length > 29) {
                    fileName = fileName.substring(0, 20) + '...' + fileName.substring(fileName.lastIndexOf('.'));
                }

                return fileName
            },

            selectItem(item) {
                this.$emit('select-item', item);
            },

            openContextMenu(event, item) {
                event.stopPropagation();
                this.$emit('open-context-menu', event, item);
            },

            showContextMenu(event, item) {
                event.stopPropagation();
                this.contextMenuPosition = {
                    x: event.pageX,
                    y: event.pageY
                };
                this.showContextMenuFlag = true;
                this.$emit("right-click-item", event, item, 'asset'); // Emit event for parent handling
            },
        }
    });
</script>
<!-- item template -->
<script type="text/x-template" id="v-item-template">
    <div class="tree-container-details">
        <div
            class="flex gap-1 w-full pl-1 pt-1 text-nowrap"
            :class="isBusy ? 'cursor-not-allowed opacity-60 pointer-events-none' : 'cursor-pointer'"
            :aria-disabled="isBusy"
            @click.stop="isBusy ? null : toggle(item)"
            @contextmenu.prevent.stop="isBusy ? null : showContextMenu($event, item)"
            @dragenter.prevent="isBusy ? null : onDragEnter()"
            @dragover.prevent
        >
            <span
                class="text-xl text-zinc-600 dark:text-white"
                v-if="isDirectory || isAssets"
                :class="isOpen ? 'icon-dam-close' : 'icon-dam-open'"
            >
            </span>
            <span>
                <svg
                    v-if="isSelfBusy"
                    class="align-center inline-block animate-spin h-5 w-5 text-violet-700"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="#8A2BE2"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <i v-else class="icon-dam-folder text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>
            </span>
            <span
                class="text-sm"
                :class="selectedItem && item.id == selectedItem.id ? 'text-violet-700 dark:text-violet-400 font-semibold' : 'text-zinc-600 dark:text-white'"
            >@{{ item?.name }}   </span>
        </div>
        <div
            v-show="isOpen"
            v-if="hasDropZone"
            class="flex flex-col pl-6"
        >
            <!-- Directories -->
            <draggable
                id="child-tree-groups"
                class="directoryItems"
                ghost-class="draggable-ghost"
                handle=".icon-dam-folder"
                v-bind="{animation: 200}"
                :list="item.children"
                item-key="id"
                :sort='false'
                group="directoryItems"
                @change="onMergeItems($event, item.id)"
                @start="onDragStart"
            >
                <template #item="{ element, index }">
                    <div class="sub-tree-container">
                        <v-tree-item
                            class="sub-tree-item"
                            :item="element"
                            :key="element.id"
                            @right-click-item="showContextMenu"
                            @set-filters="setFilters"
                            @on-merge-items="onMergeItems"
                            @on-drag-start="onDragStart"
                            :selectedItem="selectedItem"
                            :movingDirectoryId="movingDirectoryId"
                            :deletingDirectoryId="deletingDirectoryId"
                            :copyingDirectoryId="copyingDirectoryId"
                            :treeLocked="treeLocked"
                        ></v-tree-item>
                    </div>
                </template>
            </draggable>

            <!-- Asset -->
            <draggable
                id="assets-items"
                ghost-class="draggable-ghost"
                handle=".tree-container-assets-details"
                v-bind="{ animation: 200 }"
                :list="localAssets"
                item-key="id"
                :sort='false'
                group="itemsAssets"
                @start="onDragStart"
                @change="onMergeItems($event, item.id, 'asset')"
            >
                <template #item="{ element, index }">
                    <div>
                        <v-asset-item
                            :item="element"
                            @set-filters="setFilters"
                            @right-click-item="showContextMenu"
                            :selectedItem="selectedItem"
                            @on-merge-items="onMergeItems"
                            @on-drag-start="onDragStart"
                            :treeLocked="treeLocked"
                        />
                    </div>
                </template>
            </draggable>
        </div>
    </div>
    <!-- Directories -->
    <draggable 
        v-if="!isDirectory"
        id="child-tree-groups"
        class="mb-1 directoryItems ml-6"
        ghost-class="draggable-ghost"
        handle=".icon-dam-folder"
        v-bind="{animation: 200}"
        :list="item.children"
        item-key="id"
        :sort='false'
        group="directoryItems"
        @change="onMergeItems($event, item.id)"
        @start="onDragStart"
    >
        <template #item="{ element, index }">
            
        </template>
    </draggable>
    <!-- Asset -->
    <draggable
        v-if="!isAssets"
        id="assets-items"
        class="mb-1 itemsAssets ml-6"
        ghost-class="draggable-ghost"
        handle=".tree-container-assets-details"
        v-bind="{ animation: 200 }"
        :list="localAssets"
        item-key="id"
        :sort='false'
        group="itemsAssets"
        @change="onMergeItems($event, item.id, 'asset')"
        @start="onDragStart"
    >
        <template #item="{ element, index }">
            
        </template>
    </draggable>
</script>
<script type="module">
    app.component('v-tree-item', {
        template: "#v-item-template",
        props: {
            item: Object,
            selectedItem: Object,
            movingDirectoryId: {
                type: [String, Number],
                default: null,
            },
            deletingDirectoryId: {
                type: [String, Number],
                default: null,
            },
            copyingDirectoryId: {
                type: [String, Number],
                default: null,
            },
            treeLocked: {
                type: Boolean,
                default: false,
            },
        },
        data: function() {
            return {
                isOpen: false,
                showContextMenuFlag: false,
                contextMenuPosition: { x: 0, y: 0 },
                assetsLoaded: false,
                assetsLoading: false,
                assetsStale: false,
                // Local reactive asset list — own data, never reassigned, so
                // vuedraggable's Sortable stays bound to a stable array ref
                // for the lifetime of this component instance. Avoids the
                // race where the prop's `assets` is undefined at first paint
                // and the draggable orphans onto undefined.
                localAssets: [],
            };
        },
        mounted() {
            // Seed local list from prop if backend happened to include assets
            // (picker path with `with_assets=1`). Splice keeps the same ref.
            if (Array.isArray(this.item.assets) && this.item.assets.length) {
                this.localAssets.splice(0, 0, ...this.item.assets);
            }

            this.$emitter.on('current-item-expanded', (data) => {
                if (data.id !== this.item.id) return;
                this.isOpen = true;
                if (! this.assetsLoaded && ! this.assetsLoading) {
                    this.loadDirectoryAssets();
                }
            });

            this.$emitter.on('update-current-item', (data) => {
                if (data.id === this.item.id) this.item = data;
            });

            // `dirId === null` means "invalidate all"; otherwise scoped to id.
            this.$emitter.on('invalidate-dir-assets', (dirId) => {
                if (dirId == null || dirId == this.item.id) {
                    this.invalidateAssetCache();
                }
            });
        },
        watch: {
            // When parent reloads the tree (`loadDirectories`), this instance
            // may be reused for a different directory entirely (only with a
            // non-id key — id-based keys force re-mount). Reset flags and
            // empty the local list in place so vuedraggable's bound ref
            // survives.
            'item.id'() {
                this.assetsLoaded = false;
                this.assetsLoading = false;
                this.assetsStale = false;
                this.localAssets.splice(0, this.localAssets.length);
            },
        },
        computed: {
            isDirectory: function() {
                return this.item.children && Object.keys(this.item.children).length;
            },

            isAssets: function() {
                // True when the directory actually has assets to render:
                //   - lazy fetch resolved with at least one asset, or
                //   - backend hint `assets_count > 0`.
                return this.localAssets.length > 0
                    || (this.item.assets_count && this.item.assets_count > 0);
            },

            // Used to mount the inner wrapper so the asset drop target exists
            // even on empty leaf dirs that the user has expanded once.
            hasDropZone: function() {
                return this.isDirectory || this.isAssets || this.assetsLoaded;
            },

            isMoving: function() {
                return this.movingDirectoryId !== null
                    && this.item
                    && this.item.id == this.movingDirectoryId;
            },

            isDeleting: function() {
                return this.deletingDirectoryId !== null
                    && this.item
                    && this.item.id == this.deletingDirectoryId;
            },

            isCopying: function() {
                return this.copyingDirectoryId !== null
                    && this.item
                    && this.item.id == this.copyingDirectoryId;
            },

            isSelfBusy: function() {
                // True only when THIS directory has an active mutation (its own
                // delete/move/copy). Drives the per-node spinner so it shows on
                // the affected dir alone.
                return this.isMoving || this.isDeleting || this.isCopying;
            },

            isBusy: function() {
                // True when the row should be non-interactive — either this dir
                // is being mutated, or any other dir is (treeLocked broadcast).
                return this.isSelfBusy || this.treeLocked;
            },
        },
        methods: {
            onDragStart(event) {
                this.$emit("on-drag-start", event);
            },

            onMergeItems(event, id, type = 'directory') {
                this.$emit("on-merge-items", event, id, type);
            },

            toggle: function(item) {
                const willOpen = ! this.isOpen;
                if (this.isDirectory || this.isAssets || ! this.assetsLoaded) {
                    this.isOpen = willOpen;
                }

                if (willOpen && ! this.assetsLoaded && ! this.assetsLoading) {
                    this.loadDirectoryAssets();
                }

                this.$emit("set-filters", item);
            },

            // Replace contents of `localAssets` in place. vuedraggable's
            // Sortable holds the original array reference from mount; splice
            // keeps the same ref with new contents.
            replaceAssetsInPlace(fresh) {
                this.localAssets.splice(0, this.localAssets.length, ...fresh);
            },

            loadDirectoryAssets() {
                if (this.assetsLoading) {
                    this.assetsStale = true;
                    return;
                }
                this.assetsLoading = true;
                this.$axios
                    .get(`{{ route('admin.dam.directory.assets', ':id') }}`.replace(':id', this.item.id))
                    .then((response) => {
                        this.replaceAssetsInPlace(response.data.data || []);
                        this.assetsLoaded = true;
                        this.assetsLoading = false;
                        if (this.assetsStale) {
                            this.assetsStale = false;
                            this.loadDirectoryAssets();
                        }
                    })
                    .catch(() => {
                        this.assetsLoading = false;
                    });
            },

            // Auto-expand collapsed dir on dragenter so the inner asset draggable
            // mounts and can accept the drop. Mirrors Windows Explorer hover-expand.
            onDragEnter() {
                if (this.assetsLoading) return;
                if (! this.isOpen) {
                    this.isOpen = true;
                }
                if (! this.assetsLoaded) {
                    this.loadDirectoryAssets();
                }
            },

            invalidateAssetCache() {
                if (this.assetsLoading) {
                    this.assetsStale = true;
                    return;
                }
                this.assetsLoaded = false;
                if (this.isOpen) {
                    this.loadDirectoryAssets();
                }
            },

            setFilters: function(item, type = 'directory') {
                this.$emit("set-filters", item, type);
            },

            showContextMenu(event, item, type = 'directory') {
                event.stopPropagation();
                this.contextMenuPosition = {
                    x: event.pageX,
                    y: event.pageY
                };
                this.showContextMenuFlag = true;
                this.$emit("right-click-item", event, item, type); // Emit event for parent handling
            },

        }
    });
</script>
<script type="text/x-template" id="v-tree-view-template">
    <div
            class="relative"
            ref="treeContainer"
            v-if="formattedItems"
        >
            <!-- Move-in-flight overlay (directory or asset drag-move) -->
            <div
                v-if="moveStatusLabel"
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
                    <span class="text-base font-semibold text-gray-900 dark:text-white text-center break-words" v-text="moveStatusLabel"></span>
                </div>
            </div>
            <div
                class="tree-container text-nowrap overflow-hidden text-ellipsis"
                :class="treeBusy ? 'cursor-not-allowed' : ''"
            >
                <div
                    class="flex gap-1 w-full p-1 text-nowrap"
                    :class="treeBusy ? 'cursor-not-allowed opacity-60 pointer-events-none' : 'cursor-pointer'"
                    :aria-disabled="treeBusy"
                    @click.stop="treeBusy ? null : resetFilters(formattedItems[0])"
                    @contextmenu.prevent.stop="treeBusy ? null : showContextMenu($event, formattedItems[0])"
                >
                    <span>
                        <i class="icon-dam-folder text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>
                    </span>
                    <span 
                        class="text-sm text-nowrap overflow-hidden text-ellipsis"
                         :class="selectedItem && formattedItems[0].id == selectedItem.id ? 'text-violet-700 dark:text-violet-400 font-semibold' : 'text-zinc-600 dark:text-white'"
                    >
                        @{{ formattedItems[0].name }}
                    </span>
                </div>
                <draggable 
                    id="root-tree-groups"
                    ghost-class="draggable-ghost"
                    handle=".icon-dam-folder"
                    v-bind="{animation: 200}"
                    :list="formattedItems[0].children ?? []"
                    item-key="id"
                    :sort="false"
                    group="directoryItems"
                    @change="onMergeItems($event, formattedItems[0].id)"
                    @start="onDragStart"
                    v-if="formattedItems && formattedItems[0] && formattedItems[0].children.length > 0"
                >
                    <template #item="{ element, index }">
                        <div class="parent-tree-container ml-6">
                            <v-tree-item
                                class="item"
                                :item="element"
                                :key="element.id"
                                @right-click-item="showContextMenu"
                                @set-filters="setFilters"
                                @on-merge-items="onMergeItems"
                                @on-drag-start="onDragStart"
                                :selectedItem="selectedItem"
                                :movingDirectoryId="movingDirectoryId"
                                :deletingDirectoryId="deletingDirectoryId"
                                :copyingDirectoryId="copyingDirectoryId"
                                :treeLocked="treeBusy"
                            ></v-tree-item>
                        </div>
                    </template>
                </draggable>

                <draggable
                    id="assets-items"
                    ghost-class="draggable-ghost"
                    handle=".tree-container-assets-details"
                    v-bind="{ animation: 200 }"
                    :list="formattedItems[0].assets"
                    item-key="id"
                    :sort="false"
                    group="itemsAssets"
                    @start="onDragStart"
                    @change="onMergeItems($event, formattedItems[0].id, 'asset')"
                >
                    <template #item="{ element, index }">
                        <div class="ml-6">
                            <v-asset-item
                                :item="element"
                                @set-filters="setFilters"
                                @on-merge-items="onMergeItems"
                                @on-drag-start="onDragStart"
                                @right-click-item="showContextMenu"
                                :selectedItem="selectedItem"
                                :treeLocked="treeBusy"
                            />
                        </div>

                    </template>
                </draggable>
            </div>

            <!-- Context Menu -->
            <div v-if="showContextMenuFlag" 
                :style="{ top: `${contextMenuPosition.y}px`, left: `${contextMenuPosition.x}px` }" 
                class="absolute bg-white border border-gray-300 px-4 py-2 rounded shadow-lg z-50 dark:border-cherry-800 dark:bg-cherry-800 dark:text-white"
            >
                <div>
                    @if (bouncer()->hasPermission('dam.asset.upload'))
                     <div
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal"
                        @click="uploadFile"
                        v-if="requestType != 'asset' && canAccessSelected()"
                    >
                        <i class="icon-dam-upload text-sm text-zinc-600 dark:text-white"></i>
                        <input 
                            class="hidden" 
                            type="file"
                            ref="fileInput"
                            @change="handleFileChange"
                            multiple="multiple"
                            name="files[]"
                         />
                        <span class="text-sm text-zinc-600 dark:text-white"> @lang('dam::app.admin.dam.index.directory.actions.upload-files') </span>
                    </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.store'))
                        <div
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 !leading-normal dark:text-slate-300"
                            @click="createDirectory"
                            v-if="requestType != 'asset' && canAccessSelected()"
                        >
                            <i class="icon-dam-add-folder text-sm text-zinc-600 dark:text-white"></i>
                            <span class="text-sm text-zinc-600 dark:text-white"> @lang('dam::app.admin.dam.index.directory.actions.add-directory') </span>
                        </div>
                    @endif
                    <!-- @TODO: Feature Update -->
                    <!-- <div class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal" @click="copyDirectory">
                        <i class="icon-dam-copy"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.copy')</span>
                    </div>
                    <div class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal" @click="copyDirectory">
                        <i class="icon-dam-cut"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.cut')</span>
                    </div>
                    <div class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 !leading-normal dark:text-slate-300" @click="pasteDirectory">
                        <i class="icon-export"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.paste')</span>
                    </div> -->

                    @if (bouncer()->hasPermission('dam.directory.rename'))
                    <div
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal"
                        @click="renameItem"
                        v-if="requestType == 'directory' && canAccessSelected()"
                    >
                        <i class="icon-dam-rename"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.rename')</span>
                    </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.asset.rename'))
                        <div 
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal" 
                            @click="renameItem"
                            v-if="requestType == 'asset'"
                        >
                            <i class="icon-dam-rename"></i>
                            <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.rename')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.destroy'))
                    <div
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal"
                        @click="deleteItem"
                        v-if="requestType == 'directory' && canAccessSelected()"
                    >
                        <i class="icon-dam-delete"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.delete')</span>
                    </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                        <div 
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal" 
                            @click="deleteFile"
                            v-if="requestType == 'asset'"
                        >
                            <i class="icon-dam-delete"></i>
                            <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.delete')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.copy_structure'))
                        <div
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal"
                            @click="copyDirectory"
                            v-if="requestType != 'asset' && canAccessSelected()"
                        >
                            <i class="icon-dam-directory"></i>
                            <span class="text-sm text-zinc-600 dark:text-white text-nowrap">@lang('dam::app.admin.dam.index.directory.actions.copy-directory-structured')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.download_zip'))
                        <div
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 !leading-normal dark:text-slate-300"
                            @click="downloadItem('directory')"
                            v-if="requestType != 'asset' && canAccessSelected()"
                        >
                            <i class="icon-dam-zip"></i>
                            <span class="text-sm text-zinc-600  dark:text-white text-nowrap">@lang('dam::app.admin.dam.index.directory.actions.download-zip')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.asset.download'))
                    <div
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal"
                        @click="downloadItem('asset')"
                        v-if="requestType == 'asset'"
                    >
                        <i class="icon-import"></i>
                        <span class="text-sm text-zinc-600 dark:text-white text-nowrap">@lang('dam::app.admin.dam.index.directory.actions.download')</span>
                    </div>
                    @endif

                    <!-- Placeholder shown when the right-clicked dir is only
                         visible as an ancestor breadcrumb (not directly granted)
                         so every action above is hidden. Replaces the otherwise
                         empty floating box that looked like a glitch. -->
                    <div
                        v-if="requestType == 'directory' && ! canAccessSelected()"
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 text-sm text-zinc-500 dark:text-slate-400 italic !leading-normal cursor-default"
                    >
                        <span class="text-sm text-nowrap">@lang('dam::app.admin.permissions.no-actions')</span>
                    </div>
                </div>
            </div>
        </div>
        

        <!-- Create And Rename Directory Modal Form -->
        <x-admin::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
            ref="modalForm"
        >
            <form
                @submit="handleSubmit($event, createOrRenameDirectory)"
                ref="directoryCreateOrRenameForm"
            >
                <x-admin::modal ref="directoryCreateOrRenameModal" @toggle="focusNameInput">
                    <!-- Modal Header -->
                    <x-slot:header>
                        <p
                            class="text-lg text-gray-800 dark:text-white font-bold"
                            v-if="directoryCreate"
                        >
                            @lang('dam::app.admin.dam.index.directory.create.title')
                        </p>

                        <p
                            class="text-lg text-gray-800 dark:text-white font-bold"
                            v-else
                        >
                            @lang('dam::app.admin.dam.index.directory.rename.title')
                        </p>
                    </x-slot>

                    <!-- Modal Content -->
                    <x-slot:content>
                        {!! view_render_event('unopim.admin.dam.directory.create.before') !!}

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="parent_id"
                            v-model="directoryParentId"
                        />                        

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="id"
                            v-model="selectedItem.id"
                            v-if="!directoryCreate"
                        />

                        <!-- name -->
                        <x-admin::form.control-group v-if="directoryCreate">
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.index.directory.create.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                ref="nameInput"
                                rules="required"
                                :value="old('name')"
                                :label="trans('dam::app.admin.dam.index.directory.create.name')"
                                :placeholder="trans('dam::app.admin.dam.index.directory.create.name')"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group  v-if="!directoryCreate">
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.index.directory.create.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                ref="nameInput"
                                rules="required"
                                :value="old('name')"
                                v-model="directoryName"
                                :label="trans('dam::app.admin.dam.index.directory.create.name')"
                                :placeholder="trans('dam::app.admin.dam.index.directory.create.name')"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        {!! view_render_event('unopim.admin.dam.directory.create.after') !!}
                    </x-slot>

                    <!-- Modal Footer -->
                    <x-slot:footer>
                        <div class="flex gap-x-2.5 items-center">
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('dam::app.admin.dam.index.directory.create.save-btn')
                            </button>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </form>
        </x-admin::form>

        <!-- Asset Rename -->
        <x-admin::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
            ref="modalForm"
        >
            <form
                @submit="handleSubmit($event, renameAsset)"
                ref="assetRenameForm"
            >
                <x-admin::modal ref="assetRenameModal" @toggle="focusNameInput">
                    <!-- Modal Header -->
                    <x-slot:header>
                        <p
                            class="text-lg text-gray-800 dark:text-white font-bold"
                        >
                            @lang('dam::app.admin.dam.index.directory.asset.rename.title')
                        </p>
                    </x-slot>

                    <!-- Modal Content -->
                    <x-slot:content>
                        {!! view_render_event('unopim.admin.dam.asset.rename.before') !!}

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="id"
                            v-model="selectedItem.id"
                        />

                        <!-- name -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.index.directory.create.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="file_name"
                                ref="nameInput"
                                rules="required"
                                :value="old('file_name')"
                                v-model="assetName"
                                :label="trans('dam::app.admin.dam.index.directory.create.file_name')"
                                :placeholder="trans('dam::app.admin.dam.index.directory.create.file_name')"
                            />

                            <x-admin::form.control-group.error control-name="file_name" />
                        </x-admin::form.control-group>

                        {!! view_render_event('unopim.admin.dam.asset.rename.after') !!}
                    </x-slot>

                    <!-- Modal Footer -->
                    <x-slot:footer>
                        <div class="flex gap-x-2.5 items-center">
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('dam::app.admin.dam.index.directory.asset.rename.save-btn')
                            </button>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </form>
        </x-admin::form>

    </script>
<script type="module">
    app.component('v-tree-view', {
        template: '#v-tree-view-template',
        props: {
            src: {
                type: String,
                default: null,
            },
            aclBypass: {
                type: Boolean,
                default: false,
            },
            accessibleIds: {
                type: Array,
                default: () => [],
            },
        },
        data() {
            return {
                formattedItems: null,
                formattedValues: null,
                showContextMenuFlag: false,
                contextMenuPosition: {
                    x: 0,
                    y: 0
                },
                selectedItem: null,
                directoryName: null,
                assetName: null,
                selectedEvent: null,
                directoryCreate: false,
                directoryParentId: null,
                copyItem: null,
                requestType: null,
                parentItem: null,
                isLoading: false,
                actionStatus: null,
                dragStart: false,
                movingDirectoryId: null,
                deletingDirectoryId: null,
                copyingDirectoryId: null,
                gridBusy: false,
                moveStatusLabel: '',
            };
        },

        mounted() {
            this.$emitter.on('uploaded-assets', (data) => {
                this.setAssets(data);
            });

            this.$emitter.on('delete-assets', () => {
                // Mass-delete from grid — tree structure unchanged, refresh
                // asset caches without reloading the whole directory tree.
                this.invalidateAllAssetCaches();
            });

            // Grid-side mutations (upload in progress, mass-selection active)
            // freeze the tree so users can't move folders out from under an
            // in-flight grid action.
            this.$emitter.on('dam:grid-busy', (busy) => {
                this.gridBusy = !! busy;
            });

            this.loadDirectories();
        },

        computed: {
            // Aggregate "an async tree mutation is in flight" — drives the
            // grid lockout so user can't act on assets while a directory
            // delete/move/copy job is still running.
            treeMutating() {
                return !! (
                    this.deletingDirectoryId
                    || this.movingDirectoryId
                    || this.copyingDirectoryId
                );
            },
            // Tree row interaction lock — true when this side is mutating OR
            // grid is busy on the other side. Drives `treeLocked` prop chain.
            treeBusy() {
                return this.treeMutating || this.gridBusy;
            },
        },

        watch: {
            // Only broadcast TREE-side mutations to the grid. Including
            // `gridBusy` here would create a feedback loop: grid emits busy →
            // tree treeBusy=true → tree emits dam:tree-busy → grid locks
            // itself mid-upload.
            treeMutating(value) {
                this.$emitter.emit('dam:tree-busy', value);
            },
        },

        methods: {
            // Whether the currently right-clicked dir is directly granted to
            // the admin's role. Bypass roles (all/anonymous/API) always pass.
            // Used to hide upload/create/rename/delete/copy/zip context-menu
            // entries on ancestors that are tree-visible only.
            canAccessSelected() {
                if (this.aclBypass) return true;
                if (! this.selectedItem || this.selectedItem.id == null) return false;

                return this.accessibleIds.map(Number).includes(Number(this.selectedItem.id));
            },

            focusNameInput() {
                this.$nextTick(() => {
                    if (this.$refs.nameInput) {
                        this.$refs.nameInput.focus();
                        this.directoryParentId = this.directoryCreate ? this.selectedItem.id : this.selectedItem.parent_id;
                        this.directoryName = this.selectedItem.name || null;
                        this.assetName = this.selectedItem.file_name || null;
                    }
                });
            },
            showContextMenu(event, item, type = 'directory') {
                const menuWidth = 150;
                const menuHeight = 100;

                // Get the tree container's bounding rectangle
                const treeContainer = this.$refs.treeContainer.getBoundingClientRect();

                // Calculate the position relative to the container
                let x = event.clientX - treeContainer.left + 10; // Offset to the right
                let y = event.clientY - treeContainer.top;

                // Adjust for boundaries within the container
                if (x + menuWidth > treeContainer.width) {
                    x = treeContainer.width - menuWidth - 10; // Prevent overflow on the right
                }

                if (y + menuHeight > treeContainer.height) {
                    y = treeContainer.height - menuHeight - 10; // Prevent overflow on the bottom
                }

                this.contextMenuPosition = {
                    x,
                    y
                };

                this.selectedItem = item;
                this.selectedEvent = event;
                this.requestType = type;
                if (!this.isLoading) {
                    this.showContextMenuFlag = true;
                }
                document.addEventListener('click', this.closeContextMenu); // Close on click outside
            },

            closeContextMenu() {
                this.showContextMenuFlag = false;
                document.removeEventListener('click', this.closeContextMenu);
            },

            setFilters(item, type = "directory") {
                this.selectedItem = item;
                this.parentItem = item;

                if (item.hasOwnProperty('directories') || item.hasOwnProperty('directories')) {
                    this.parentItem = item.directories[0];
                }

                let column = type == 'directory' ? 'directory_id' : 'directory_asset_id';
                let value = [this.selectedItem.id];

                if (type == 'directory') {
                    value = [...value, ...this.findAllDirectoryIds(this.selectedItem)];
                }

                this.$emitter.emit('current-directory', this.parentItem);
                this.$emitter.emit('data-grid:reset-all-filters');
                this.$emitter.emit('data-grid:filter', {
                    column: {
                        column: column,
                        index: column
                    },
                    value
                });

                this.closeContextMenu();

                // @TODO:need to future implements
                // this.loadDirectoryChildrens();
            },

            resetFilters(item) {
                this.selectedItem = item;
                this.parentItem = item;
                this.$emitter.emit('data-grid:reset-all-filters');
                this.$emitter.emit('data-grid:refresh');
                this.$emitter.emit('current-directory', this.selectedItem);
                this.closeContextMenu();
            },

            findAllDirectoryIds(selectedItem) {
                let ids = [];

                function traverse(item) {
                    if (item.id) {
                        ids.push(item.id);
                    }

                    if (item.children && item.children.length > 0) {
                        item.children.forEach(child => traverse(child));
                    }
                }

                traverse(selectedItem);

                return ids;
            },

            createDirectory() {
                this.directoryCreate = true;
                this.$refs.directoryCreateOrRenameModal.toggle();
                this.closeContextMenu();
            },

            createOrRenameDirectory(params, {
                resetForm,
                setErrors
            }) {
                let formData = new FormData(this.$refs.directoryCreateOrRenameForm);
                this.$axios.post(this.directoryCreate ? "{{ route('admin.dam.directory.store') }}" : "{{ route('admin.dam.directory.update') }}", formData)
                    .then((response) => {
                        this.$refs.directoryCreateOrRenameModal?.close();
                        if (this.directoryCreate) {
                            if (!this.selectedItem.children) {
                                this.selectedItem.children = [];
                            }

                            this.selectedItem.children.push(response.data.data);

                        } else {
                            this.selectedItem = response.data.data;
                        }

                        this.loadDirectories();

                        this.$emitter.emit('current-item-expanded', this.selectedItem);

                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });

                        resetForm();
                    })
                    .catch(error => {
                        console.log(error, 'error');
                        if (error.response.status == 422) {
                            setErrors(error.response.data.errors);
                        }

                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response.data.message
                        });
                    });
            },

            renameItem() {
                if (this.requestType == 'asset') {
                    this.$refs.assetRenameModal.toggle();
                    this.closeContextMenu();

                    return;
                }

                this.directoryCreate = false;
                this.$refs.directoryCreateOrRenameModal.toggle();
                this.closeContextMenu();
            },

            renameAsset(params, {
                resetForm,
                setErrors
            }) {
                let formData = new FormData(this.$refs.assetRenameForm);
                this.$axios.post("{{ route('admin.dam.assets.rename') }}", formData)
                    .then((response) => {
                        this.$refs.assetRenameModal?.close();
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                        this.$emitter.emit('data-grid:refresh');
                        this.loadDirectories();
                        resetForm();
                    })
                    .catch(error => {
                        if (error.response.status == 422) {
                            setErrors(error.response.data.errors);
                        }

                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response.data.message
                        });
                    });
            },

            deleteItem() {
                this.$emitter.emit('open-delete-modal', {
                    message: "@lang('dam::app.admin.components.modal.confirm.message')",
                    agree: () => {
                        this.isLoading = true;
                        this.deletingDirectoryId = this.selectedItem.id;
                        this.$axios.delete(`{{ route('admin.dam.directory.destroy', ':id') }}`.replace(':id', this.selectedItem.id))
                            .then(response => {
                                this.parentItem = response.data.data;
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                setTimeout(() => {
                                    this.checkActionStatus('delete_directory');
                                }, 1000);

                            })
                            .catch((error) => {
                                this.isLoading = false;
                                this.deletingDirectoryId = null;
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    }
                });

                this.closeContextMenu();
            },

            deleteFile() {
                this.$emitter.emit('open-delete-modal', {
                    agree: () => {
                        this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', this.selectedItem.id))
                            .then(response => {
                                // Asset delete — tree structure unchanged, just
                                // refresh asset caches so the deleted asset
                                // disappears from the tree.
                                this.invalidateAllAssetCaches();

                                this.$emitter.emit('data-grid:refresh');

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                this.setFilters(this.parentItem);
                            })
                            .catch((error) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            });
                    }
                });

                this.closeContextMenu();
            },

            downloadItem(type = 'directory', ) {
                let downloadLink = '';
                if (type == 'directory') {
                    downloadLink = `{{ route('admin.dam.directory.zip_download', ':id') }}`.replace(':id', this.selectedItem.id);
                } else {
                    downloadLink = `{{ route('admin.dam.assets.download', ':id') }}`.replace(':id', this.selectedItem.id);
                }

                window.open(downloadLink, '_self');
            },

            copyDirectory() {
                this.isLoading = true;
                this.copyingDirectoryId = this.selectedItem.id;
                this.$axios.post("{{ route('admin.dam.directory.copy_structure') }}", this.selectedItem)
                    .then((response) => {
                        setTimeout(() => {
                            this.checkActionStatus('copy_directory_structure');
                        }, 1000);

                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                    })
                    .catch(error => {
                        this.isLoading = false;
                        this.copyingDirectoryId = null;
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response.data.message
                        });
                    });
            },

            pasteDirectory() {
                this.copyItem.parent_id = this.selectedItem.id;
                this.$axios.post("{{ route('admin.dam.directory.copy') }}", this.copyItem)
                    .then((response) => {
                        if (!this.selectedItem.children) {
                            this.selectedItem.children = [];
                        }

                        this.selectedItem.children.push(response.data.data);

                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                    })
                    .catch(error => {
                        console.log(error, 'error');
                        if (error.response.status == 422) {
                            setErrors(error.response.data.errors);
                        }
                    });
            },

            onDragStart(event) {
                this.dragStart = true;
            },

            onMergeItems(event, directoryId, type = 'directory') {
                const {
                    added,
                    removed
                } = event;

                if (this.isLoading) {
                    this.loadDirectories();
                    return;
                }

                if (!this.dragStart) {
                    this.loadDirectories();
                    return;
                }

                this.dragStart = false;
                
                let moved = added || removed;
                
                if (moved && type == 'directory') {
                    // @TODO: this is hot fixed, need to improve
                    let {parent} = this.findItemDirectoryById(this.formattedItems, moved.element.id);
                    if (parent) {
                        this.addedItems(moved.element, parent.id, type);
                    }
                }

                if (moved && type == 'asset') {
                    // Only the `added` half of the cross-list change (target
                    // dir gained the asset) drives the move. The `removed`
                    // half (source dir lost the asset) fires too — ignore it
                    // to avoid a duplicate API call.
                    // `directoryId` here is the target dir's id, supplied by
                    // the receiving draggable's `@change` binding.
                    if (added) {
                        this.addedItems(moved.element, directoryId, type);
                    }
                }
            },

            findItemDirectoryById(items, id, parent = null) {
                for (const item of items) {
                    if (item.id === id) {
                        return { item, parent };
                    }
                    
                    if (item.children && item.children.length > 0) {
                        const found = this.findItemDirectoryById(item.children, id, item);
                        if (found) {
                            return found;
                        }
                    }
                }

                return null; 
            },

            findItemAssetById(items, targetId, parent = null) {
                for (const item of items) {
                    const assetItem = (item.assets || []).find(obj => obj.id === targetId);
                    if (assetItem) {
                        return { item, parent };
                    }

                    if (item.children && item.children.length > 0) {
                        const found = this.findItemAssetById(item.children, targetId, item);
                        if (found) {
                            return found;
                        }
                    }
                }

                return null;
            },

            addedItems(item, moveTodirectoryId, type = 'directory') {
                this.isLoading = true;
                this.actionStatus = 'pending';

                const itemName = type == 'directory' ? (item.name ?? '') : (item.file_name ?? '');
                const labelTemplate = type == 'directory'
                    ? `@lang('dam::app.admin.dam.index.move.directory')`
                    : `@lang('dam::app.admin.dam.index.move.asset')`;
                this.moveStatusLabel = labelTemplate.replace(':name', itemName);

                if (type == 'directory') {
                    this.movingDirectoryId = item.id;
                }

                this.$axios.post(type == 'directory' ? "{{ route('admin.dam.directory.moved') }}" : "{{ route('admin.dam.assets.moved') }}", {
                        new_parent_id: moveTodirectoryId,
                        move_item_id: item.id,
                    })
                    .then((response) => {
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });

                        if (type == 'directory') {
                            setTimeout(() => {
                                this.checkActionStatus('move_directory_structure');
                            }, 1000);
                        } else {
                            this.isLoading = false;
                            this.actionStatus = null;
                            this.moveStatusLabel = '';
                            // Asset drag-move — refresh source and target asset
                            // caches; tree structure unchanged so no need to
                            // reload the directory list.
                            this.invalidateAllAssetCaches();
                        }
                    })
                    .catch(error => {
                        this.isLoading = false;
                        this.actionStatus = null;
                        this.movingDirectoryId = null;
                        this.moveStatusLabel = '';
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response.data.message
                        });

                        this.loadDirectories();
                    });
            },

            uploadFile() {
                this.$refs.fileInput.click();
            },

            handleFileChange(event) {
                const fileInput = event.target.files;
                if (! fileInput || fileInput.length === 0) return;

                const formData = new FormData();
                for (let i = 0; i < fileInput.length; i++) {
                    formData.append('files[]', fileInput[i]);
                }
                formData.append('directory_id', this.selectedItem.id);

                // Route through v-dam-upload's pipeline so the upload spinner,
                // cancel button, and error/large-file handling kick in. Owning
                // the axios call here would skip those affordances.
                this.$emitter.emit('dam:upload-files', formData);

                event.target.value = null;
            },

            setAssets(data) {
                if (! this.selectedItem.assets) this.selectedItem.assets = [];
                if (! this.selectedItem.children) this.selectedItem.children = [];

                // Asset upload — tree structure unchanged, just refresh the
                // target dir's lazy-loaded asset cache. Root is handled below
                // via the same broadcast, since root assets are managed by
                // this component (not a v-tree-item).
                this.invalidateDirAssetCache(this.selectedItem.id);

                this.$nextTick(() => {
                    this.$emitter.emit('current-item-expanded', this.selectedItem);
                    this.$emitter.emit('current-directory', this.selectedItem);
                    this.setFilters(this.selectedItem);
                });
            },

            isRootDir(id) {
                return this.formattedItems
                    && this.formattedItems[0]
                    && id == this.formattedItems[0].id;
            },

            invalidateDirAssetCache(dirId) {
                if (this.isRootDir(dirId)) {
                    this.loadRootAssets();
                } else {
                    this.$emitter.emit('invalidate-dir-assets', dirId);
                }
            },

            invalidateAllAssetCaches() {
                // Broadcast to every v-tree-item (id=null = match all) and
                // refresh root's own list.
                this.$emitter.emit('invalidate-dir-assets', null);
                this.loadRootAssets();
            },

            loadDirectories() {
                this.$axios.get("{{ route('admin.dam.directory.index') }}")
                        .then((response) => {
                            const tree = response.data.data;

                            // Default Root.assets to an empty array synchronously
                            // so the root `<draggable :list>` binds to a valid
                            // array; without this, vuedraggable wires up against
                            // `undefined` for the brief window before
                            // `loadRootAssets` resolves and never re-binds when
                            // the array later replaces undefined — leaving the
                            // root asset list blank until a manual page reload.
                            if (tree && tree[0] && ! Array.isArray(tree[0].assets)) {
                                tree[0].assets = [];
                            }

                            this.formattedItems = tree;

                            this.$nextTick(() => {
                                if (this.selectedItem) {
                                    this.$emitter.emit('current-item-expanded', this.selectedItem);
                                    this.setFilters(this.selectedItem);
                                } else {
                                    this.setDefaultSeletedItem();
                                }

                                if (this.formattedItems && this.formattedItems[0]) {
                                    this.loadRootAssets();
                                }
                            });
                        })
                        .catch((error) => {
                            console.error('Error fetching directories:', error);
                        });
            },

            loadRootAssets() {
                const root = this.formattedItems[0];
                if (! root) return;
                this.$axios
                    .get(`{{ route('admin.dam.directory.assets', ':id') }}`.replace(':id', root.id))
                    .then((response) => {
                        const fresh = response.data.data || [];
                        // Mutate in place so vuedraggable's Sortable instance,
                        // which holds the original array reference from initial
                        // mount, sees the new contents. Reassigning `root.assets`
                        // creates a new array that the already-mounted Sortable
                        // does not pick up.
                        if (Array.isArray(root.assets)) {
                            root.assets.splice(0, root.assets.length, ...fresh);
                        } else {
                            root.assets = fresh;
                        }
                    })
                    .catch(() => {});
            },

            loadDirectoryChildrens() {
                this.$axios.get(`{{ route('admin.dam.directory.children', ':id') }}`.replace(':id', this.parentItem.id))
                    .then((response) => {
                        this.selectedItem = response.data.data;
                    })
                    .catch((error) => {
                        console.error('Error fetching directory children:', error);
                    });
            },
            // @TODO: need to future implements this method
            loadDirectoryAssets() {
                this.$axios.get(`{{ route('admin.dam.directory.assets', ':id') }}`.replace(':id', this.parentItem.id))
                    .then((response) => {
                        this.parentItem.assets = response.data.data;
                    })
                    .catch((error) => {
                        console.error('Error fetching directory children:', error);
                    });
            },

            setDefaultSeletedItem() {
                if (!this.parentItem) {
                    this.selectedItem = this.formattedItems[0];
                    this.parentItem = this.formattedItems[0];
                }

                this.$emitter.emit('current-directory', this.selectedItem);
            },

            getDatagrids() {
                let datagrids = localStorage.getItem(
                    'datagrids'
                );

                return JSON.parse(datagrids) ?? [];
            },

            checkActionStatus(action) {
                if (this.actionStatus == 'completed') {
                    setTimeout(() => {
                        this.goForNextAction(action);
                        this.selectedItem = this.parentItem
                        this.$emitter.emit('data-grid:reset-all-filters');
                        this.setFilters(this.parentItem);

                        if (action == 'move_directory_structure') {
                            this.movingDirectoryId = null;
                            this.moveStatusLabel = '';
                        }

                        if (action == 'delete_directory') {
                            this.deletingDirectoryId = null;
                        }

                        if (action == 'copy_directory_structure') {
                            this.copyingDirectoryId = null;
                        }
                    }, 1000);

                    this.isLoading = false;
                    this.actionStatus = null;

                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: 'Action completed successfully'
                    });

                    return true;
                }
                this.$axios.get(`{{ route('admin.dam.action_request.status', ':eventType') }}`.replace(':eventType', action))
                    .then((response) => {
                        this.actionStatus = response.data.status;

                        if (this.actionStatus == 'failed') {
                                this.$emitter.emit('add-flash', { type: 'error', message: response.data.message });
                                this.isLoading = false;
                                if (action == 'move_directory_structure') {
                                    this.movingDirectoryId = null;
                                }

                                if (action == 'delete_directory') {
                                    this.deletingDirectoryId = null;
                                }

                                if (action == 'copy_directory_structure') {
                                    this.copyingDirectoryId = null;
                                }

                                return;
                            }


                        if (this.actionStatus == 'error') {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: response.data.message
                            });
                            this.isLoading = false;
                            if (action == 'move_directory_structure') {
                                this.movingDirectoryId = null;
                                this.moveStatusLabel = '';
                            }

                            if (action == 'delete_directory') {
                                this.deletingDirectoryId = null;
                            }

                            if (action == 'copy_directory_structure') {
                                this.copyingDirectoryId = null;
                            }
                            this.goForNextAction(action);
                            return;
                        }

                        setTimeout(() => {
                            this.checkActionStatus(action);
                        }, 2000);
                    })
                    .catch((error) => {
                        this.isLoading = false;
                        if (action == 'move_directory_structure') {
                            this.movingDirectoryId = null;
                            this.moveStatusLabel = '';
                        }

                        if (action == 'delete_directory') {
                            this.deletingDirectoryId = null;
                        }

                        if (action == 'copy_directory_structure') {
                            this.copyingDirectoryId = null;
                        }
                    });
            },

            goForNextAction(action) {
                if (
                    action == 'delete_directory'
                    || action == 'copy_directory_structure'
                    || action == 'move_directory_structure'
                ) {
                    this.loadDirectories();
                }
            }
        },
    });
</script>
@endPushOnce
