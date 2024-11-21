@props([
    'name'        => 'assets',
    'assetValues' => [],
    'width'       => '120px',
    'height'      => '120px'
])

<v-asset-field
    name="{{ $name }}"
    asset-values="{{ (is_array($assetValues) ? implode(',', $assetValues) : $assetValues) }}"
    width="{{ $width }}"    
    height="{{ $height }}"
    :errors="errors"
>
    <x-admin::shimmer.image class="w-[110px] h-[110px] rounded" />
</v-asset-field>

@pushOnce('scripts')
    <script type="text/x-template" id="v-asset-field-template">
        <!-- Panel Content -->
        <div class="grid">
            <x-admin::shimmer.image class="w-[110px] h-[110px] rounded" v-if="isLoading" />

            <div class="flex flex-wrap gap-3" v-else>
                <input type="hidden" :name="name + '[]'" value="" v-if="assets.length === 0">

                <!-- Uploaded assets -->
                <div 
                    v-bind="{animation: 200}"
                    v-for="(element, index) in assets"
                >
                    <v-asset-field-item
                        :name="name"
                        :index="index"
                        :asset="element"
                        :width="width"
                        :height="height"
                        @onRemove="remove($event)"
                    >
                    </v-asset-field-item>
                </div>

                <!-- Add Asset -->
                <label
                    class="grid justify-items-center items-center w-full h-[120px] max-w-[210px] max-h-[120px] border border-dashed dark:border-gray-300 rounded cursor-pointer transition-all hover:border-gray-400 border-gray-300"
                    :style="{'max-width': this.width, 'max-height': this.height}"
                    :for="$.uid + '_assetImageInput'"
                    @click="setCurrentAssets();$refs.assetPickerModal.open()"
                >
                    <div class="flex flex-col items-center">
                        <span class="icon-dam-folder text-2xl"></span>
                        <p class="grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center">
                            @lang('dam::app.admin.components.asset.field.add-asset')
                        </p>
                    </div>
                </label>

                <x-dam::modal ref="assetPickerModal">
                    <x-slot:header>
                        <div class="flex gap-x-2.5">
                            <!-- save selected assets -->
                            <span 
                                class="text-gray-800 dark:text-white font-semibold"
                            >
                                @lang('dam::app.admin.components.asset.field.assign-assets')
                            </span>
                        </div>
                    </x-slot>

                    <!--Modal Content -->
                    <x-slot:content>
                        <div class="flex gap-3">
                            @if (bouncer()->hasPermission('dam.directory.index'))
                                <x-dam::asset.picker.directory-tree />
                            @endif

                            <x-dam::asset.picker 
                                :src="route('admin.dam.asset_picker.index')"
                                ref="datagrid"
                            >
                                <template #body-header="{ records, meta, massActions, selectAllRecords }">
                                    <div class="flex gap-2 items-center justify-between pb-4" v-if="records.length">
                                        <!-- Select All -->
                                        <div class="flex gap-2">
                                            <label for="mass_action_select_all_records">
                                                <input
                                                    type="checkbox"
                                                    name="mass_action_select_all_records"
                                                    id="mass_action_select_all_records"
                                                    class="peer hidden"
                                                    :checked="['all', 'partial'].includes(meta.mode)"
                                                    @change="selectAllRecords"
                                                >
    
                                                <span
                                                    class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                                                    :class="[
                                                        meta.mode === 'all' ? 'peer-checked:icon-checkbox-check peer-checked:text-violet-700 ' : (
                                                        meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-violet-700' : ''
                                                        ),
                                                    ]"
                                                >
                                                </span>
                                                
                                            </label>
                                            <span class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-white"  >@lang("Select All")</span>
                                        </div>
                                        
                                        @if (bouncer()->hasPermission('dam.asset_assign'))
                                            <span 
                                                @click="saveAssets"
                                                class="secondary-button"
                                            >
                                                Assign
                                            </span>
                                        @endif
                                    </div>
                                </template>
                                <template #body="{ columns, records, performAction, setCurrentSelectionMode, meta, applied, isLoading }">
                                    <template v-if="! isLoading && records.length">
                                        <div
                                            v-for="record in records"
                                        >

                                            <!-- Select asset -->
                                            <label :for="`mass_action_select_record_${record[meta.primary_column]}`" class="cursor-pointer">
                                                <div class="grid image-card relative overflow-hidden transition-all hover:border-gray-400 group">
                                                    <img 
                                                        :src="record.path"
                                                        :alt="record.file_name"
                                                        class="w-full h-full object-cover object-top"
                                                    >
                                                </div>
                                                <div class="flex gap-2 items-center mt-2.5">
                                                    <input
                                                        type="checkbox"
                                                        class="peer hidden"
                                                        :name="`mass_action_select_record_${record[meta.primary_column]}`"
                                                        :value="record[meta.primary_column]"
                                                        :id="`mass_action_select_record_${record[meta.primary_column]}`"
                                                        v-model="applied.massActions.indices"
                                                        @change="setCurrentSelectionMode"
                                                    >
                                                    
                                                    <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer rounded-md text-2xl">
                                                    </span>

                                                    <h2 class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-white overflow-hidden" v-text="record.file_name"></h2>
                                                </div>
                                            </label>
                                        </div>
                                    </template>
    
                                    <template v-else>
                                        <x-admin::shimmer.datagrid.table.body isMultiRow="false" />
                                    </template>
    
                                </template>
                            </x-dam::asset.picker>
                        </div>
                    </x-slot>
                </x-dam::modal>

            </div>
        </div>  
    </script>

    <script type="text/x-template" id="v-asset-field-item-template">
        <div class="grid gap-2">
            <div class="grid justify-items-center min-w-[120px] max-h-[120px] relative rounded overflow-hidden transition-all hover:border-gray-400 group" :style="{'width': this.width, 'height': this.height}">
                <!-- Image Preview -->
                <img
                    :src="asset.url"
                    class="w-full h-full object-cover object-top"
                />
                <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
                    <!-- Actions -->
                    <div class="flex items-center justify-center h-full">
                        <span
                            class="icon-dam-download text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="download"
                            title="@lang('dam::app.admin.components.asset.field.download')"
                        ></span>

                        <span
                            class="icon-dam-full text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="preview"
                            v-if="'image' === asset.file_type"
                            title="@lang('dam::app.admin.components.asset.field.preview')"
                        ></span>

                        <span
                            class="icon-cancel text-3xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="remove"
                            title="@lang('dam::app.admin.components.asset.field.remove')"
                        ></span>

                        <input type="hidden" :name="name + '[]'" v-if="! asset.is_new && asset.value" :value="asset.value"/>
                    </div>
                </div>
            </div>

            <!-- Image Name -->
            <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all" v-text="asset.file_name"></p>
        </div>

        <!-- Modal Component for Preview -->
        <x-dam::modal ref="assetPreviewModal" no-class="true">
            <x-slot:content class="flex items-center">
                <div class="flex flex-row gap-3 justify-between w-full">
                    <div class="flex justify-center w-full">
                        <img 
                            :src="asset.previewUrl" 
                            alt="Preview" 
                            class="w-max"
                            v-if="asset"
                        />
                    </div>
                    <div>
                        <span
                            class="icon-cancel text-3xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md"
                            @click="toggle"
                        >
                        </span>
                    </div>
                </div>
            </x-slot>
        </x-dam::modal>
    </script>

    <script type="module">
        app.component('v-asset-field', {
            template: '#v-asset-field-template',

            props: {
                name: {
                    type: String, 
                    default: 'images',
                },

                assetValues: {
                    type: Array,
                    default: () => []
                },

                width: {
                    type: String,
                    default: '120px'
                },

                height: {
                    type: String,
                    default: '120px'
                },

                errors: {
                    type: Object,
                    default: () => {}
                }
            },

            data() {
                return {
                    assets: [],

                    placeholders: [
                    ],

                    currentAssets: [],

                    isLoading: false,
                }
            },

            mounted() {
                this.fetchAssets(this.assetValues, true);

                this.$emitter.on('change-datagrid', this.loadAssetValues);
            },

            methods: {
                remove(image) {
                    let index = this.assets.indexOf(image);

                    this.assets.splice(index, 1);
                },

                async saveAssets() {
                    let selectedIds = [];

                    const prevAssets = this.assets;

                    this.assets = [];

                    this.$refs.datagrid.applied.massActions.indices.forEach(id => {
                        let existing = prevAssets.filter(asset => asset.id === id);

                        if (existing.length === 1) {
                            this.assets.push(existing[0]);
                        } else {
                            selectedIds.push(id);
                        }
                    });

                    selectedIds = await this.fetchAssets(selectedIds);

                    this.assets = [
                        ...this.assets,
                        ...selectedIds
                    ];

                    this.$refs.assetPickerModal.close();
                },

                parseJson(value, silent = false) {
                    try {
                        return JSON.parse(value);
                    } catch (e) {
                        if (! silent) {
                            console.error(e);
                        }

                        return value;
                    }
                },

                loadAssetValues() {
                    if (this.currentAssets.length && this.$refs?.datagrid?.applied?.massActions?.indices) {
                        let selectedIndices = this.$refs.datagrid.applied.massActions.indices;

                        this.$refs.datagrid.applied.massActions.indices = [
                            ...this.currentAssets.filter(id => ! selectedIndices.includes(id)),
                            ...selectedIndices
                        ];

                        this.currentAssets = [];

                        this.$refs.datagrid.setCurrentSelectionMode()
                    }
                },

                fetchAssets(assetIds, initialize = false) {
                    this.isLoading = true;

                    return this.$axios.get("{{ route('admin.dam.asset_picker.get_assets') }}", {params: {assetIds: assetIds} })
                        .then(response => {
                            this.isLoading = false;

                            if (initialize) {
                                this.assets = response.data;

                                this.setCurrentAssets();
                            }

                            return response.data
                        }).catch(error => {
                            console.error(error);

                            this.isLoading = false;
                        });
                },

                setCurrentAssets() {
                    this.currentAssets = this.assets.map(item => item.id);
                },
            }
        });

        app.component('v-asset-field-item', {
            template: '#v-asset-field-item-template',

            props: ['index', 'asset', 'name', 'width', 'height'],

            methods: {
                remove() {
                    this.$emit('onRemove', this.asset)
                },

                download() {
                    let downloadLink = `{{ route('admin.dam.assets.download', '') }}/${this.asset.id}`;

                    window.open(downloadLink, '_self');
                },

                preview(record) {
                    if (! this.asset.previewUrl) {
                        this.setPreviewUrl();
                    }

                    this.$refs.assetPreviewModal.open();
                },

                setPreviewUrl() {
                    let filePath = encodeURIComponent(this.asset.storage_file_path);

                    this.asset.previewUrl = `{{ route('admin.dam.file.preview', '') }}?path=${filePath}`;
                },
            }
        });
    </script>
@endPushOnce
