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

@include('dam::asset.picker-modal')

@pushOnce('scripts')
    <script type="text/x-template" id="v-asset-field-template">

        <div class="grid">
            <x-admin::shimmer.image class="w-[110px] h-[110px] rounded" v-if="isLoading" />

            <div class="flex flex-wrap gap-3" v-else>
                <input type="hidden" :name="name + '[]'" value="" v-if="assets.length === 0">

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

                <label
                    class="grid justify-items-center items-center w-full h-[120px] max-w-[210px] max-h-[120px] border border-dashed dark:border-gray-300 rounded cursor-pointer transition-all hover:border-gray-400 border-gray-300"
                    :style="{'max-width': this.width, 'max-height': this.height}"
                    :for="$.uid + '_assetImageInput'"
                    @click="openPicker"
                >
                    <div class="flex flex-col items-center">
                        <span class="icon-dam-folder text-2xl"></span>
                        <p class="grid text-sm text-gray-600 dark:text-gray-300 font-semibold text-center">
                            @lang('dam::app.admin.components.asset.field.add-asset')
                        </p>
                    </div>
                </label>

                <v-dam-asset-picker ref="assetPicker" @assign="onAssign"></v-dam-asset-picker>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="v-asset-field-item-template">
        <div class="grid gap-2">
            <div class="grid justify-items-center min-w-[120px] max-h-[120px] relative rounded overflow-hidden transition-all hover:border-gray-400 group" :style="{'width': this.width, 'height': this.height}">

                <img
                    :src="asset.url"
                    class="w-full h-full object-cover object-top"
                    v-if="!imgLoadError"
                    v-on:error="imgLoadError = true"
                />
                <img
                    v-if="imgLoadError"
                    :src="typePlaceholder"
                    :data-href="asset.url"
                    class="absolute inset-0 w-full h-full object-cover object-top cursor-pointer"
                    @click="window.location.href = asset.url"
                />
                <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">

                    <div class="flex items-center justify-center h-full">
                        <span
                            class="icon-dam-download text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="download"
                            title="@lang('dam::app.admin.components.asset.field.download')"
                        ></span>

                        <span
                            class="icon-dam-full text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                            @click="preview"
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

            <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all" v-text="asset.file_name"></p>
        </div>

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

                    currentAssets: [],

                    isLoading: false,
                }
            },

            mounted() {
                this.fetchAssets(this.assetValues, true);
            },

            methods: {
                remove(image) {
                    let index = this.assets.indexOf(image);

                    this.assets.splice(index, 1);
                },

                openPicker() {
                    this.setCurrentAssets();

                    this.$refs.assetPicker.open(this.currentAssets);
                },

                async onAssign(ids) {
                    const prevAssets = this.assets;

                    this.assets = [];

                    let selectedIds = [];

                    ids.forEach(id => {
                        let existing = prevAssets.filter(asset => asset.id === id);

                        if (existing.length === 1) {
                            this.assets.push(existing[0]);
                        } else {
                            selectedIds.push(id);
                        }
                    });

                    const fetched = selectedIds.length ? await this.fetchAssets(selectedIds) : [];

                    this.assets = [
                        ...this.assets,
                        ...(fetched || [])
                    ];
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

            data() {
                return {
                    imgLoadError: false,
                };
            },

            computed: {
                typePlaceholder() {
                    const placeholders = {
                        video:    `{{ asset('storage/dam/grid/video.svg') }}`,
                        audio:    `{{ asset('storage/dam/grid/audio.svg') }}`,
                        document: `{{ asset('storage/dam/grid/file.svg') }}`,
                    };

                    return placeholders[this.asset.file_type] ?? `{{ asset('storage/dam/grid/unspecified.svg') }}`;
                },
            },

            methods: {
                remove() {
                    this.$emit('onRemove', this.asset)
                },

                download() {
                    let downloadLink = `{{ route('admin.dam.assets.download', ':id') }}`.replace(':id', this.asset.id);

                    window.open(downloadLink, '_self');
                },

                preview() {
                    this.$emitter.emit('dam-open-preview', this.asset.id);
                },
            }
        });
    </script>
@endPushOnce
