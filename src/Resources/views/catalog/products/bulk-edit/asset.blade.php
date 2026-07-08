@include('dam::asset.picker-modal')

<v-dam-asset-picker></v-dam-asset-picker>

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
                    this.$emitter.emit('dam-asset-picker:open', {
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

        /**
         * Teach the core bulk-edit cell how to render DAM asset attributes.
         *
         * `admin::components.bulkedit.cell` maps an attribute type to its cell
         * component via `getComponentType(type)`, whose switch has no `asset`
         * case and falls back to a plain text cell. We augment that method here
         * (the `v-spreadsheet-asset` component is registered above) instead of
         * editing the core file. This module runs before `app.mount('#app')`
         * (fired on window `load`), so every asset cell picks up the mapping on
         * its first render.
         */
        const damCellComponent = app.component('v-spreadsheet-cell');

        if (damCellComponent?.methods?.getComponentType) {
            const damOriginalGetComponentType = damCellComponent.methods.getComponentType;

            damCellComponent.methods.getComponentType = function (type) {
                switch (type) {
                    case 'asset': return 'v-spreadsheet-asset';
                }

                return damOriginalGetComponentType.call(this, type);
            };
        }
    </script>
@endPushOnce
