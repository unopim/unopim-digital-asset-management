@props(['isMultiRow' => false])

@include('dam::components.shared.asset-card')

<v-gallery-table>
    {{ $slot }}
</v-gallery-table>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-gallery-table-template"
    >
        <div class="w-full">
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
                            @lang('dam::app.admin.dam.index.select-all')
                        </span>
                    </label>
                </div>
            </slot>

            <div
                class="grid grid-cols-2 md:!grid-cols-3 xl:!grid-cols-4 2xl:!grid-cols-5 gap-4"
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
                            <v-dam-asset-card
                                :asset="record"
                                :draggable="false"
                                @preview="previewImage(record.id)"
                                @edit="editImage(record.id)"
                                @delete="deleteImage(record.id)"
                            ></v-dam-asset-card>

                            <div class="flex gap-1.5 items-center mt-2">
                                <label
                                    v-if="$parent.available.massActions.length"
                                    :for="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                    class="flex items-center cursor-pointer shrink-0"
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
                                </label>

                                <h2
                                    class="text-xs sm:text-sm font-normal text-gray-600 dark:text-gray-300 truncate"
                                    v-text="record.file_name"
                                ></h2>
                            </div>
                        </div>
                    </template>
                </slot>
            </div>

            <template v-else>
                @include('dam::components.shared.empty-state')
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-gallery-table', {
            template: '#v-gallery-table-template',

            data() {
                return {};
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
                    this.$navigate(`{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', recordId));
                },

                previewImage(recordId) {
                    this.$emitter.emit('dam-open-preview', recordId);
                },
            },
        });
    </script>
@endpushOnce
