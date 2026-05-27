@props(['isMultiRow' => false])

<v-gallery-table>
    {{ $slot }}
</v-gallery-table>

@pushOnce('styles')
    {{-- Responsive grid overrides pushed after admin CSS so they win the cascade --}}
    <style>
        @media (min-width: 768px) {
            .dam-gallery-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1240px) {
            .dam-gallery-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (min-width: 1920px) {
            .dam-gallery-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }
    </style>
@endPushOnce

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-gallery-table-template"
    >
        <div class="w-full">
            <!-- Select All bar — only when mass actions are available -->
            <slot name="body-header">
                <div
                    class="flex flex-row gap-2 items-center pb-4"
                    v-if="$parent.available.records.length && $parent.available.massActions.length"
                >
                    <label
                        for="mass_action_select_all_records"
                        class="flex items-center gap-2 cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            name="mass_action_select_all_records"
                            id="mass_action_select_all_records"
                            class="peer hidden"
                            :checked="['all', 'partial'].includes($parent.applied.massActions.meta.mode)"
                            @change="$parent.selectAllRecords"
                        >

                        <span
                            class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                            :class="{
                                'peer-checked:icon-checkbox-check peer-checked:text-violet-700': $parent.applied.massActions.meta.mode === 'all',
                                'peer-checked:icon-checkbox-partial peer-checked:text-violet-700': $parent.applied.massActions.meta.mode === 'partial',
                            }"
                        ></span>

                        <span class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white">
                            @lang("Select All")
                        </span>
                    </label>
                </div>
            </slot>

            <!-- Records grid -->
            <div
                class="dam-gallery-grid grid grid-cols-2 gap-4"
                v-if="$parent.available.records.length"
            >
                <slot name="body">
                    <template v-if="$parent.isLoading">
                        <x-admin::shimmer.datagrid.table.body :isMultiRow="$isMultiRow" />
                    </template>

                    <template v-else>
                        <div
                            v-for="record in $parent.available.records"
                            :key="record.id"
                        >
                            <!-- Card -->
                            <div class="image-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900 transition-colors group">
                                <!-- Thumbnail -->
                                <img
                                    :src="getAssetSrc(record)"
                                    :alt="record.file_name"
                                    class="w-full h-full"
                                    :class="record.file_type === 'image' ? 'object-cover object-center' : 'object-contain p-4 sm:p-6'"
                                    v-on:error="onImageError($event, record)"
                                >

                                <!-- File-type badge (top-right) -->
                                <span
                                    v-if="record.extension"
                                    class="absolute top-1.5 right-1.5 z-10 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white shadow-md"
                                    :class="{
                                        'bg-violet-600': record.file_type === 'video' || record.file_type === 'audio',
                                        'bg-red-600':    (record.extension || '').toLowerCase() === 'pdf',
                                        'bg-gray-600':   record.file_type !== 'video' && record.file_type !== 'audio' && (record.extension || '').toLowerCase() !== 'pdf',
                                    }"
                                    v-text="(record.extension || '').toUpperCase()"
                                ></span>

                                <!-- Play / audio centred overlay -->
                                <div
                                    v-if="record.file_type === 'video' || record.file_type === 'audio'"
                                    class="absolute inset-0 flex items-center justify-center pointer-events-none"
                                >
                                    <span
                                        class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-black/55 text-white text-xl sm:text-2xl shadow-lg"
                                        :class="record.file_type === 'video' ? 'icon-play' : 'icon-information'"
                                        aria-hidden="true"
                                    ></span>
                                </div>

                                <!-- Action overlay: always visible on mobile, hover-only on sm+ -->
                                <div class="absolute inset-0 flex items-center justify-center bg-black/80 dark:bg-cherry-800/90 transition-opacity max-sm:opacity-100 opacity-0 group-hover:opacity-100">
                                    <div class="flex gap-1">
                                        @if (bouncer()->hasPermission('dam.asset.view'))
                                            <button
                                                type="button"
                                                class="icon-dam-preview text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-violet-600 transition-colors"
                                                title="@lang('dam::app.admin.dam.asset.edit.preview-modal.card.preview')"
                                                @click.stop="previewImage(record.id)"
                                            ></button>
                                        @endif

                                        @if (bouncer()->hasPermission('dam.asset.edit'))
                                            <button
                                                type="button"
                                                class="icon-edit text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-violet-600 transition-colors"
                                                title="@lang('dam::app.admin.dam.index.directory.actions.edit')"
                                                @click.stop="editImage(record.id)"
                                            ></button>
                                        @endif

                                        @if (bouncer()->hasPermission('dam.asset.destroy'))
                                            <button
                                                type="button"
                                                class="icon-delete text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-red-600 transition-colors"
                                                title="@lang('dam::app.admin.dam.index.directory.actions.delete')"
                                                @click.stop="deleteImage(record.id)"
                                            ></button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Filename + optional mass-action checkbox -->
                            <div class="flex gap-1.5 items-center mt-2">
                                <label
                                    v-if="$parent.available.massActions.length"
                                    :for="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                    class="flex gap-1.5 items-center cursor-pointer min-w-0"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer hidden"
                                        :name="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                        :value="record[$parent.available.meta.primary_column]"
                                        :id="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                        v-model="$parent.applied.massActions.indices"
                                        @change="$parent.setCurrentSelectionMode"
                                    >

                                    <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 rounded-md text-2xl shrink-0"></span>

                                    <span
                                        class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 truncate"
                                        v-text="record.file_name"
                                    ></span>
                                </label>

                                <span
                                    v-else
                                    class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 truncate block"
                                    v-text="record.file_name"
                                ></span>
                            </div>
                        </div>
                    </template>
                </slot>
            </div>

            <!-- Empty state -->
            <div
                class="flex flex-col items-center justify-center gap-4 py-16 text-center"
                v-else
            >
                <img
                    src="{{ unopim_asset('images/no-records-found.svg', 'dam') }}"
                    alt=""
                    class="w-32 h-32 opacity-60"
                />
                <p class="text-xl font-bold text-zinc-800 dark:text-slate-50">
                    @lang('admin::app.components.datagrid.table.no-records-available')
                </p>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-gallery-table', {
            template: '#v-gallery-table-template',

            data() {
                return {
                    assetPlaceholders: {
                        video:       '{{ unopim_asset('images/grid/video.svg', 'dam') }}',
                        audio:       '{{ unopim_asset('images/grid/audio.svg', 'dam') }}',
                        pdf:         '{{ unopim_asset('images/grid/file.svg', 'dam') }}',
                        spreadsheet: '{{ unopim_asset('images/grid/sheet.svg', 'dam') }}',
                        csv:         '{{ unopim_asset('images/grid/csv.svg', 'dam') }}',
                        document:    '{{ unopim_asset('images/grid/file.svg', 'dam') }}',
                        image:       '{{ unopim_asset('images/grid/image.svg', 'dam') }}',
                    },
                    fallbackSrc: '{{ unopim_asset('images/grid/unspecified.svg', 'dam') }}',
                };
            },

            computed: {
                gridsCount() {
                    let count = this.$parent.available.columns.length;

                    if (this.$parent.available.actions.length) {
                        ++count;
                    }

                    if (this.$parent.available.massActions.length) {
                        ++count;
                    }

                    return count;
                },
            },

            methods: {
                getAssetSrc(record) {
                    if (record.file_type === 'image') {
                        return record.path;
                    }

                    return this.assetPlaceholders[record.file_type] ?? this.fallbackSrc;
                },

                onImageError(event, record) {
                    event.target.src = this.assetPlaceholders[record.file_type] ?? this.fallbackSrc;
                    event.target.classList.remove('object-cover', 'object-center');
                    event.target.classList.add('object-contain', 'p-4');
                },

                deleteImage(recordId) {
                    this.$emitter.emit('open-delete-modal', {
                        agree: () => {
                            this.$axios
                                .delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', recordId))
                                .then(({ data }) => {
                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: data.message,
                                    });

                                    this.$emitter.emit('delete-assets', {
                                        actionType: 'single-action',
                                        count: 1,
                                    });

                                    this.$parent.get();
                                })
                                .catch(error => {
                                    this.$emitter.emit('add-flash', {
                                        type: 'error',
                                        message: error?.response?.data?.message,
                                    });
                                });
                        }
                    });
                },

                editImage(recordId) {
                    window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', recordId);
                },

                previewImage(recordId) {
                    this.$emitter.emit('dam-open-preview', recordId);
                },
            },
        });
    </script>
@endpushOnce
