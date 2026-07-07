

{{-- Register the persistent DAM upload manager component (template only). --}}
@once('dam-bulkedit-asset-drop-upload')
    <x-dam::asset.drop-upload />
@endonce


@once('dam-grid-preview-modal')
    @include('dam::asset.grid-preview-modal')
@endonce


<v-bulkedit-asset-picker></v-bulkedit-asset-picker>

@pushOnce('scripts')
    {{-- ============================ Spreadsheet cell ============================ --}}
    <script type="text/x-template" id="v-spreadsheet-asset-template">
        <div class="w-full h-full flex items-center gap-1.5 px-1">

            <div v-if="assets.length" class="flex-shrink-0 flex items-center gap-0.5">
                <div
                    v-for="asset in visibleAssets"
                    :key="asset.id"
                    class="w-6 h-6 rounded overflow-hidden border border-gray-200 dark:border-cherry-700"
                >
                    <img
                        :src="asset.url"
                        :alt="asset.file_name"
                        class="w-full h-full object-cover"
                        v-on:error="$event.target.style.display = 'none'"
                    />
                </div>

                <span
                    v-if="assetIds.length > visibleCount"
                    class="text-xs text-gray-400 px-0.5 leading-none"
                >…</span>
            </div>

            <input
                ref="input"
                type="text"
                :name="`${entityId}_${column.code}`"
                class="flex-1 min-w-0 text-xs text-gray-600 dark:text-gray-300 bg-transparent truncate focus:outline-none"
                readonly
            />

            <div class="flex items-center gap-0.5 flex-shrink-0">
                <span
                    @click="openPicker"
                    class="cursor-pointer text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 text-base icon-edit"
                ></span>

                <span
                    v-if="assetIds.length"
                    @click="clear"
                    class="cursor-pointer text-gray-400 hover:text-red-500 text-base icon-delete"
                ></span>
            </div>
        </div>
    </script>

    {{-- ========================= Shared picker modal ========================= --}}
    <script type="text/x-template" id="v-bulkedit-asset-picker-template">
        <div>
            {{-- Persistent DAM upload manager (owns the floating progress panel).
                 Kept outside the modal — whose content is v-if-destroyed on close —
                 so uploads keep running even if the modal is closed. --}}
            <v-dam-drop-upload class="hidden"></v-dam-drop-upload>

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
                                        <!-- Select asset -->
                                        <label :for="`mass_action_select_record_${record[meta.primary_column]}`" class="cursor-pointer">
                                            <div class="grid image-card relative overflow-hidden transition-all hover:border-gray-400 group">
                                                <img
                                                    :src="record.path"
                                                    :alt="record.file_name"
                                                    class="w-full h-full object-cover object-top"
                                                >

                                                {{-- Centred eye button on hover — opens the fullscreen
                                                     viewer. pointer-events-none on the overlay lets a
                                                     click elsewhere on the card still toggle selection;
                                                     only the button captures the click. --}}
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

        const damBulkAssetCache    = new Map();
        const damBulkAssetInflight = new Map();

        function damBulkFetchAssets($axios, url, ids) {
            const strIds = ids.map(String);
            const need   = strIds.filter(id => ! damBulkAssetCache.has(id) && ! damBulkAssetInflight.has(id));

            if (need.length) {
                const req = $axios.get(url, { params: { assetIds: need } })
                    .then(response => {
                        (response.data || []).forEach(asset => {
                            damBulkAssetCache.set(String(asset.id), asset);
                        });
                    })
                    .catch(error => { console.error(error); })
                    .finally(() => { need.forEach(id => damBulkAssetInflight.delete(id)); });

                need.forEach(id => damBulkAssetInflight.set(id, req));
            }

            const pending = strIds
                .map(id => damBulkAssetInflight.get(id))
                .filter(Boolean);

            return Promise.all(pending).then(() =>
                strIds.map(id => damBulkAssetCache.get(id)).filter(Boolean)
            );
        }

        app.component('v-spreadsheet-asset', {
            template: '#v-spreadsheet-asset-template',

            props: {
                isActive: Boolean,
                modelValue: {
                    type: [String, Array],
                    default: '',
                },
                entityId: Number,
                column: Object,
                attribute: Object,
            },

            data() {
                return {
                    assetIds: [],
                    assets: [],
                    visibleCount: 3,
                    fetchedKey: null,
                };
            },

            computed: {
                visibleAssets() {
                    return this.assets.slice(0, this.visibleCount);
                },
            },

            mounted() {
                this.normalize(this.modelValue);
                this.syncInput();
                this.fetchAssets();
            },

            watch: {
                modelValue(newVal) {
                    this.normalize(newVal);
                    this.syncInput();
                    this.fetchAssets();

                    // Reflect external changes (paste / fill-down / clear) into the
                    // save payload as an array of ids.
                    this.$emitter.emit('update-spreadsheet-data', {
                        value: this.assetIds,
                        entityId: this.entityId,
                        column: this.column,
                    });
                },
            },

            methods: {
                normalize(val) {
                    if (Array.isArray(val)) {
                        this.assetIds = val.map(v => String(v).trim()).filter(Boolean);
                    } else if (typeof val === 'string' && val) {
                        this.assetIds = val.split(',').map(v => v.trim()).filter(Boolean);
                    } else {
                        this.assetIds = [];
                    }
                },

                syncInput() {
                    if (this.$refs.input) {
                        const count = this.assetIds.length;
                        this.$refs.input.value = count ? `${count} asset(s)` : '';
                    }
                },

                fetchAssets() {
                    const ids = this.assetIds.slice();
                    const key = ids.join(',');

                    if (key === this.fetchedKey) {
                        return;
                    }

                    this.fetchedKey = key;

                    if (! ids.length) {
                        this.assets = [];

                        return;
                    }

                    damBulkFetchAssets(this.$axios, "{{ route('admin.dam.asset_picker.get_assets') }}", ids)
                        .then(list => {
                            // Guard against a stale response if the value changed meanwhile.
                            if (this.assetIds.join(',') !== key) {
                                return;
                            }

                            const byId = {};
                            list.forEach(asset => { byId[String(asset.id)] = asset; });

                            this.assets = ids.map(id => byId[String(id)]).filter(Boolean);
                        });
                },

                openPicker() {
                    this.$emitter.emit('bulkedit-asset-picker:open', {
                        // Picker record ids are numeric; match them so checkboxes pre-check.
                        ids: this.assetIds.map(id => (isNaN(id) ? id : Number(id))),
                        onAssign: ids => this.applyIds(ids),
                    });
                },

                applyIds(ids) {
                    this.assetIds = (ids || []).map(v => String(v).trim()).filter(Boolean);
                    this.syncInput();
                    this.fetchAssets();

                    // CSV keeps copy / paste / fill-down working like the gallery cell.
                    this.$emit('update:modelValue', this.assetIds.join(','));

                    this.$emitter.emit('update-spreadsheet-data', {
                        value: this.assetIds,
                        entityId: this.entityId,
                        column: this.column,
                    });
                },

                clear() {
                    this.applyIds([]);
                },

                updateValue(val) {
                    this.applyIds(Array.isArray(val) ? val : (val ? String(val).split(',') : []));
                },
            },
        });

        app.component('v-bulkedit-asset-picker', {
            template: '#v-bulkedit-asset-picker-template',

            data() {
                return {
                    // Callback that pushes the chosen ids back to the originating cell.
                    pendingAssign: null,

                    // Asset ids to pre-check once the picker grid loads.
                    currentAssets: [],

                    // Directory currently selected in the picker's tree — upload target.
                    pickerCurrentDirectory: null,

                    canUploadAssets: @js(bouncer()->hasPermission('dam.asset.upload')),
                };
            },

            mounted() {
                this.$emitter.on('bulkedit-asset-picker:open', this.openPicker);

                this.$emitter.on('change-datagrid', this.loadAssetValues);

                // Track the directory the picker is browsing so uploads land there.
                this.$emitter.on('current-directory', (dir) => { this.pickerCurrentDirectory = dir; });

                this.$emitter.on('dam:uploads-refresh', this.onUploadsRefresh);
            },

            methods: {

                openPreview(id) {
                    this.$emitter.emit('dam-open-preview', isNaN(id) ? id : Number(id));
                },

                openPicker({ ids, onAssign }) {
                    this.pendingAssign = typeof onAssign === 'function' ? onAssign : null;
                    this.currentAssets = (ids || []).slice();

                    // Reset any leftover selection if the picker is still mounted.
                    const datagrid = this.$refs.datagrid;
                    if (datagrid?.applied?.massActions) {
                        datagrid.applied.massActions.indices = [];
                    }

                    this.$refs.assetPickerModal.open();
                },


                loadAssetValues() {
                    if (this.currentAssets.length && this.$refs?.datagrid?.applied?.massActions?.indices) {
                        let selectedIndices = this.$refs.datagrid.applied.massActions.indices;

                        this.$refs.datagrid.applied.massActions.indices = [
                            ...this.currentAssets.filter(id => ! selectedIndices.includes(id)),
                            ...selectedIndices,
                        ];

                        this.currentAssets = [];

                        this.$refs.datagrid.setCurrentSelectionMode();
                    }
                },

                saveAssets() {
                    const indices = this.$refs.datagrid?.applied?.massActions?.indices ?? [];

                    if (typeof this.pendingAssign === 'function') {
                        this.pendingAssign(indices.slice());
                    }

                    this.pendingAssign = null;

                    this.$refs.assetPickerModal.close();
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
                    if (! this.pickerCurrentDirectory || this.pickerCurrentDirectory.id !== directoryId) {
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
