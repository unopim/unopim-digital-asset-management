<x-dam::tree.asset-count-badge />

<!-- Panel -->
<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow w-[360px]">
    <!-- Panel Header -->
    <p class="flex justify-between text-base text-gray-800 dark:text-white font-semibold mb-4">
        @lang('dam::app.admin.dam.index.directory.title')
    </p>

    <!-- Panel Content -->
    <div class="mb-5 text-sm text-gray-600 dark:text-gray-300 flex flex-col gap-2">
        <x-dam::tree.search />

        <v-directory-tree
            :show-assets="{{ config('dam.tree.show_assets') ? 'true' : 'false' }}"
        >
            <x-admin::shimmer.tree />
        </v-directory-tree>

    </div>
</div>

@pushOnce('scripts')
<script>
    window.__damTreeShowAssets = {{ config('dam.tree.show_assets') ? 'true' : 'false' }};
</script>
    <script
        type="text/x-template"
        id="v-directory-tree-template"
    >
    <div 
            class="relative" 
            ref="treeContainer"
            v-if="formattedItems"
        >
            <div class="tree-container text-nowrap overflow-auto" style="max-height: 480px; max-width: 100%;">
                <div
                    class="flex gap-1 w-full p-1 text-nowrap cursor-pointer"
                    :data-dir-id="formattedItems[0].id"
                    @click="setFilters(formattedItems[0])"
                >
                    <span>
                        <i class="icon-dam-folder text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>
                    </span>
                    <span
                        class="text-sm text-nowrap overflow-hidden text-ellipsis"
                         :class="selectedItem && formattedItems[0].id == selectedItem.id ? 'text-violet-700 dark:text-violet-400 font-semibold' : 'text-zinc-600 dark:text-gray-300'"
                    >
                        @{{ formattedItems[0].name }}
                    </span>
                    <v-asset-count-badge :count="formattedItems[0].assets_total_count ?? 0" />
                </div>
                <div v-for="(asset, index) in formattedItems[0].children">
                    <div class="flex parent-tree-container ml-6">
                        <v-directory-tree-item
                            class="item"
                            :item="asset"
                            :key="index"
                            :selectedItem="selectedItem"
                            :show-assets="showAssets"
                            @set-filters="setFilters"
                        />
                    </div>
                </div>

                <div
                    v-if="showAssets"
                    class="pt-1 ltr:pl-3 ltr:pr-10"
                    v-for="(asset, index) in formattedItems[0].assets"
                >
                    <div class="flex ml-6">
                        <v-directory-tree-asset-item
                            :item="asset"
                            @set-filters="setFilters"
                            :selectedItem="selectedItem"
                        />
                    </div>
                </div>
            </div>


            <!-- Show loader -->
             <div 
                v-if="isLoading" 
                :style="{ top: `${contextMenuPosition.y}px`, left: `${contextMenuPosition.x}px` }"
                class="absolute z-50"
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
    </script>

    <script type="module">
        app.component('v-directory-tree', {
            template: '#v-directory-tree-template',

            props: {
                showAssets: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    isLoading: true,

                    directories: [],

                    formattedItems: null,
                    selectedItem: null,
                    parentItem: null,
                    expandRequested: new Set(),
                }
            },

            mounted() {
                this.get();
                this.$emitter.on('dam:reveal-directory', ({ id }) => this.revealDirectory(id));
            },

            methods: {
                get() {
                    const params = this.showAssets ? { with_assets: 1 } : {};
                    this.$axios.get("{{ route('admin.dam.directory.index') }}", { params })
                       .then((response) => {
                            this.isLoading = false;

                            this.formattedItems = response.data.data;
                            this.setDefaultSeletedItem();
                        })
                       .catch((error) => {
                            console.error('Error fetching directories:', error);
                        });
                },

                setDefaultSeletedItem() {
                    if (!this.parentItem) {
                        this.selectedItem = this.formattedItems[0];
                        this.parentItem = this.formattedItems[0];
                    }

                    // Skip setFilters — datagrid does its own ACL-scoped initial fetch.
                    this.$emitter.emit('current-directory', this.selectedItem);
                },

                setFilters(item, type = "directory") {
                    this.selectedItem = item;

                    this.parentItem = item.hasOwnProperty('directories') ? item.directories[0] : item;

                    let column = type == 'directory' ? 'directory_id' : 'directory_asset_id';
                    let value = [this.selectedItem.id];

                    this.$emitter.emit('current-directory', this.selectedItem);
                    this.$emitter.emit('data-grid:reset-all-filters');
                    this.$emitter.emit('data-grid:filter', { column: {column: column, index: column}, value});
                },

                findPathToDirectory(id) {
                    const root = this.formattedItems && this.formattedItems[0];
                    if (! root) return null;

                    const stack = [[root, [root]]];
                    while (stack.length) {
                        const [node, path] = stack.pop();
                        if (Number(node.id) === Number(id)) {
                            return path;
                        }
                        for (const child of (node.children || [])) {
                            stack.push([child, [...path, child]]);
                        }
                    }
                    return null;
                },

                async revealDirectory(id) {
                    const path = this.findPathToDirectory(id);
                    if (! path) {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: "@lang('dam::app.admin.dam.index.directory.search.not-found-flash')",
                        });
                        return;
                    }

                    // Ask each ancestor item to open. v-directory-tree-item listens
                    // for 'picker:expand-directory' on $emitter and toggles isOpen.
                    for (let i = 1; i < path.length; i++) {
                        this.$emitter.emit('picker:expand-directory', { id: path[i].id });
                    }

                    await this.$nextTick();
                    await this.$nextTick();

                    const target = path[path.length - 1];
                    const el = this.$refs.treeContainer
                        && this.$refs.treeContainer.querySelector(`[data-dir-id="${target.id}"]`);
                    if (el && typeof el.scrollIntoView === 'function') {
                        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }

                    this.setFilters(target);
                },

                findAllDirectoryIds(selectedItem){
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
            }
        });
    </script>

    <script type="text/x-template" id="v-directory-tree-item-template">
        <div class="tree-container-details">
            <div
                class="flex gap-1 w-full p-1 text-nowrap cursor-pointer"
                :data-dir-id="item.id"
                @click.stop="toggle(item)"
            >
                <span
                    class="text-xl text-zinc-600 dark:text-gray-300"
                    v-if="isFolder || isAssets"
                    :class="isOpen ? 'icon-dam-close' : 'icon-dam-open'"
                >
                </span>
                <span
                    class="text-sm flex items-center gap-1"
                    :class="selectedItem && item.id == selectedItem.id ? 'text-violet-700 dark:text-violet-400 font-semibold' : 'text-zinc-600 dark:text-gray-300'"
                >
                    <i class="icon-dam-folder text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white"></i>

                    @{{ item.name }}
                </span>
                <v-asset-count-badge :count="item.assets_total_count ?? 0" />
            </div>
            <div
                v-show="isOpen"
                v-if="isFolder || isAssets"
                class="flex flex flex-col pl-4"
            >
                <!-- Directories -->
                <div class="flex sub-tree-container gap-2 py-1 ltr:pl-3 ltr:pr-10" v-for="(asset, index) in item.children">
                    <v-directory-tree-item
                        class="sub-tree-item"
                        :item="asset"
                        :key="index"
                        :selectedItem="selectedItem"
                        :show-assets="showAssets"
                        @set-filters="setFilters"
                    ></v-directory-tree-item>
                </div>

                <!-- Asset -->
                <div
                    v-if="showAssets"
                    class="flex py-1 ltr:pl-3 ltr:pr-10"
                    v-for="(asset, index) in item.assets"
                >
                    <v-directory-tree-asset-item
                        :item="asset"
                        :selectedItem="selectedItem"
                        @set-filters="setFilters"
                    />
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-directory-tree-item', {
            template: "#v-directory-tree-item-template",

            props: {
                item: Object,
                selectedItem: Object,
                showAssets: {
                    type: Boolean,
                    default: false,
                },
            },

            computed: {
                isFolder: function() {
                    return this.item.children && Object.keys(this.item.children).length;
                },

                isAssets: function() {
                    if (! this.showAssets) return false;
                    return this.item.assets && Object.keys(this.item.assets).length;
                }
            },

            data() {
                return {
                    assetItem: this.item,
                    isOpen: false
                };
            },

            mounted() {
                this.$emitter.on('picker:expand-directory', ({ id }) => {
                    if (Number(id) === Number(this.item.id) && (this.isFolder || this.isAssets)) {
                        this.isOpen = true;
                    }
                });
            },

            methods: {
                setFilters(item, type = 'directory') {
                    this.$emit("set-filters", item, type);
                },

                toggle: function(item) {
                    if (this.isFolder || this.isAssets) {
                        this.isOpen = !this.isOpen;
                    }

                    this.setFilters(item);
                },
            },
        });
    </script>


    <script
        type="text/x-template"
        id="v-directory-tree-asset-item-template"
    >
        <div 
            class="tree-container-assets-details"
            @click.stop="setFilters(item)"
        >    
            <div
                class="flex gap-1 w-full p-1 cursor-pointer"
            >
                <span>
                    <i 
                        class="text-xl transition-all group-hover:text-gray-800 dark:text-gray-300 dark:group-hover:text-white cursor-grab"
                        :class="getFileTypeIcon(item)"
                    ></i>
                </span>
                <span 
                    class="text-sm"
                    :class="selectedItem && selectedItem.file_name && item.id == selectedItem.id ? 'text-violet-700 dark:text-violet-400 font-semibold' : 'text-zinc-600 dark:text-gray-300'"
                >
                    @{{ item.file_name }}
                </span>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-directory-tree-asset-item', {
            template: "#v-directory-tree-asset-item-template",

            props: ['item', 'selectedItem'],

            methods: {
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

                setFilters(item) {
                    this.$emit("set-filters", item, 'asset');
                },
            }
        });
    </script>
@endpushOnce
