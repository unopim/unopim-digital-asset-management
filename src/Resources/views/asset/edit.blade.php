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
            'url'    => '?',
            'code'   => 'preview',
            'name'   => 'dam::app.admin.dam.asset.edit.tab.preview',
            'icon'   => 'icon-dam-preview'
            ],
        ];

        if (bouncer()->hasPermission('dam.asset.property')) {
            $items[] = [
                'url'    => '?properties',
                'code'   => 'properties',
                'name'   => 'dam::app.admin.dam.asset.edit.tab.properties',
                'icon'   => 'icon-dam-properties'
            ];
        }

        if (bouncer()->hasPermission('dam.asset.comment')) {
            $items[] = [
                'url'    => '?comments',
                'code'   => 'comments',
                'name'   => 'dam::app.admin.dam.asset.edit.tab.comments',
                'icon'   => 'icon-dam-notes'
            ];
        }

        if (bouncer()->hasPermission('dam.asset.linked_resources')) {
            $items[] = [
                'url'    => '?linked-resources',
                'code'   => 'linked-resources',
                'name'   => 'dam::app.admin.dam.asset.edit.tab.linked_resources',
                'icon'   => 'icon-dam-link'
            ];
        }

        if (bouncer()->hasPermission('history.view')) {
            $items[] = [
                'url'    => '?history',
                'code'   => 'history',
                'name'   => 'dam::app.admin.dam.asset.edit.tab.history',
                'icon'   => 'icon-information'
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
                        <div class="flex flex-col flex-1 gap-2 overflow-auto bg-white dark:bg-cherry-900 rounded-lg box-shadow  
                            @if($asset->file_type === 'audio')
                                items-center justify-center
                            @endif
                        ">
                            {!! view_render_event('unopim.dam.asset.edit.card.general.before', ['asset' => $asset]) !!}
                            
                                @if ($asset->extension == 'pdf')
                                    <div id="iframe-container">
                                        <iframe 
                                            src="{{ $asset->previewPath }}" 
                                            width="100%" 
                                            height="800"
                                            onerror="document.getElementById('iframe-container').innerHTML = '<p>Unable to load content. Please check your network connection or resource availability.</p>';"
                                        >
                                        </iframe>
                                    </div>
                                @elseif (in_array($asset->file_type, ['audio']))
                                    <audio controls="" autoplay="" name="media">
                                        <source src="{{ $asset->previewPath }}"  type="audio/{{ $asset->extension }}">
                                        Your browser does not support the audio element.
                                    </audio>
                                @elseif (in_array($asset->file_type, ['video', 'audio']))
                                    <video controls="" autoplay="" name="media" width="100%">
                                        <source src="{{ $asset->previewPath }}" type="video/{{ $asset->extension }}">
                                        Your browser does not support the video element.
                                    </video>
                                     
                                @else
                                    <img
                                        src="{{ $asset->previewPath }}"
                                        alt="{{ $asset->file_name }}"
                                    />
                                @endif
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

                            <!-- Embedded MetaInfo -->
                            @if ($asset->embeddedMetaInfo)
                                {!! view_render_event('unopim.dam.asset.edit.card.accordian.embedded_meta_info.before', ['asset' => $asset]) !!}

                                <x-admin::accordion>
                                    <x-slot:header>
                                        <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                            @lang('dam::app.admin.dam.asset.edit.embedded_meta_info')
                                        </p>
                                    </x-slot>
        
                                    <x-slot:content class="overflow-x-auto max-w-full !px-0 !pb-0">
                                        <x-admin::table class="w-full text-sm text-left min-w-[400px]">
                                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                                    <x-admin::table.thead.tr>
                                                        <x-admin::table.th>
                                                            @lang('dam::app.admin.dam.asset.edit.name')
                                                        </x-admin::table.th>

                                                        <x-admin::table.th>
                                                            @lang('dam::app.admin.dam.asset.edit.value')
                                                        </x-admin::table.th>
                                                    </x-admin::table.thead.tr>
                                            </x-admin::table.thead>
                                            @foreach ($asset->embeddedMetaInfo as $metaInfoName => $metaInfoValue)
                                                <x-admin::table.thead.tr
                                                    class="hover:bg-violet-50 hover:bg-opacity-50 dark:hover:bg-cherry-800"
                                                > 
                                                    <x-admin::table.td>
                                                        <p
                                                            class="dark:text-white" 
                                                        >
                                                        {{$metaInfoName}}
                                                        </p>

                                                    </x-admin::table.td>

                                                    <x-admin::table.td>
                                                        <p class="dark:text-white">
                                                            @if (is_array($metaInfoValue))
                                                                <!-- You can add logic to display the array content if needed -->
                                                                @foreach ($metaInfoValue as $label => $value)
                                                                    <tr
                                                                        class="hover:bg-violet-50 hover:bg-opacity-50 dark:hover:bg-cherry-800"
                                                                    > 
                                                                        <x-admin::table.td>{{ $label }}</x-admin::table.td>
                                                                        <x-admin::table.td>{{ $value }}</x-admin::table.td>
                                                                    </tr>
                                                                @endforeach
                                                            @else
                                                                @if ('FileDateTime' == $metaInfoName)
                                                                    {{ date('Y-m-d H:i:s', $metaInfoValue) }}
                                                                @else
                                                                    {{ $metaInfoValue }}
                                                                @endif
                                                            @endif
                                                        </p>
                                                    </x-admin::table.td>
                                                </x-admin::table.thead.tr>
                                            @endforeach                                
                                        </x-admin::table>
                                    </x-slot>
                                </x-admin::accordion>
        
                                {!! view_render_event('unopim.dam.asset.edit.card.accordian.embedded_meta_info.after', ['asset' => $asset]) !!}
                            @endif
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
                            console.log(error);
                            this.$emitter.emit('add-flash', {type: 'error', message: error.response.data.message});
                                console.error('Upload failed:', error);
                        });
                    },
                    removeTag(formData) {   
                        this.$axios.post("{{ route('admin.dam.assets.remove-tag') }}", formData).then((response) => {
                            this.$emitter.emit('uploaded-assets', response.data.file);

                        }).catch((error) => {
                            console.log(error);
                            this.$emitter.emit('add-flash', {type: 'error', message: error.response.data.message});
                                console.error('Upload failed:', error);
                        });
                    }
                }
            });
        </script>   

        <!-- **** Custom Download **** -->
        <script
            type="text/x-template"
            id="v-custom-download-template"
        >
            @if (bouncer()->hasPermission('dam.asset.download'))
                @if ($asset->file_type === 'image')
                    <button class="secondary-button" @click="customDownloadModel">
                        <span class="text-xl text-violet-700 icon-dam-download"></span>
                        <span>@lang('dam::app.admin.dam.asset.edit.button.custom_download')</span>    
                    </button>
                @else
                    <button class="secondary-button" @click="downloadItem">
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

        </script>

        <script type="module">
            app.component('v-custom-download', {
                template: '#v-custom-download-template',
                data() {
                    const selectedItem = @json($asset);
                    return {
                        selectedItem: selectedItem,
                        supportedExtensionTypes: [
                            {
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
                        selectedItemWidth: selectedItem?.embeddedMetaInfo?.COMPUTED?.Width,
                        selectedItemHeight: selectedItem?.embeddedMetaInfo?.COMPUTED?.Height,
                    };
                },
                methods: {
                    customDownloadModel() {                        
                        this.$refs.assetCustomDownloadModal.toggle();
                    },
                    customDownload(params, { resetForm, setErrors }) {
                        
                        const format = (() => {
                            try {
                                return JSON.parse(params.format).value;
                            } catch (e) {
                                return params.format;
                            }
                        })();

                        const formatHeight = params.height;
                        const formatWidth = params.width;

                        let downloadLink = `{{ route('admin.dam.assets.custom_download', '') }}/${this.selectedItem.id}?format=${format}&height=${formatHeight}&width=${formatWidth}`;

                        this.selectedItemExtension = this.selectedItem?.extension;
                        this.selectedItemWidth = this.selectedItem?.embeddedMetaInfo?.COMPUTED?.Width;
                        this.selectedItemHeight = this.selectedItem?.embeddedMetaInfo?.COMPUTED?.Height;

                        this.$refs.assetCustomDownloadModal.close();

                        window.open(downloadLink, '_self');
                    },
                    downloadItem() {
                        let downloadLink = `{{ route('admin.dam.assets.download', '') }}/${this.selectedItem.id}`;

                        window.open(downloadLink, '_self');
                    },
                }
            });
        </script>  

        <!-- **** Rename **** -->
        <script
            type="text/x-template"
            id="v-rename-asset-template"
        >
            @if (bouncer()->hasPermission('dam.asset.rename'))
                <button class="secondary-button" @click="renameItem">
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
                                @lang('Rename')
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
                                    @lang('File Name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="file_name"
                                    rules="required"
                                    :value="old('file_name')"
                                    v-model="selectedItem.file_name"
                                    ref="fileName"
                                    :label="trans('File Name')"
                                    :placeholder="trans('File Name')"
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
                                    @lang('Save')
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
                        selectedItem: @json($asset)
                    };
                }, 
                methods: {
                    renameItem() {
                        this.$refs.assetRenameModal.toggle();
                    },
                    focusNameInput() {
                        this.$nextTick(() => {
                            if (this.$refs.fileName) {
                                this.$refs.fileName.focus();
                            }
                        });
                    },
                    renameAsset(params, { resetForm, setErrors  }) {
                        let formData = new FormData(this.$refs.assetRenameForm);
                        
                        this.$axios.post("{{ route('admin.dam.assets.rename') }}", formData)
                            .then((response) => {
                                this.$refs.assetRenameModal.close();
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                resetForm();
                                location.reload();
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
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
                    @change="onFileChange"
                />
                <label
                    for="file-upload"
                    class="secondary-button cursor-pointer"
                >
                    <span class="text-xl text-violet-700 icon-dam-upload"></span>
                    <span>@lang('dam::app.admin.dam.asset.edit.button.re_upload')</span>
                </label>
            @endif
        </script>

        <script type="module">
            app.component('v-reupload-asset', {
                template: '#v-reupload-asset-template',
                data() {
                    return {
                        selectedItem: @json($asset)
                    };
                },
                methods: {
                    onFileChange(e) {
                        const fileInput = e.target.files;
    
                        if (fileInput.length > 0) {
                            const formData = new FormData();
                            
                            formData.append('file', fileInput[0]);

                            if (this.selectedItem) {
                                formData.append('asset_id', this.selectedItem.id);
                            }
                            
                            this.handleFileUpload(formData);
                        }
                    },
                    handleFileUpload(formData) {   
                        this.$axios.post("{{ route('admin.dam.assets.re_upload') }}", formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        }).then((response) => {
                            
                            location.reload();
                            this.$emitter.emit('uploaded-assets', response.data.file);
                            this.$emitter.emit('add-flash', {type: 'success', message: response.data.message});

                        }).catch((error) => {
                            console.log(error);
                            this.$emitter.emit('add-flash', {type: 'error', message: error.response.data.message});
                                console.error('Upload failed:', error);
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
                <button class="secondary-button" @click="deleteFile"> 
                    <span class="text-xl text-violet-700 icon-dam-delete"></span>
                    <span>@lang('dam::app.admin.dam.asset.edit.button.delete')</span>
                </button>
            @endif
        </script>

        <script type="module">
            app.component('v-delete-asset', {
                template: '#v-delete-asset-template',
                data() {
                    return {
                        selectedItem: @json($asset)
                    };
                },
                methods: {
                    deleteFile() {
                        this.$emitter.emit('open-delete-modal', {
                            agree: () => {
                                this.$axios.delete(`{{ route('admin.dam.assets.destroy', '') }}/${this.selectedItem.id}`)
                                    .then(response => {
                                        this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                        window.location.assign("{{ route('admin.dam.assets.index') }}");
                                    })
                                .catch((error) => {
                                    console.log(error);
                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
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
