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

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3" v-else>
                <input type="hidden" :name="name + '[]'" value="" v-if="assets.length === 0">

                <draggable
                    class="contents"
                    ghost-class="draggable-ghost"
                    v-bind="{animation: 200}"
                    :list="assets"
                    item-key="id"
                    handle=".icon-drag"
                >
                    <template #item="{ element, index }">
                        <v-asset-field-item
                            :name="name"
                            :index="index"
                            :asset="element"
                            :width="width"
                            :height="height"
                            @onRemove="remove($event)"
                        >
                        </v-asset-field-item>
                    </template>
                </draggable>

                <label
                    class="group flex flex-col justify-center items-center min-h-[160px] rounded-lg border-2 border-dashed border-gray-300 dark:border-cherry-500 bg-gradient-to-br from-violet-50/40 to-white dark:from-cherry-900/40 dark:to-cherry-900 cursor-pointer transition-all hover:border-violet-500 dark:hover:border-violet-400 hover:shadow-md"
                    @click="openPicker"
                >
                    <span class="icon-dam-folder text-3xl text-gray-400 group-hover:text-violet-600 transition-colors"></span>
                    <p class="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @lang('dam::app.admin.components.asset.field.add-asset')
                    </p>
                </label>

                <v-dam-asset-picker ref="assetPicker" @assign="onAssign"></v-dam-asset-picker>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="v-asset-field-item-template">
        <div class="group relative flex flex-col rounded-lg border border-gray-200 dark:border-cherry-800 bg-white dark:bg-cherry-900 overflow-hidden shadow-sm transition-all hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-700">
            <div class="relative w-full">
                <img
                    :src="asset.url"
                    class="w-full h-[140px] object-cover object-top bg-gray-100 dark:bg-cherry-800"
                    v-if="!imgLoadError"
                    v-on:error="imgLoadError = true"
                />
                <img
                    v-if="imgLoadError"
                    :src="typePlaceholder"
                    :data-href="asset.url"
                    class="w-full h-[140px] object-cover object-top bg-gray-100 dark:bg-cherry-800 cursor-pointer"
                    @click="window.location.href = asset.url"
                />

                <div class="absolute inset-0 flex items-end justify-center gap-2 p-2 bg-gradient-to-t from-black/60 via-black/20 to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                    <span class="icon-drag text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-white/30 cursor-grab active:cursor-grabbing"></span>

                    <span
                        class="icon-view text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-white/30 cursor-pointer"
                        @click="preview"
                        aria-label="@lang('dam::app.admin.components.asset.field.preview')"
                    ></span>

                    <span
                        class="icon-dam-download text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-white/30 cursor-pointer"
                        @click="download"
                        aria-label="@lang('dam::app.admin.components.asset.field.download')"
                    ></span>

                    <span
                        class="icon-delete text-xl p-1.5 rounded-md text-white bg-white/10 hover:bg-red-500/80 cursor-pointer"
                        @click="remove"
                        aria-label="@lang('dam::app.admin.components.asset.field.remove')"
                    ></span>

                    <input type="hidden" :name="name + '[]'" v-if="! asset.is_new && asset.value" :value="asset.value"/>
                </div>
            </div>

            <p
                class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-center truncate"
                v-text="asset.file_name"
            ></p>
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
