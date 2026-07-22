<x-admin::layouts>
    <x-slot:title>
        @lang('dam::app.admin.dam.tag.index.title')
    </x-slot:title>

    {!! view_render_event('unopim.dam.tags.list.before') !!}

    <v-dam-tags-page>
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('dam::app.admin.dam.tag.index.title')
            </p>
        </div>

        <x-admin::shimmer.datagrid />
    </v-dam-tags-page>

    {!! view_render_event('unopim.dam.tags.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-dam-tags-page-template">
            <div>
                <div class="flex justify-between items-center gap-4 flex-wrap">
                    <div>
                        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                            @lang('dam::app.admin.dam.tag.index.title')
                        </p>

                        <p class="text-sm text-zinc-600 dark:text-slate-300 mt-2">
                            @lang('dam::app.admin.dam.tag.index.description')
                        </p>
                    </div>

                    @if (bouncer()->hasPermission('dam.tags.create'))
                        <button type="button" class="primary-button" @click="openCreate">
                            @lang('dam::app.admin.dam.tag.index.create')
                        </button>
                    @endif
                </div>

                <x-admin::datagrid src="{{ route('admin.dam.tags.index') }}" ref="datagrid">
                    <template #header="{ columns, actions, applied, sortPage, selectAllRecords, available, isLoading }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.head />
                        </template>

                        <template v-else>
                            <div
                                class="row grid gap-2.5 min-h-[47px] px-4 py-2.5 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 bg-violet-50 dark:bg-cherry-900 font-semibold items-center"
                                :style="`grid-template-columns: 60px repeat(${columns.filter(c => c.visible !== false).length}, minmax(80px, 1fr)) minmax(120px, 1fr)`"
                            >
                                <p v-if="available.massActions.length" class="flex items-center">
                                    <label class="cursor-pointer">
                                        <input
                                            type="checkbox"
                                            class="peer hidden"
                                            :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                            @change="selectAllRecords"
                                        >
                                        <span
                                            class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                                            :class="{
                                                'peer-checked:icon-checkbox-check peer-checked:text-violet-700': applied.massActions.meta.mode === 'all',
                                                'peer-checked:icon-checkbox-partial peer-checked:text-violet-700': applied.massActions.meta.mode === 'partial',
                                            }"
                                        ></span>
                                    </label>
                                </p>
                                <p v-else></p>

                                <p
                                    v-for="column in columns.filter(c => c.visible !== false)"
                                    class="flex gap-1.5 items-center min-w-0"
                                    :class="{'cursor-pointer select-none hover:text-gray-800 dark:hover:text-white': column.sortable}"
                                    @click="sortPage(column)"
                                >
                                    <span
                                        class="block overflow-hidden text-ellipsis text-nowrap"
                                        :title="column.label"
                                        v-text="column.label"
                                    ></span>

                                    <i
                                        class="text-base text-gray-600 dark:text-gray-300 align-text-bottom"
                                        :class="[applied.sort.order === 'asc' ? 'icon-down-stat' : 'icon-up-stat']"
                                        v-if="column.index == applied.sort.column"
                                    ></i>
                                </p>

                                <div v-if="actions.length" class="flex gap-2.5 items-center justify-end select-none">
                                    <p class="text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.components.datagrid.table.actions')
                                    </p>
                                </div>
                            </div>
                        </template>
                    </template>

                    <template #body="{ columns, records, performAction, applied, available }">
                        <div
                            v-for="record in records"
                            :key="record.id"
                            class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                            :style="`grid-template-columns: 60px repeat(${columns.filter(c => c.visible !== false).length}, minmax(80px, 1fr)) minmax(120px, 1fr)`"
                        >
                            <p v-if="available.massActions.length" @click.stop>
                                <label class="cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="peer hidden"
                                        :value="record.id"
                                        v-model="applied.massActions.indices"
                                    >
                                    <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer rounded-md text-2xl"></span>
                                </label>
                            </p>
                            <p v-else></p>

                            <p class="truncate" :title="record.name">@{{ record.name }}</p>
                            <p>@{{ record.assets_count }}</p>
                            <p class="truncate">@{{ record.created_at }}</p>

                            <div class="flex justify-end whitespace-nowrap" @click.stop>
                                <a
                                    v-if="record.actions.find(a => a.index === 'edit')"
                                    @click="editTag(record)"
                                >
                                    <span
                                        :class="record.actions.find(a => a.index === 'edit')?.icon"
                                        :title="record.actions.find(a => a.index === 'edit')?.title"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    ></span>
                                </a>

                                <a
                                    v-if="record.actions.find(a => a.index === 'delete')"
                                    @click="performAction(record.actions.find(a => a.index === 'delete'))"
                                >
                                    <span
                                        :class="record.actions.find(a => a.index === 'delete')?.icon"
                                        :title="record.actions.find(a => a.index === 'delete')?.title"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    ></span>
                                </a>
                            </div>
                        </div>
                    </template>
                </x-admin::datagrid>

                <x-admin::modal ref="tagModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            @{{ isEditing ? @js(trans('dam::app.admin.dam.tag.modal.edit-title')) : @js(trans('dam::app.admin.dam.tag.modal.create-title')) }}
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div class="flex flex-col gap-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">
                                @lang('dam::app.admin.dam.tag.modal.name-label')
                            </label>

                            <input
                                type="text"
                                v-model="form.name"
                                :placeholder="@js(trans('dam::app.admin.dam.tag.modal.name-placeholder'))"
                                class="w-full rounded-md border bg-white dark:bg-cherry-900 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 focus:outline-none focus:border-violet-500 dark:focus:border-violet-400"
                                :class="error ? 'border-red-500 dark:border-red-500' : 'border-gray-300 dark:border-cherry-700'"
                                @keyup.enter="saveTag"
                                @input="error = ''"
                            />

                            <p v-if="error" class="text-xs text-red-600 dark:text-red-400" v-text="error"></p>

                            <div class="flex items-center justify-end gap-2 mt-2">
                                <button type="button" class="secondary-button" @click="$refs.tagModal.toggle()">
                                    @lang('dam::app.admin.dam.tag.modal.cancel')
                                </button>

                                <button type="button" class="primary-button" :disabled="isSaving" @click="saveTag">
                                    <span v-if="!isSaving">@lang('dam::app.admin.dam.tag.modal.save')</span>
                                    <span v-else>@lang('dam::app.admin.dam.tag.modal.saving')</span>
                                </button>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </div>
        </script>

        <script type="module">
            app.component('v-dam-tags-page', {
                template: '#v-dam-tags-page-template',

                data() {
                    return {
                        isEditing: false,
                        isSaving: false,
                        editingId: null,
                        error: '',
                        form: { name: '' },
                    };
                },

                methods: {
                    openCreate() {
                        this.isEditing = false;
                        this.editingId = null;
                        this.error = '';
                        this.form.name = '';
                        this.$refs.tagModal.toggle();
                    },

                    editTag(record) {
                        this.isEditing = true;
                        this.editingId = record.id;
                        this.error = '';
                        this.form.name = record.name;
                        this.$refs.tagModal.toggle();
                    },

                    saveTag() {
                        if (this.isSaving) return;

                        const name = (this.form.name || '').trim();

                        if (! name) {
                            this.error = @js(trans('dam::app.admin.dam.tag.modal.name-label'));
                            return;
                        }

                        this.isSaving = true;

                        const request = this.isEditing
                            ? this.$axios.put(`{{ route('admin.dam.tags.update', ':id') }}`.replace(':id', this.editingId), { name })
                            : this.$axios.post(`{{ route('admin.dam.tags.store') }}`, { name });

                        request
                            .then(({ data }) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: data.message });
                                this.$refs.tagModal.toggle();
                                this.$refs.datagrid.get();
                            })
                            .catch(error => {
                                const resp = error?.response?.data;
                                this.error = resp?.errors?.name?.[0]
                                    ?? resp?.message
                                    ?? @js(trans('dam::app.admin.dam.tag.modal.name-label'));
                            })
                            .finally(() => {
                                this.isSaving = false;
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
