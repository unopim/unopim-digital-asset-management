@props(['isMultiRow' => false])

<v-gallery-table>
    {{ $slot }}
</v-gallery-table>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-gallery-table-template"
    >
        <div class="w-full min-h-screen">
            <slot name="body-header">
                <div class="flex flex-row gap-2 items-center pb-4" v-if="$parent.available.records.length">
                    <p v-if="$parent.available.massActions.length" class="items-center">
                        <label for="mass_action_select_all_records">
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
                                :class="[
                                    $parent.applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checkbox-check peer-checked:text-violet-700 ' : (
                                        $parent.applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-violet-700' : ''
                                    ),
                                ]"
                            >
                            </span>
                            
                        </label>
                    </p>
                    <span class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-white"  >@lang("Select All")</span>
                </div>
            </slot>
            <div class="grid grid-cols-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols- gap-4" v-if="$parent.available.records.length">
                <slot name="body">
                    <template v-if="$parent.isLoading">
                        <x-admin::shimmer.datagrid.table.body :isMultiRow="$isMultiRow" />
                    </template>

                    <template v-else>
                        <template v-if="$parent.available.records.length">
                            <div
                                v-for="record in $parent.available.records"
                                >
                                <div class="grid image-card relative overflow-hidden transition-all hover:border-gray-400 group">                    
                                    <img
                                        :src="record.path"
                                        :alt="record.file_name"
                                        class="w-full h-full object-cover object-center"
                                    >
                                
                                    <!-- ################ -->
                                    <div class="flex flex-col justify-center invisible w-full p-3 bg-black dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
                                        <!-- Actions -->
                                        <div class="flex justify-center">
                                            <!-- delete icon -->
                                            @if (bouncer()->hasPermission('dam.asset.destroy'))
                                                <span 
                                                    class="icon-delete text-2xl p-1.5 rounded-md cursor-pointer text-white hover:text-cherry-800 hover:bg-violet-100 dark:hover:bg-black"
                                                    @click="deleteImage(record.id)"
                                                >
                                                </span>
                                            @endif

                                            <!-- edit icon -->
                                            @if (bouncer()->hasPermission('dam.asset.edit'))
                                                <div 
                                                    class="icon-edit text-2xl p-1.5 rounded-md cursor-pointer text-white hover:text-cherry-800 hover:bg-violet-100 dark:hover:bg-black"
                                                    @click="editImage(record.id)"
                                                >
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <!-- { "id": 3, "file_name": "image-2.jpg", "file_type": "image", "file_size": 421, "mime_type": "image/jpg", "extension": "jpg", "path": "/x/y/z3/image-2.jpg", "created_at": "2024-09-26 12:52:19", "updated_at": "2024-09-26 12:52:19", "actions": [] } -->
                                <!-- <a :href="'/admin/dam/assets/edit/' + record.id"> -->
                                
                                <!-- ########### -->
                                <div class="flex gap-2 items-center mt-2.5">
                                    <!-- Mass Actions -->
                                    <p v-if="$parent.available.massActions.length">
                                        <label :for="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`">
                                            <input
                                                type="checkbox"
                                                class="peer hidden"
                                                :name="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                                :value="record[$parent.available.meta.primary_column]"
                                                :id="`mass_action_select_record_${record[$parent.available.meta.primary_column]}`"
                                                v-model="$parent.applied.massActions.indices"
                                                @change="$parent.setCurrentSelectionMode"
                                            >

                                            <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer rounded-md text-2xl">
                                            </span>
                                        </label>
                                    </p>
                                    <h2 class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-white overflow-hidden" v-text="record.file_name"></h2>
                                </div>
                            </div>
                        </template>
                    </template>
                </slot>
            </div>

            <div class="flex flex-col px-4 py-4 justify-center gap-2 text-center items-center text-xl text-zinc-800 dark:text-slate-50 font-bold leading-normal m-auto" v-else>
                <p>
                    <img src="{{ unopim_asset('images/no-records-found.svg', 'dam') }}" class="" />
                </p>
                <p>
                    @lang('admin::app.components.datagrid.table.no-records-available')
                </p>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-gallery-table', {
            template: '#v-gallery-table-template',

            data: function() {
                return {

                }
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
                                .delete(`{{ route('admin.dam.assets.destroy', '') }}/${recordId}`)
                                .then(({
                                    data
                                }) => {
                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: 'successfully deleted'
                                    });
                                    this.$emitter.emit('delete-assets', {
                                        actionType: 'single-action'
                                    });
                                    this.$parent.get();
                                })
                                .catch(error => {
                                    console.log(error);
                                    this.$emitter.emit('add-flash', {
                                        type: 'error',
                                        message: error?.response?.data?.message
                                    });
                                });
                        }
                    });

                    this.closeContextMenu();
                },
                closeContextMenu() {
                    this.showContextMenuFlag = false;
                    document.removeEventListener('click', this.closeContextMenu);
                },
                editImage(recordId) {
                    window.location.href = `{{ route('admin.dam.assets.edit', '') }}/${recordId}`;
                }
            }
        });
    </script>
@endpushOnce
