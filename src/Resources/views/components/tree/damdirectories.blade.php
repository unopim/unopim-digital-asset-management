<v-tree-view>
    <x-admin::shimmer.tree />
</v-tree-view>

@pushOnce('scripts')
<!-- asset name template -->
<script type="text/x-template" id="v-asset-item-template">
    <div 
        class="tree-container-assets-details" 
    >    
        <div
            class="flex gap-1 w-full p-1 cursor-pointer"
            @click.stop="setFilters(item)"
            @contextmenu.prevent.stop="showContextMenu($event, item)"
        >
            <span>
                <i 
                    class="text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"
                    :class="getFileTypeIcon(item)"
                ></i>
            </span>
            <span 
                class="text-sm dark:text-white"
                :class="selectedItem && selectedItem.file_name && item.id == selectedItem.id ? 'text-violet-700' : 'text-zinc-600'"
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
            class="flex gap-1 w-full pl-1 pt-1 text-nowrap cursor-pointer"
            @click.stop="toggle(item)"
            @contextmenu.prevent.stop="showContextMenu($event, item)"
        >
            <span 
                class="text-xl text-zinc-600 dark:text-white"
                v-if="isDirectory || isAssets"
                :class="isOpen ? 'icon-dam-close' : 'icon-dam-open'"
            >
            </span>
            <span>
                <i class="icon-dam-folder text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>
            </span>
            <span 
                class="text-sm dark:text-white"
                :class="selectedItem && item.id == selectedItem.id ? 'text-violet-700' : 'text-zinc-600'"
            >@{{ item?.name }}   </span>
        </div>
        <div 
            v-show="isOpen" 
            v-if="isDirectory || isAssets"
            class="flex flex flex-col pl-6"
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
                            :key="index"
                            @right-click-item="showContextMenu"
                            @set-filters="setFilters"
                            @on-merge-items="onMergeItems"
                            @on-drag-start="onDragStart"
                            :selectedItem="selectedItem"
                        ></v-tree-item>
                    </div>
                </template>
            </draggable>

            <!-- Asset -->
            <draggable 
                id="assets-items"
                ghost-class="draggable-ghost"
                handle=".tree-container-assets-details"
                v-bind="{animation: 200}"
                :list="item.assets"
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
        v-bind="{animation: 200}"
        :list="item.assets"
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
        },
        data: function() {
            return {
                isOpen: false,
                showContextMenuFlag: false, // To control menu visibility
                contextMenuPosition: {
                    x: 0,
                    y: 0
                }, // Store menu position
                // selectedItem: null, // To store selected item
            };
        },
        mounted() {
            this.$emitter.on('current-item-expanded', (data) => {
                if (data.id === this.item.id) {
                    this.isOpen = true;
                }
            });

            this.$emitter.on('update-current-item', (data) => {
                if (data.id === this.item.id) {
                    this.item = data;
                }
            });
        },
        computed: {
            isDirectory: function() {
                return this.item.children && Object.keys(this.item.children).length;
            },

            isAssets: function() {
                return this.item.assets && Object.keys(this.item.assets).length;
            }
        },
        methods: {
            onDragStart(event) {
                this.$emit("on-drag-start", event);
            },

            onMergeItems(event, id, type = 'directory') {
                this.$emit("on-merge-items", event, id, type);
            },

            toggle: function(item) {
                if (this.isDirectory || this.isAssets) {
                    this.isOpen = !this.isOpen;
                }

                this.$emit("set-filters", item);
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
            <div class="tree-container text-nowrap overflow-hidden text-ellipsis">
                <div
                    class="flex gap-1 w-full p-1 text-nowrap cursor-pointer"
                    @click.stop="resetFilters(formattedItems[0])"
                    @contextmenu.prevent.stop="showContextMenu($event, formattedItems[0])"
                >
                    <span>
                        <i class="icon-dam-folder text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>
                    </span>
                    <span 
                        class="text-sm text-nowrap overflow-hidden text-ellipsis dark:text-white"
                         :class="selectedItem && formattedItems[0].id == selectedItem.id ? 'text-violet-700' : 'text-zinc-600'"
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
                                :key="index"
                                @right-click-item="showContextMenu"
                                @set-filters="setFilters"
                                @on-merge-items="onMergeItems"
                                @on-drag-start="onDragStart"
                                :selectedItem="selectedItem"
                            ></v-tree-item>
                        </div>
                    </template>
                </draggable>

                <draggable 
                    id="assets-items"
                    ghost-class="draggable-ghost"
                    handle=".tree-container-assets-details"
                    v-bind="{animation: 200}"
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
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                        @click="uploadFile"
                        v-if="requestType != 'asset'"
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
                            v-if="requestType != 'asset'"
                        >
                            <i class="icon-dam-add-folder text-sm text-zinc-600 dark:text-white"></i>
                            <span class="text-sm text-zinc-600 dark:text-white"> @lang('dam::app.admin.dam.index.directory.actions.add-directory') </span>
                        </div>
                    @endif
                    <!-- @TODO: Feature Update -->
                    <!-- <div class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" @click="copyDirectory">
                        <i class="icon-dam-copy"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.copy')</span>
                    </div>
                    <div class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" @click="copyDirectory">
                        <i class="icon-dam-cut"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.cut')</span>
                    </div>
                    <div class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 !leading-normal dark:text-slate-300" @click="pasteDirectory">
                        <i class="icon-export"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.paste')</span>
                    </div> -->

                    @if (bouncer()->hasPermission('dam.directory.rename'))
                    <div 
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                        @click="renameItem"
                        v-if="requestType == 'directory'"
                    >
                        <i class="icon-dam-rename"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.rename')</span>
                    </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.asset.rename'))
                        <div 
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                            @click="renameItem"
                            v-if="requestType == 'asset'"
                        >
                            <i class="icon-dam-rename"></i>
                            <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.rename')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.destroy'))
                    <div 
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                        @click="deleteItem"
                        v-if="requestType == 'directory'"
                    >
                        <i class="icon-dam-delete"></i>
                        <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.delete')</span>
                    </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                        <div 
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                            @click="deleteFile"
                            v-if="requestType == 'asset'"
                        >
                            <i class="icon-dam-delete"></i>
                            <span class="text-sm text-zinc-600 dark:text-white">@lang('dam::app.admin.dam.index.directory.actions.delete')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.copy_structure'))
                        <div 
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                            @click="copyDirectory"
                            v-if="requestType != 'asset'"
                        >
                            <i class="icon-dam-directory"></i>
                            <span class="text-sm text-zinc-600 dark:text-white text-nowrap">@lang('dam::app.admin.dam.index.directory.actions.copy-directory-structured')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.directory.download_zip'))
                        <div 
                            class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                            @click="downloadItem('directory')"
                            v-if="requestType != 'asset'"
                        >
                            <i class="icon-dam-zip"></i>
                            <span class="text-sm text-zinc-600  dark:text-white text-nowrap">@lang('dam::app.admin.dam.index.directory.actions.download-zip')</span>
                        </div>
                    @endif

                    @if (bouncer()->hasPermission('dam.asset.download'))
                    <div 
                        class="flex items-center justify-start rounded-md p-1.5 gap-2 cursor-pointer text-sm text-zinc-600 dark:text-white !leading-normal dark:text-slate-300" 
                        @click="downloadItem('asset')"
                        v-if="requestType == 'asset'"
                    >
                        <i class="icon-import"></i>
                        <span class="text-sm text-zinc-600 dark:text-white text-nowrap">@lang('dam::app.admin.dam.index.directory.actions.download')</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Show loader -->
             <div 
                v-if="isLoading" 
                :style="{ top: `${contextMenuPosition.y}px`, left: `${contextMenuPosition.x}px` }"
                class="absolute z-50 dark:text-white"
            >
                <!-- Spinner -->
                <svg class="align-center inline-block animate-spin h-5 w-5 ml-2 text-white-700" xmlns="http://www.w3.org/2000/svg" fill="none"  aria-hidden="true" viewBox="0 0 24 24">
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    >
                    </circle>

                    <path
                        class="opacity-75"
                        fill="#8A2BE2"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    >
                    </path>
                </svg>
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
        props: ['src'],
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
            };
        },

        mounted() {
            this.$emitter.on('uploaded-assets', (data) => {
                this.setAssets(data);
            });

            this.$emitter.on('delete-assets', (data) => {
                // @TODO: Need to implement in future
                // if (data.actionType == 'single-action') {
                //     this.setFilters(this.parentItem);
                // }

                this.loadDirectories()
            });

            this.loadDirectories();
        },

        methods: {
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
                        this.$refs.directoryCreateOrRenameModal.close();
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
                        this.$refs.assetRenameModal.close();
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
                        this.$axios.delete(`{{ route('admin.dam.directory.destroy', '') }}/${this.selectedItem.id}`)
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
                        this.$axios.delete(`{{ route('admin.dam.assets.destroy', '') }}/${this.selectedItem.id}`)
                            .then(response => {
                                this.loadDirectories();

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
                    downloadLink = `{{ route('admin.dam.directory.zip_download', '') }}/${this.selectedItem.id}`;
                } else {
                    downloadLink = `{{ route('admin.dam.assets.download', '') }}/${this.selectedItem.id}`;

                }

                window.open(downloadLink, '_self');
            },

            copyDirectory() {
                this.isLoading = true;
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

                if (moved  && type == 'asset') {
                    // @TODO: this is hot fixed, need to improve
                    let {item} = this.findItemAssetById(this.formattedItems, moved.element.id);
                    if (parent) {
                        this.addedItems(moved.element, item.id, type);
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
                    const assetItem = item.assets.find(obj => obj.id === targetId);
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
                this.$axios.post(type == 'directory' ? "{{ route('admin.dam.directory.moved') }}" : "{{ route('admin.dam.assets.moved') }}", {
                        new_parent_id: moveTodirectoryId,
                        move_item_id: item.id,
                    })
                    .then((response) => {
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                        this.loadDirectories();
                    })
                    .catch(error => {
                        this.isLoading = false;
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
                let fileInput = event.target.files;
                if (fileInput.length > 0) {
                    let formData = new FormData();

                    for (let index = 0; index < fileInput.length; index++) {
                        formData.append('files[]', fileInput[index]);
                    }

                    formData.append('directory_id', this.selectedItem.id);

                    this.$axios.post("{{ route('admin.dam.assets.upload') }}", formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        }
                    }).then((response) => {
                        this.$emitter.emit('uploaded-assets', response.data.files);
                        this.$emitter.emit('data-grid:refresh');
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                    }).catch((error) => {
                        console.log(error);
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error.response.data.message
                        });
                        console.error('Upload failed:', error);
                    });
                }
            },

            setAssets(data) {
                if (!this.selectedItem.assets) {
                    this.selectedItem.assets = [];
                }

                if (!this.selectedItem.children) {
                    this.selectedItem.children = [];
                }

                this.selectedItem.assets = [...this.selectedItem.assets, ...data];


                this.setFilters(this.selectedItem);

                this.$emitter.emit('current-item-expanded', this.selectedItem);
            },

            loadDirectories() {
                this.$axios.get("{{ route('admin.dam.directory.index') }}")
                    .then((response) => {
                        this.formattedItems = response.data.data;
                        this.setDefaultSeletedItem();
                    })
                    .catch((error) => {
                        console.error('Error fetching directories:', error);
                    });
            },

            loadDirectoryChildrens() {
                this.$axios.get(`{{ route('admin.dam.directory.children', '') }}/${this.parentItem.id}`)
                    .then((response) => {
                        this.selectedItem = response.data.data;
                    })
                    .catch((error) => {
                        console.error('Error fetching directory children:', error);
                    });
            },
            // @TODO: need to future implements this method
            loadDirectoryAssets() {
                this.$axios.get(`{{ route('admin.dam.directory.assets', '') }}/${this.parentItem.id}`)
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
                    }, 1000);

                    this.isLoading = false;

                    this.$emitter.emit('add-flash', {
                        type: 'success',
                        message: 'Action completed successfully'
                    });

                    return true;
                }

                this.$axios.get(`{{ route('admin.dam.action_request.status', '') }}/${action}`)
                    .then((response) => {
                        this.actionStatus = response.data.status;

                        if (this.actionStatus == 'failed') {
                                this.$emitter.emit('add-flash', { type: 'error', message: response.data.message });
                                this.isLoading = false;

                                return;
                            }


                        if (this.actionStatus == 'error') {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: response.data.message
                            });
                            this.isLoading = false;
                            this.goForNextAction(action);
                            return;
                        }

                        setTimeout(() => {
                            this.checkActionStatus(action);
                        }, 2000);
                    })
                    .catch((error) => {
                        this.isLoading = false;
                    });
            },

            goForNextAction(action) {
                if (action == 'delete_directory' || action == 'copy_directory_structure') {
                    this.loadDirectories();
                }
            }
        },
    });
</script>
@endPushOnce
