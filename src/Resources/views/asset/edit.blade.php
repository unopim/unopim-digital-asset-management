<x-dam::layouts.with-history.asset>
    <x-slot:title>
        @lang('dam::app.admin.dam.asset.edit.title')
    </x-slot:title>

    <x-slot:entityName>
        asset
    </x-slot>

    <x-slot:label>
        {{ ucfirst($asset->file_name) }}
    </x-slot>

    <x-slot:button-one>
        <v-custom-download>
        </v-custom-download>
    </x-slot>

    <x-slot:button-two>
        <v-rename-asset>
        </v-rename-asset>
    </x-slot>

    <x-slot:button-three>
        <v-reupload-asset>
        </v-reupload-asset>
    </x-slot>

    <x-slot:button-four>
        <v-delete-asset>
        </v-delete-asset>
    </x-slot>

    @php
        $items = [
            [
                'url' => '?',
                'code' => 'preview',
                'name' => 'dam::app.admin.dam.asset.edit.tab.preview',
                'icon' => 'icon-dam-preview',
            ],
        ];

        $items[] = [
            'url' => '?meta-data',
            'code' => 'meta-data',
            'name' => 'dam::app.admin.dam.asset.edit.embedded_meta_info',
            'icon' => 'icon-manage-column',
        ];

        if (bouncer()->hasPermission('dam.asset.property')) {
            $items[] = [
                'url' => '?properties',
                'code' => 'properties',
                'name' => 'dam::app.admin.dam.asset.edit.tab.properties',
                'icon' => 'icon-dam-properties',
            ];
        }

        if (bouncer()->hasPermission('dam.asset.comment')) {
            $items[] = [
                'url' => '?comments',
                'code' => 'comments',
                'name' => 'dam::app.admin.dam.asset.edit.tab.comments',
                'icon' => 'icon-dam-notes',
            ];
        }

        if (bouncer()->hasPermission('dam.asset.linked_resources')) {
            $items[] = [
                'url' => '?linked-resources',
                'code' => 'linked-resources',
                'name' => 'dam::app.admin.dam.asset.edit.tab.linked_resources',
                'icon' => 'icon-dam-link',
            ];
        }

        if (bouncer()->hasPermission('history.view')) {
            $items[] = [
                'url' => '?history',
                'code' => 'history',
                'name' => 'dam::app.admin.dam.asset.edit.tab.history',
                'icon' => 'icon-information',
            ];
        }

    @endphp

    <x-slot:add-tabs :items="$items"></x-slot:add-tabs>

    {!! view_render_event('unopim.dam.admin.asset.edit.before') !!}

    <v-edit-asset></v-edit-asset>

    <x-slot:properties>
        {!! view_render_event('unopim.admin.dam.assets.edit.properties.before') !!}
        @include('dam::asset.properties.index')
        {!! view_render_event('unopim.admin.dam.assets.edit.properties.after') !!}
    </x-slot:properties>

    <x-slot:comments>
        {!! view_render_event('unopim.admin.dam.assets.edit.properties.before') !!}
        @include('dam::asset..comments.index')
        {!! view_render_event('unopim.admin.dam.assets.edit.properties.after') !!}
    </x-slot:comments>

    <x-slot:linked_resources>
        @if (bouncer()->hasPermission('dam.asset.linked_resources.index'))
            @include('dam::asset.linked-resources.index', ['assetId' => $asset->id])
        @endif
    </x-slot:linked_resources>

    <x-slot:meta_data>
        @include('dam::asset.meta-data.index', ['asset' => $asset])
    </x-slot:meta_data>

    {!! view_render_event('unopim.dam.admin.asset.edit.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-asset-template"
        >
            {!! view_render_event('unopim.dam.asset.edit.before') !!}

            <x-admin::form
                :action="route('admin.dam.assets.update', $asset->id)"
                enctype="multipart/form-data"
                method="PUT"
            >
                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                    <div class="flex gap-2.5 mt-3.5 w-full">

                        <!-- Left sub Component -->
                        <div class="flex flex-col flex-1 gap-2 overflow-auto bg-white dark:bg-cherry-900 rounded-lg box-shadow items-center justify-start p-8">
                            {!! view_render_event('unopim.dam.asset.edit.card.general.before', ['asset' => $asset]) !!}

                            <v-asset-preview-modal></v-asset-preview-modal>

                            {!! view_render_event('unopim.dam.asset.edit.card.general.after', ['asset' => $asset]) !!}
                        </div>

                        <!-- Right sub-component -->
                        <div class="flex flex-col gap-5 w-[360px] h-full max-sm:w-full bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                            <!-- Tags -->
                            {!! view_render_event('unopim.dam.asset.edit.card.accordian.tags.before', ['asset' => $asset]) !!}
                            
                            <x-admin::accordion>
                                <x-slot:header>
                                    <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                        @lang('dam::app.admin.dam.asset.edit.tags')
                                    </p>
                                </x-slot>

                                <x-slot:content class="gap-4">
                                    <x-admin::form.control-group>

                                        @php
                                            $options = json_encode($tags->toArray());

                                            $selectedOptions =  old('tags') ?? json_encode($asset->tags->pluck('id')->toArray());
                                            
                                        @endphp

                                        <x-admin::form.control-group.control
                                            type="tagging"
                                            id="tags"
                                            name="tags"
                                            :options="$options"
                                            :value="$selectedOptions"
                                            :label="trans('dam::app.admin.dam.asset.edit.tags')"
                                            :placeholder="trans('dam::app.admin.dam.asset.edit.select-tags')"
                                            track-by="id"
                                            label-by="name"
                                            @add-option="onTaggingChange($event)"
                                            @select-option="onTaggingChange($event)"
                                            @remove-option="onTaggingRemove($event)"
                                        />
                                        
                                        <x-admin::form.control-group.error control-name="tags" />

                                    </x-admin::form.control-group>
                                    
                                </x-slot>
                            </x-admin::accordion>

                            {!! view_render_event('unopim.dam.asset.edit.card.accordian.tags.after', ['asset' => $asset]) !!}

                            {!! view_render_event('unopim.dam.asset.edit.card.accordian.directory_path.befor', ['asset' => $asset]) !!}

                            <x-admin::accordion>
                                <x-slot:header>
                                    <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                        @lang('dam::app.admin.dam.asset.edit.directory-path')
                                    </p>
                                </x-slot>

                                <x-slot:content class="gap-4">
                                    <p class="text-sm text-zinc-600 !leading-normal dark:text-slate-300"> {{ $asset->getPathWithOutFileSystemRoot() }}</p>
                                </x-slot>
                            </x-admin::accordion>

                            {!! view_render_event('unopim.dam.asset.edit.card.accordian.directory_path.after', ['asset' => $asset]) !!}
                        </div>
                    </div>
                </div>
            </x-admin::form>

            {!! view_render_event('unopim.dam.asset.edit.after') !!}

        </script>

        <script type="module">
            app.component('v-edit-asset', {
                template: '#v-edit-asset-template',

                data: function() {
                    return {
                        asset: @json($asset)
                    };
                },
                methods: {
                    onTaggingChange(event) {
                        const changedValue = event.target.value;
                        if (changedValue) {
                            const formData = new FormData();
                            const tagValue = typeof changedValue === 'object' ? changedValue.name : changedValue;
                            formData.append('tag', tagValue);
                            if (this.asset) {
                                formData.append('asset_id', this.asset.id);
                            }

                            this.addOrUpdateTag(formData);
                        }
                    },
                    onTaggingRemove(event) {
                        const changedValue = event.target.value;
                        if (changedValue) {
                            const formData = new FormData();

                            const tagValue = typeof changedValue === 'object' ? changedValue.name : changedValue;
                            formData.append('tag', tagValue);

                            if (this.asset) {
                                formData.append('asset_id', this.asset.id);
                            }

                            this.removeTag(formData);
                        }
                    },
                    addOrUpdateTag(formData) {
                        this.$axios.post("{{ route('admin.dam.assets.tag') }}", formData).then((response) => {
                            this.$emitter.emit('uploaded-assets', response.data.file);
                        }).catch((error) => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response.data.message
                            });
                            console.error('Upload failed:', error);
                        });
                    },
                    removeTag(formData) {
                        this.$axios.post("{{ route('admin.dam.assets.remove-tag') }}", formData).then((response) => {
                            this.$emitter.emit('uploaded-assets', response.data.file);

                        }).catch((error) => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response.data.message
                            });
                            console.error('Upload failed:', error);
                        });
                    }
                }
            });
        </script>

        <!-- **** Asset Preview Modal **** -->
        @include('dam::asset.preview-modal')

        <!-- **** Custom Download **** -->
        <script
            type="text/x-template"
            id="v-custom-download-template"
        >

         <!-- ****  previous and next buttons **** -->
            @php
                $queryParams = request()->query();
                $queryString = '';
                if (!empty($queryParams)) {
                    $queryString = '?' . implode('&', array_map(fn($key) => $key, array_keys($queryParams)));
                }
            @endphp

            @if($asset->previousAssetId)
                <button class="secondary-button" title="{{ trans('dam::app.admin.dam.asset.edit.previous') }}"
                 :disabled="isLocked"
                 :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked }"
                 @click="goToPreviousAsset('{{ route('admin.dam.assets.edit', $asset->previousAssetId) }}{{ $queryString }}')">
                    <span class="text-2xl">&larr;</span>
                </button>
            @endif
            @if($asset->nextAssetId)
                <button class="secondary-button"  title="{{ trans('dam::app.admin.dam.asset.edit.next') }}"
                    :disabled="isLocked"
                    :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked }"
                    @click="goToNextAsset('{{ route('admin.dam.assets.edit', $asset->nextAssetId) }}{{ $queryString }}')">

                    <span class="text-2xl">&rarr;</span>
                </button>
            @endif

            @if (bouncer()->hasPermission('dam.asset.download'))

                @if($asset->extension ==='svg')
                <button class="secondary-button"
                    :disabled="isLocked"
                    :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked }"
                    @click="svgDownloadModel">
                    <span class="text-xl text-violet-700 icon-dam-download"></span>
                    <span>@lang('dam::app.admin.dam.asset.edit.button.custom_download')</span>
                </button>
                @elseif ($asset->file_type === 'image')
                    <button class="secondary-button"
                        :disabled="isLocked"
                        :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked }"
                        @click="customDownloadModel">
                        <span class="text-xl text-violet-700 icon-dam-download"></span>
                        <span>@lang('dam::app.admin.dam.asset.edit.button.custom_download')</span>
                    </button>
                @else
                    <button class="secondary-button"
                        :disabled="isLocked"
                        :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked }"
                        @click="downloadItem">
                        <span class="text-xl text-violet-700 icon-dam-download"></span>
                        <span>@lang('dam::app.admin.dam.asset.edit.button.download')</span>
                    </button>
                @endif
            @endif
                
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit.prevent="handleSubmit($event, customDownload)"
                    ref="assetCustomDownloadForm"
                >
                    <x-admin::modal ref="assetCustomDownloadModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p
                                class="text-lg text-gray-800 dark:text-white font-bold"
                            >
                                @lang('dam::app.admin.dam.asset.edit.custom-download.title')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            {!! view_render_event('unopim.admin.dam.asset.custom_download.before') !!}

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                                v-model="selectedItem"
                            />

                            <!-- format -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('dam::app.admin.dam.asset.edit.custom-download.format')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="format"
                                    class="cursor-pointer"
                                    rules="required"
                                    :value="old('format')"
                                    :label="trans('dam::app.admin.dam.asset.edit.custom-download.format')"
                                    :placeholder="trans('dam::app.admin.dam.asset.edit.custom-download.format')"
                                    v-model="selectedItemExtension"
                                    ::options="supportedExtensionTypes"
                                    track-by="value"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="format" />
                            </x-admin::form.control-group>

                            <div class="flex gap-4 items-top">
                                <!-- width -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('dam::app.admin.dam.asset.edit.custom-download.width')
                                    </x-admin::form.control-group.label>
    
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="width"
                                        rules="required"
                                        :value="old('width')"
                                        v-model="selectedItemWidth"
                                        :label="trans('dam::app.admin.dam.asset.edit.custom-download.width')"
                                        :placeholder="trans('dam::app.admin.dam.asset.edit.custom-download.width-placeholder')"
                                    />
    
                                    <x-admin::form.control-group.error control-name="width" />
                                </x-admin::form.control-group>
    
                                <!-- height -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('dam::app.admin.dam.asset.edit.custom-download.height')
                                    </x-admin::form.control-group.label>
    
                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="height"
                                        rules="required"
                                        :value="old('height')"
                                        v-model="selectedItemHeight"
                                        :label="trans('dam::app.admin.dam.asset.edit.custom-download.height')"
                                        :placeholder="trans('dam::app.admin.dam.asset.edit.custom-download.height-placeholder')"
                                    />
    
                                    <x-admin::form.control-group.error control-name="height" />
                                </x-admin::form.control-group>
                            </div>

                            {!! view_render_event('unopim.admin.dam.asset.custom_download.after') !!}
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('dam::app.admin.dam.asset.edit.custom-download.download-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit.prevent="handleSubmit($event, svgCustomDownload)"
                    ref="svgCustomDownloadForm"
                >
                    <x-admin::modal ref="svgCustomDownloadModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p
                                class="text-lg text-gray-800 dark:text-white font-bold"
                            >
                                @lang('dam::app.admin.dam.asset.edit.custom-download.title')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            {!! view_render_event('unopim.admin.dam.asset.custom_download.before') !!}

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                                v-model="selectedItem"
                            />

                            <!-- format -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('dam::app.admin.dam.asset.edit.custom-download.format')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="format"
                                    class="cursor-pointer"
                                    rules="required"
                                    :value="old('format')"
                                    :label="trans('dam::app.admin.dam.asset.edit.custom-download.format')"
                                    :placeholder="trans('dam::app.admin.dam.asset.edit.custom-download.format')"
                                    v-model="selectedItemExtension"
                                    ::options="supportedExtensionTypes"
                                    track-by="value"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="format" />
                            </x-admin::form.control-group>

                            {!! view_render_event('unopim.admin.dam.asset.custom_download.after') !!}
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('dam::app.admin.dam.asset.edit.custom-download.download-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

        </script>

        <script type="module">
            app.component('v-custom-download', {
                template: '#v-custom-download-template',
                data() {
                    const selectedItem = @json($asset);
                    return {
                        selectedItem: selectedItem,
                        isLocked: false,
                        onLockChange: null,
                        supportedExtensionTypes: [{
                                label: "@lang('dam::app.admin.dam.asset.edit.custom-download.extension-types.original')",
                                value: selectedItem?.extension,
                            },
                            {
                                label: "@lang('dam::app.admin.dam.asset.edit.custom-download.extension-types.jpg')",
                                value: 'jpg'
                            },
                            {
                                label: "@lang('dam::app.admin.dam.asset.edit.custom-download.extension-types.jpeg')",
                                value: 'jpeg'
                            },
                            {
                                label: "@lang('dam::app.admin.dam.asset.edit.custom-download.extension-types.png')",
                                value: 'png'
                            },
                            {
                                label: "@lang('dam::app.admin.dam.asset.edit.custom-download.extension-types.webp')",
                                value: 'webp'
                            },
                        ],

                        selectedItemExtension: selectedItem?.extension,
                        selectedItemWidth: selectedItem?.width ?? 0,
                        selectedItemHeight: selectedItem?.height ?? 0,
                    };
                },
                mounted() {
                    this.onLockChange = (locked) => { this.isLocked = !!locked; };
                    this.$emitter.on('dam-asset-action-locked', this.onLockChange);
                },
                unmounted() {
                    if (this.onLockChange) {
                        this.$emitter.off('dam-asset-action-locked', this.onLockChange);
                    }
                },
                methods: {

                    goToPreviousAsset(url) {
                        if (this.isLocked) return;
                        window.location.href = url;
                    },

                    goToNextAsset(url) {
                        if (this.isLocked) return;
                        window.location.href = url;
                    },

                    svgDownloadModel() {
                        this.$refs.svgCustomDownloadModal.toggle();
                    },
                    svgCustomDownload(params, {
                        resetForm,
                        setErrors
                    }) {
                        const format = (() => {
                            try {
                                return JSON.parse(params.format).value;
                            } catch (e) {
                                return params.format;
                            }
                        })();

                        let downloadLink = `{{ route('admin.dam.assets.custom_download', ':id') }}`.replace(':id', this.selectedItem.id) + `?format=${format}`;

                        this.selectedItemExtension = this.selectedItem?.extension;

                        this.$refs.svgCustomDownloadModal.close();

                        window.open(downloadLink, '_self');
                    },
                    customDownloadModel() {
                        this.$refs.assetCustomDownloadModal.toggle();
                    },
                    customDownload(params, {
                        resetForm,
                        setErrors
                    }) {
                        const format = (() => {
                            try {
                                return JSON.parse(params.format).value;
                            } catch (e) {
                                return params.format;
                            }
                        })();

                        const formatHeight = params.height;
                        const formatWidth = params.width;

                        let downloadLink =
                            `{{ route('admin.dam.assets.custom_download', ':id') }}`.replace(':id', this.selectedItem.id) + `?format=${format}&height=${formatHeight}&width=${formatWidth}`;

                        this.selectedItemExtension = this.selectedItem?.extension;
                        this.selectedItemWidth = this.selectedItem?.width ?? 0;
                        this.selectedItemHeight = this.selectedItem?.height ?? 0;

                        this.$refs.assetCustomDownloadModal.close();

                        window.open(downloadLink, '_self');
                    },
                    downloadItem() {
                        let downloadLink = `{{ route('admin.dam.assets.download', ':id') }}`.replace(':id', this.selectedItem.id);

                        window.open(downloadLink, '_self');
                    },
                },

            });
        </script>

        <!-- **** Rename **** -->
        <script
            type="text/x-template"
            id="v-rename-asset-template"
        >
            @if (bouncer()->hasPermission('dam.asset.rename'))
                <button class="secondary-button"
                    :disabled="isLocked"
                    :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked }"
                    @click="renameItem">
                    <span class="text-xl text-violet-700 icon-dam-rename"></span>
                    <span>@lang('dam::app.admin.dam.asset.edit.button.rename')</span>
                </button>
            @endif
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
                                @lang('dam::app.admin.dam.asset.edit.button.rename')
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
                                    @lang('dam::app.admin.dam.asset.edit.file-name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="file_name"
                                    rules="required"
                                    :value="old('file_name')"
                                    v-model="selectedItem.file_name"
                                    ref="fileName"
                                    :label="trans('dam::app.admin.dam.asset.edit.file-name')"
                                    :placeholder="trans('dam::app.admin.dam.asset.edit.file-name')"
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
                                    :disabled="isRenaming"
                                    :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isRenaming }"
                                >
                                    <svg
                                        v-if="isRenaming"
                                        class="align-center inline-block animate-spin h-4 w-4 mr-2 text-white"
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
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    @lang('dam::app.admin.dam.asset.edit.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

        </script>

        <script type="module">
            app.component('v-rename-asset', {
                template: '#v-rename-asset-template',
                data: function() {
                    return {
                        selectedItem: @json($asset),
                        isLocked: false,
                        onLockChange: null,
                        isRenaming: false,
                    };
                },
                mounted() {
                    this.onLockChange = (locked) => { this.isLocked = !!locked; };
                    this.$emitter.on('dam-asset-action-locked', this.onLockChange);
                },
                unmounted() {
                    if (this.onLockChange) {
                        this.$emitter.off('dam-asset-action-locked', this.onLockChange);
                    }
                },
                methods: {
                    renameItem() {
                        if (this.isLocked) return;
                        this.$refs.assetRenameModal.toggle();
                    },
                    focusNameInput() {
                        this.$nextTick(() => {
                            if (this.$refs.fileName) {
                                this.$refs.fileName.focus();
                            }
                        });
                    },
                    renameAsset(params, {
                        resetForm,
                        setErrors
                    }) {
                        if (this.isRenaming) return;

                        this.isRenaming = true;

                        let formData = new FormData(this.$refs.assetRenameForm);

                        this.$axios.post("{{ route('admin.dam.assets.rename') }}", formData)
                            .then((response) => {
                                this.$refs.assetRenameModal.close();
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                resetForm();
                                location.reload();
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }

                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data.message
                                });
                            })
                            .finally(() => {
                                this.isRenaming = false;
                            });
                    },
                }
            });
        </script>

        <!-- **** Reupload **** -->
        <script
            type="text/x-template"
            id="v-reupload-asset-template"
        >      
            @if (bouncer()->hasPermission('dam.asset.re_upload'))
                <input type="file"
                    name="file"
                    id="file-upload"
                    class="hidden"
                    :disabled="isUploading"
                    @change="onFileChange"
                />
                <label
                    for="file-upload"
                    class="secondary-button cursor-pointer"
                    :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isUploading }"
                    :aria-disabled="isUploading"
                >
                    <svg
                        v-if="isUploading"
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
                    <span v-else class="text-xl text-violet-700 icon-dam-upload"></span>
                    <span v-if="isUploading">@lang('dam::app.admin.dam.asset.edit.button.re_uploading')</span>
                    <span v-else>@lang('dam::app.admin.dam.asset.edit.button.re_upload')</span>
                </label>

                <button
                    v-if="isUploading"
                    type="button"
                    class="secondary-button"
                    @click="cancelUpload"
                >
                    @lang('dam::app.admin.dam.asset.edit.button.cancel')
                </button>
            @endif
        </script>

        <script type="module">
            const reUploadFileTooLargeMsg = @js(trans('dam::app.admin.dam.asset.datagrid.file-too-large', ['size' => \Webkul\DAM\Helpers\AssetHelper::humanReadableSize(\Webkul\DAM\Helpers\AssetHelper::getMaxUploadSizeKb())]));
            const reUploadFailedMsg = @js(trans('dam::app.admin.dam.asset.datagrid.file-upload-failed'));

            app.component('v-reupload-asset', {
                template: '#v-reupload-asset-template',
                data() {
                    return {
                        selectedItem: @json($asset),
                        isUploading: false,
                        abortController: null,
                    };
                },
                methods: {
                    onFileChange(e) {
                        if (this.isUploading) {
                            e.target.value = null;
                            return;
                        }

                        const fileInput = e.target.files;

                        if (fileInput.length > 0) {
                            const formData = new FormData();

                            formData.append('file', fileInput[0]);

                            if (this.selectedItem) {
                                formData.append('asset_id', this.selectedItem.id);
                            }

                            this.handleFileUpload(formData);
                        }

                        e.target.value = null;
                    },

                    cancelUpload() {
                        if (this.abortController) {
                            this.abortController.abort();
                            this.abortController = null;
                        }
                    },

                    handleFileUpload(formData) {
                        this.isUploading = true;
                        this.abortController = new AbortController();
                        this.$emitter.emit('dam-asset-action-locked', true);

                        this.$axios.post("{{ route('admin.dam.assets.re_upload') }}", formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            },
                            signal: this.abortController.signal,
                        }).then((response) => {
                            // Server-level errors (e.g. post_max_size exceeded) return 200 with an
                            // HTML body instead of JSON. Detect by checking the data type.
                            if (typeof response.data !== 'object' || response.data === null) {
                                this.$emitter.emit('add-flash', { type: 'error', message: reUploadFileTooLargeMsg });
                                return;
                            }
                            location.reload();
                            this.$emitter.emit('uploaded-assets', response.data.file);
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message
                            });

                        }).catch((error) => {
                            if (this.$axios.isCancel(error) || error.code === 'ERR_CANCELED') {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: @js(trans('dam::app.admin.dam.asset.edit.button.re-upload-cancelled')),
                                });
                                return;
                            }
                            const message = error.response?.status === 413
                                ? reUploadFileTooLargeMsg
                                : (error.response?.data?.message ?? reUploadFailedMsg);
                            this.$emitter.emit('add-flash', { type: 'error', message });
                        }).finally(() => {
                            this.isUploading = false;
                            this.abortController = null;
                            this.$emitter.emit('dam-asset-action-locked', false);
                        });
                    }
                }
            });
        </script>

        <!-- **** Delete **** -->
        <script
            type="text/x-template"
            id="v-delete-asset-template"
        >
            @if (bouncer()->hasPermission('dam.asset.delete'))
                <button class="secondary-button"
                    :disabled="isLocked || isDeleting"
                    :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isLocked || isDeleting }"
                    @click="deleteFile">
                    <svg
                        v-if="isDeleting"
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
                    <span v-else class="text-xl text-violet-700 icon-dam-delete"></span>
                    <span>@lang('dam::app.admin.dam.asset.edit.button.delete')</span>
                </button>
            @endif
        </script>

        <script type="module">
            app.component('v-delete-asset', {
                template: '#v-delete-asset-template',
                data() {
                    return {
                        selectedItem: @json($asset),
                        isLocked: false,
                        onLockChange: null,
                        isDeleting: false,
                    };
                },
                mounted() {
                    this.onLockChange = (locked) => { this.isLocked = !!locked; };
                    this.$emitter.on('dam-asset-action-locked', this.onLockChange);
                },
                unmounted() {
                    if (this.onLockChange) {
                        this.$emitter.off('dam-asset-action-locked', this.onLockChange);
                    }
                },
                methods: {
                    deleteFile() {
                        if (this.isLocked || this.isDeleting) return;
                        this.$emitter.emit('open-delete-modal', {
                            agree: () => {
                                this.isDeleting = true;

                                this.$axios.delete(
                                        `{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', this.selectedItem.id)
                                    )
                                    .then(response => {
                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: response.data.message
                                        });

                                        window.location.assign("{{ route('admin.dam.assets.index') }}");
                                    })
                                    .catch((error) => {
                                        this.$emitter.emit('add-flash', {
                                            type: 'error',
                                            message: error.response.data.message
                                        });
                                    })
                                    .finally(() => {
                                        this.isDeleting = false;
                                    });
                            }
                        });

                        this.closeContextMenu();
                    },
                    closeContextMenu() {
                        this.showContextMenuFlag = false;
                        document.removeEventListener('click', this.closeContextMenu);
                    },
                }
            });
        </script>
    @endPushOnce
    </x-admin::layouts.with-history.asset>
