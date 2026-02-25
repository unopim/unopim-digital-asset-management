
<v-properties>
    {!! view_render_event('dam.admin.dam.properties.create.before') !!}
    <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('dam::app.admin.dam.asset.properties.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Create property Button -->
            @if (bouncer()->hasPermission('dam.asset.property.create'))
                <button
                    type="button"
                    class="primary-button"
                >
                    @lang('dam::app.admin.dam.asset.properties.index.create-btn')                    
                </button>
            @endif
        </div>
    </div>

    <!-- DataGrid Shimmer -->
    <x-admin::shimmer.datagrid />
</v-properties>

{!! view_render_event('dam.admin.dam.properties.create.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-properties-template"
    >
        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('dam::app.admin.dam.asset.properties.index.title')
            </p>

            <div class="flex gap-x-2 5 items-center">
                @if (bouncer()->hasPermission('dam.asset.property.create'))
                    <button
                        type="button"
                        class="primary-button"
                        @click="selectedProperties=0; selectedProperty={}; $refs.propertyUpdateOrCreateModal.toggle();codeIsNew=true;"
                    >
                        @lang('dam::app.admin.dam.asset.properties.index.create-btn')
                    </button>
                @endif
            </div>
        </div>

        @if (bouncer()->hasPermission('dam.asset.property.view'))
            <x-admin::datagrid
                :src="route('admin.dam.asset.properties.index', $id)"
                ref="datagrid"
            >
                <template #body="{ columns, records, performAction, available, selectAllRecords, setPropertySelectionMode, applied }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                    >
                        @if (bouncer()->hasPermission('dam.asset.property.delete'))
                            <p v-if="available.massActions.length">
                                <label :for="`mass_action_select_record_${record[available.meta.primary_column]}`">
                                    <input
                                        type="checkbox"
                                        class="peer hidden"
                                        :name="`mass_action_select_record_${record[available.meta.primary_column]}`"
                                        :value="record[available.meta.primary_column]"
                                        :id="`mass_action_select_record_${record[available.meta.primary_column]}`"
                                        v-model="applied.massActions.indices"
                                        @change="setCurrentSelectionMode"
                                    >

                                    <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer rounded-md text-2xl">
                                    </span>
                                </label>
                            </p>
                        @endif
                        

                        <p 
                            v-html="record.name"
                            class="break-words"
                        ></p>

                        <p 
                            v-html="record.type"
                            class="break-words"
                        ></p>

                        <p 
                            v-html="record.language"
                            class="break-words"
                        ></p>

                        <p 
                            v-html="record.value"
                            class="break-words"
                        ></p>

                        <div class="flex justify-end">
                            @if (bouncer()->hasPermission('dam.asset.property.update'))
                                <a @click="selectedProperties=1; editModel(record.actions.find(action => action.index === 'edit')?.url)">
                                    <span
                                        :class="record.actions.find(action => action.index === 'edit')?.icon"
                                        title="@lang('dam::app.admin.dam.asset.properties.index.datagrid.edit')"
                                        class="cursor-pointer icon-edit rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            @endif

                            @if (bouncer()->hasPermission('dam.asset.property.delete'))
                                <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                    <span
                                        :class="record.actions.find(action => action.index === 'delete')?.icon"
                                        title="@lang('dam::app.admin.dam.asset.properties.index.datagrid.delete')"
                                        class="cursor-pointer icon-delete rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>
        @endif

        <x-admin::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
            ref="modalForm"
        >
            <form
               @submit="handleSubmit($event, updateOrCreate)"
               ref="propertyCreateForm"
            >
                <x-admin::modal ref="propertyUpdateOrCreateModal">
                    <x-slot:header>
                        <p
                            class="text-lg text-gray-800 dark:text-white font-bold"
                            v-if="selectedProperties"
                        >
                            @lang('dam::app.admin.dam.asset.properties.index.edit.title')
                        </p>

                        <p
                            class="text-lg text-gray-800 dark:text-white font-bold"
                            v-else
                        >
                           @lang('dam::app.admin.dam.asset.properties.index.create.title')
                        </p>
                    </x-slot:header>

                    <x-slot:content>
                        {!! view_render_event('dam.admin.dam.properties.create.before') !!}
                        
                        <x-admin::form.control-group.control
                            type="hidden"
                            name="id"
                            v-model="selectedProperty.id"
                        />

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.asset.properties.index.create.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="name"
                                rules="required"
                                :value="old('name')"
                                v-model="selectedProperty.name"
                                :label="trans('dam::app.admin.dam.asset.properties.index.create.name')"
                                :placeholder="trans('dam::app.admin.dam.asset.properties.index.create.name')"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>


                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.asset.properties.index.create.type')
                            </x-admin::form.control-group.label>

                            @php
                                $supportedTypes = ['text'];

                                // @todo  need to do for other types 'textarea', 'price', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'gallery', 'file', 'checkbox'

                                $attributeTypes = [];

                                foreach($supportedTypes as $type) {
                                    $attributeTypes[] = [
                                        'id'    => $type,
                                        'label' => trans('admin::app.catalog.attributes.create.'. $type)
                                    ];
                                }

                                $attributeTypesJson = json_encode($attributeTypes);

                            @endphp

                            <x-admin::form.control-group.control
                                type="select"
                                name="type"
                                class="cursor-pointer"
                                rules="required"
                                :value="old('type')"
                                v-model="selectedProperty.type"
                                :label="trans('dam::app.admin.dam.asset.properties.index.create.type')"
                                :placeholder="trans('dam::app.admin.dam.asset.properties.index.create.type')"
                                :options="$attributeTypesJson"
                                track-by="id"
                                label-by="label"
                                ::disabled="true!== codeIsNew"
                            />

                            <x-admin::form.control-group.error control-name="type" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.asset.properties.index.create.language')
                            </x-admin::form.control-group.label>

                            @php
                                 $options = json_encode(core()->getAllActiveLocales()->toArray());
                            @endphp

                            <x-admin::form.control-group.control
                                type="select"
                                id="language"
                                name="language"
                                rules="required"
                                :options="$options"
                                :value="old('language')"
                                v-model="selectedProperty.language"
                                :label="trans('dam::app.admin.dam.asset.properties.index.create.language')"
                                :placeholder="trans('dam::app.admin.dam.asset.properties.index.create.language')"
                                track-by="id"
                                label-by="name"
                                ::disabled="true!== codeIsNew"
                            />
                            
                            <x-admin::form.control-group.error control-name="language" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('dam::app.admin.dam.asset.properties.index.create.value')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="value"
                                rules="required"
                                :value="old('value')"
                                v-model="selectedProperty.value"
                                :label="trans('dam::app.admin.dam.asset.properties.index.create.value')"
                                :placeholder="trans('dam::app.admin.dam.asset.properties.index.create.value')"
                            />

                            <x-admin::form.control-group.error control-name="value" />
                        </x-admin::form.control-group>
                    </x-slot:content>

                    <x-slot:footer>
                        <div class="flex gap-x2 5 items-center">
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang("dam::app.admin.dam.asset.properties.index.create.save-btn")
                            </button>
                        </div>
                    </x-slot:footer>
                </x-admin::modal>
            </form>
        </x-admin::form>
    </script>

    <script type="module">
        app.component('v-properties', {
            template: '#v-properties-template',

            data() {
                return {
                    selectedProperty: {},

                    codeIsNew: true,

                    selectedProperties: 0,
                }
            },

            computed: {
                gridsCount() {
                    let count = this.$refs.datagrid.available.columns.length;

                    if (this.$refs.datagrid.available.actions.length) {
                        ++count;
                    }

                    if (this.$refs.datagrid.available.massActions.length) {
                        ++count;
                    }

                    return count;
                },
            },

            methods: {
                updateOrCreate(params, { resetForm, setErrors }) {
                    let formData = new FormData(this.$refs.propertyCreateForm);

                    if (params.id) {
                        formData.append('_method', 'put');
                    }

                    this.$axios.post(params.id ? "{{ route('admin.dam.asset.properties.update',  $id) }}" : "{{ route('admin.dam.asset.property.store',  $id) }}", formData)
                    .then((response) => {
                        this.$refs.propertyUpdateOrCreateModal.close();

                        this.$refs.datagrid.get();

                        this.$emitter.emit('add-flash', { type: 'success', message: response.data.message })

                        resetForm();
                    })
                    .catch(error => {
                        console.log(error);
                        if (error.response.status === 422) {
                            setErrors(error.response.data.errors);
                        }
                    })
                },

                editModel(url) {
                
                    this.codeIsNew = false;

                    this.$axios.get(url)
                        .then((response) => {
                            this.selectedProperty = response.data;

                            this.$refs.propertyUpdateOrCreateModal.toggle();
                        })
                        .catch(error => {
                            
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message })
                        });
                },
            }
        })
    </script>
@endPushOnce
  


