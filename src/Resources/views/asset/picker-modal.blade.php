

@once('dam-grid-preview-modal')
    @include('dam::asset.grid-preview-modal')
@endonce

@once('dam-asset-picker-drop-upload')
    <x-dam::asset.drop-upload />
@endonce

@once('dam-asset-picker-persistent-upload')
    <v-dam-drop-upload class="hidden"></v-dam-drop-upload>
@endonce

@pushOnce('scripts')
    <script type="text/x-template" id="v-dam-asset-picker-template">
        <div>
            <x-dam::modal ref="assetPickerModal">
                <x-slot:header>
                    <div class="flex gap-x-2.5">
                        <span class="text-gray-800 dark:text-white font-semibold">
                            @lang('dam::app.admin.components.asset.field.assign-assets')
                        </span>
                    </div>
                </x-slot>

                <x-slot:content>
                    <v-dam-drop-upload
                        :current-directory="pickerCurrentDirectory"
                        :can-upload="canUploadAssets"
                    >
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
                                        <span class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-white">@lang("Select All")</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if (bouncer()->hasPermission('dam.asset.upload'))
                                            <input
                                                type="file"
                                                multiple
                                                name="picker_upload_files[]"
                                                :id="$.uid + '_pickerUpload'"
                                                class="hidden"
                                                @change="uploadToPicker"
                                            />
                                            <label
                                                :for="$.uid + '_pickerUpload'"
                                                class="secondary-button cursor-pointer"
                                            >
                                                <span>@lang('dam::app.admin.dam.index.upload')</span>
                                            </label>
                                        @endif

                                        @if (bouncer()->hasPermission('dam.asset_assign'))
                                            <span
                                                @click="saveAssets"
                                                class="secondary-button"
                                            >
                                                @lang('dam::app.admin.components.asset.field.assign-assets')
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </template>

                            <template #body="{ columns, records, performAction, setCurrentSelectionMode, meta, applied, isLoading }">
                                <template v-if="! isLoading && records.length">
                                    <div v-for="record in records">
                                        <label :for="`mass_action_select_record_${record[meta.primary_column]}`" class="cursor-pointer">
                                            <div class="grid image-card relative overflow-hidden transition-all hover:border-gray-400 group">
                                                <img
                                                    :src="record.path"
                                                    :alt="record.file_name"
                                                    loading="lazy"
                                                    decoding="async"
                                                    class="w-full h-full object-cover object-top"
                                                >

                                                <div class="absolute inset-0 flex items-center justify-center bg-black/60 dark:bg-cherry-800/70 transition-opacity opacity-0 group-hover:opacity-100 pointer-events-none">
                                                    <button
                                                        type="button"
                                                        class="icon-dam-preview text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-violet-600 transition-colors pointer-events-auto"
                                                        title="@lang('dam::app.admin.dam.asset.edit.preview-modal.card.preview')"
                                                        @click.stop.prevent="openPreview(record[meta.primary_column])"
                                                    ></button>
                                                </div>
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
                    </v-dam-drop-upload>
                </x-slot>
            </x-dam::modal>
        </div>
    </script>

    <script type="module">
        app.component('v-dam-asset-picker', {
            template: '#v-dam-asset-picker-template',

            data() {
                return {
                    pendingAssign: null,
                    currentAssets: [],
                    pickerCurrentDirectory: null,
                    canUploadAssets: @js(bouncer()->hasPermission('dam.asset.upload')),
                };
            },

            mounted() {
                this.$emitter.on('dam-asset-picker:open', ({ ids, onAssign } = {}) => this.open(ids, onAssign));
                this.$emitter.on('change-datagrid', this.loadAssetValues);
                this.$emitter.on('current-directory', (dir) => { this.pickerCurrentDirectory = dir; });
                this.$emitter.on('dam:uploads-refresh', this.onUploadsRefresh);
            },

            methods: {
                open(ids = [], onAssign = null) {
                    this.pendingAssign = typeof onAssign === 'function' ? onAssign : null;
                    this.currentAssets = (ids || []).map(id => (isNaN(id) ? id : Number(id)));

                    const datagrid = this.$refs.datagrid;
                    if (datagrid?.applied?.massActions) {
                        datagrid.applied.massActions.indices = [];
                    }

                    this.$refs.assetPickerModal.open();
                },

                openPreview(id) {
                    this.$emitter.emit('dam-open-preview', isNaN(id) ? id : Number(id));
                },

                saveAssets() {
                    const indices = this.$refs.datagrid?.applied?.massActions?.indices ?? [];

                    if (typeof this.pendingAssign === 'function') {
                        this.pendingAssign(indices.slice());
                        this.pendingAssign = null;
                    } else {
                        this.$emit('assign', indices.slice());
                    }

                    this.$refs.assetPickerModal.close();
                },

                loadAssetValues() {
                    if (this.currentAssets.length && this.$refs?.datagrid?.applied?.massActions?.indices) {
                        const selectedIndices = this.$refs.datagrid.applied.massActions.indices;

                        this.$refs.datagrid.applied.massActions.indices = [
                            ...this.currentAssets.filter(id => ! selectedIndices.includes(id)),
                            ...selectedIndices,
                        ];

                        this.currentAssets = [];

                        this.$refs.datagrid.setCurrentSelectionMode();
                    }
                },

                uploadToPicker(e) {
                    const files = e.target.files;
                    if (! files || ! files.length) { e.target.value = ''; return; }

                    const dirId = this.pickerCurrentDirectory?.id;
                    if (! dirId) {
                        this.$emitter.emit('add-flash', { type: 'warning', message: 'Select a directory to upload into.' });
                        e.target.value = '';
                        return;
                    }

                    const items = Array.from(files).map(file => ({
                        file,
                        relativePath: file.name,
                        preserveRoot: false,
                    }));

                    this.$emitter.emit('dam:enqueue-upload', { items, folderPaths: [], targetDirId: dirId });

                    e.target.value = '';
                },

                onUploadsRefresh({ directoryId, assetIds = [] } = {}) {
                    if (directoryId == null) {
                        return;
                    }

                    const currentId = this.pickerCurrentDirectory?.id;
                    const isCurrent = currentId != null && Number(currentId) === Number(directoryId);

                    if (! isCurrent) {
                        this.$emitter.emit('dam:reveal-directory', { id: directoryId });

                        return;
                    }

                    const datagrid = this.$refs.datagrid;
                    if (! datagrid?.get) {
                        return;
                    }

                    Promise.resolve(datagrid.get()).then(() => {
                        if (! assetIds.length || ! datagrid.applied?.massActions) {
                            return;
                        }

                        const indices = datagrid.applied.massActions.indices;
                        assetIds.forEach(id => { if (! indices.includes(id)) indices.push(id); });
                        datagrid.setCurrentSelectionMode();
                    });
                },
            },
        });
    </script>
@endPushOnce
