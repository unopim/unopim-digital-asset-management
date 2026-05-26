<x-admin::layouts>
    <x-slot:title>
        @lang('dam::app.admin.dam.share.index.title')
    </x-slot:title>

    {!! view_render_event('unopim.dam.shares.list.before') !!}

    <v-dam-shares-page>
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('dam::app.admin.dam.share.index.title')
            </p>
        </div>

        <p class="text-sm text-zinc-600 dark:text-slate-300 mt-2">
            @lang('dam::app.admin.dam.share.index.description')
        </p>

        <x-admin::shimmer.datagrid />
    </v-dam-shares-page>

    {!! view_render_event('unopim.dam.shares.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-dam-shares-page-template">
            <div>
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('dam::app.admin.dam.share.index.title')
                    </p>
                </div>

                <p class="text-sm text-zinc-600 dark:text-slate-300 mt-2">
                    @lang('dam::app.admin.dam.share.index.description')
                </p>

                <x-admin::datagrid src="{{ route('admin.dam.shares.index') }}" ref="datagrid">
                    <template #body="{ columns, records, performAction }">
                        <div
                            v-for="record in records"
                            :key="record.id"
                            class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                            :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        >
                            <p v-html="record.share_type" class="truncate"></p>
                            <p v-html="record.target_name" class="truncate"></p>
                            <p v-html="record.share_name" class="truncate"></p>
                            <p v-html="record.created_by_name" class="truncate"></p>
                            <p v-html="record.status"></p>
                            <p v-html="record.expires_at" class="truncate"></p>
                            <p v-html="record.view_count"></p>
                            <p v-html="record.download_count"></p>
                            <p v-html="record.created_at" class="truncate"></p>

                            <div class="flex justify-end" @click.stop>
                                <a
                                    v-if="record.actions.find(a => a.index === 'edit')"
                                    @click="editShare(record)"
                                >
                                    <span
                                        :class="record.actions.find(a => a.index === 'edit')?.icon"
                                        :title="record.actions.find(a => a.index === 'edit')?.title"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    ></span>
                                </a>

                                <a @click="copyShareLink(record.actions.find(a => a.index === 'copy_link')?.url)">
                                    <span
                                        :class="record.actions.find(a => a.index === 'copy_link')?.icon"
                                        :title="record.actions.find(a => a.index === 'copy_link')?.title"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    ></span>
                                </a>

                                <a
                                    v-if="record.actions.find(a => a.index === 'revoke')"
                                    @click="revokeShare(record.actions.find(a => a.index === 'revoke')?.url)"
                                >
                                    <span
                                        :class="record.actions.find(a => a.index === 'revoke')?.icon"
                                        :title="record.actions.find(a => a.index === 'revoke')?.title"
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

                <x-admin::modal ref="shareModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            @{{ headerLabel }}
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div class="flex flex-col gap-4">
                            <div v-if="isLoading" class="text-sm text-gray-500 dark:text-slate-400 py-4 text-center">
                                @lang('dam::app.admin.dam.share.modal.loading')
                            </div>

                            <template v-else>
                                <!-- Active: URL row -->
                                <div v-if="currentShare && currentShare.status === 'active'" class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        :value="currentShare.public_url"
                                        readonly
                                        class="flex-1 min-w-0 rounded-md border border-gray-300 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 focus:outline-none cursor-not-allowed"
                                    />
                                    <button
                                        type="button"
                                        class="secondary-button shrink-0"
                                        @click="copyShareLink(currentShare.public_url)"
                                    >
                                        @lang('dam::app.admin.dam.share.modal.copy')
                                    </button>
                                </div>

                                <!-- Revoked: notice + reauthorize -->
                                <div v-else-if="currentShare && currentShare.status === 'revoked'" class="flex flex-col gap-3">
                                    <div class="flex items-start gap-2 rounded-md border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-3 py-2">
                                        <span class="icon-warning text-lg text-amber-500 shrink-0 mt-px"></span>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            @lang('dam::app.admin.dam.share.modal.revoked-notice')
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="text"
                                            :value="currentShare.public_url"
                                            readonly
                                            class="flex-1 min-w-0 rounded-md border border-gray-200 dark:border-cherry-700 bg-gray-100 dark:bg-cherry-900 px-3 py-2 text-sm text-gray-400 dark:text-slate-500 line-through focus:outline-none cursor-not-allowed"
                                        />
                                        <button
                                            type="button"
                                            class="primary-button shrink-0"
                                            :disabled="isReauthorizing"
                                            @click="reauthorize"
                                        >
                                            <span v-if="!isReauthorizing">@lang('dam::app.admin.dam.share.modal.reauthorize')</span>
                                            <span v-else>@lang('dam::app.admin.dam.share.modal.reauthorizing')</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- No share: create button -->
                                <div v-else>
                                    <button
                                        type="button"
                                        class="primary-button"
                                        :disabled="isCreating || !targetId"
                                        @click="createShare"
                                    >
                                        <span v-if="!isCreating">@lang('dam::app.admin.dam.share.modal.create')</span>
                                        <span v-else>@lang('dam::app.admin.dam.share.modal.creating')</span>
                                    </button>
                                </div>

                                <!-- Advanced checkbox -->
                                <label class="flex items-center gap-2 cursor-pointer select-none mt-1">
                                    <input
                                        type="checkbox"
                                        class="peer hidden"
                                        v-model="showAdvanced"
                                    />
                                    <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer rounded-md text-2xl"></span>
                                    <span class="text-sm text-gray-600 dark:text-slate-300">
                                        @lang('dam::app.admin.dam.share.modal.advanced')
                                    </span>
                                </label>

                                <!-- Advanced section -->
                                <div
                                    v-if="showAdvanced"
                                    class="border border-gray-200 dark:border-cherry-700 rounded-md p-4 flex flex-col gap-4"
                                >
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">
                                            @lang('dam::app.admin.dam.share.modal.name-label')
                                        </label>
                                        <input
                                            type="text"
                                            v-model="advancedName"
                                            :placeholder="@js(trans('dam::app.admin.dam.share.modal.name-hint'))"
                                            class="w-full rounded-md border border-gray-300 dark:border-cherry-700 bg-white dark:bg-cherry-900 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 focus:outline-none focus:border-violet-500 dark:focus:border-violet-400"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">
                                            @lang('dam::app.admin.dam.share.modal.expiry')
                                        </label>
                                        <v-multiselect
                                            v-model="expiryOption"
                                            :options="expiryOptions"
                                            track-by="value"
                                            label="label"
                                            :allow-empty="false"
                                            :close-on-select="true"
                                            :clear-on-select="false"
                                            :searchable="false"
                                            :show-labels="false"
                                            :placeholder="@js(trans('dam::app.admin.dam.share.modal.expiry'))"
                                        ></v-multiselect>
                                    </div>

                                    <div class="flex items-center gap-2 flex-wrap">
                                        <button
                                            v-if="currentShare && currentShare.status === 'active'"
                                            type="button"
                                            class="secondary-button"
                                            :disabled="isSaving"
                                            @click="saveAdvanced"
                                        >
                                            @lang('dam::app.admin.dam.share.modal.save')
                                        </button>

                                        <button
                                            v-if="!currentShare"
                                            type="button"
                                            class="primary-button"
                                            :disabled="isCreating || !targetId"
                                            @click="createShare"
                                        >
                                            <span v-if="!isCreating">@lang('dam::app.admin.dam.share.modal.create')</span>
                                            <span v-else>@lang('dam::app.admin.dam.share.modal.creating')</span>
                                        </button>

                                        <button
                                            v-if="currentShare && currentShare.status === 'revoked'"
                                            type="button"
                                            class="primary-button"
                                            :disabled="isReauthorizing"
                                            @click="reauthorize"
                                        >
                                            <span v-if="!isReauthorizing">@lang('dam::app.admin.dam.share.modal.reauthorize')</span>
                                            <span v-else>@lang('dam::app.admin.dam.share.modal.reauthorizing')</span>
                                        </button>

                                        <button
                                            v-if="currentShare && currentShare.status === 'active'"
                                            type="button"
                                            class="secondary-button !text-red-600 dark:!text-red-500"
                                            :disabled="isRevoking"
                                            @click="revokeFromModal(currentShare)"
                                        >
                                            @lang('dam::app.admin.dam.share.modal.revoke')
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </div>
        </script>

        <script type="module">
            app.component('v-dam-shares-page', {
                template: '#v-dam-shares-page-template',

                data() {
                    return {
                        targetType: '',
                        targetId: null,
                        isLoading: false,
                        isCreating: false,
                        isSaving: false,
                        isRevoking: false,
                        isReauthorizing: false,
                        currentShare: null,
                        showAdvanced: false,
                        advancedName: '',
                        expiryOptions: [
                            { value: '',  label: @js(trans('dam::app.admin.dam.share.modal.no-expiry')) },
                            { value: 1,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-1d')) },
                            { value: 7,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-7d')) },
                            { value: 30,  label: @js(trans('dam::app.admin.dam.share.modal.expiry-30d')) },
                            { value: 365, label: @js(trans('dam::app.admin.dam.share.modal.expiry-365d')) },
                        ],
                        expiryOption: null,
                    };
                },

                computed: {
                    gridsCount() {
                        const cols = this.$refs.datagrid?.available?.columns?.length ?? 0;
                        const hasActions = (this.$refs.datagrid?.available?.actions?.length ?? 0) > 0;

                        return cols + (hasActions ? 1 : 0);
                    },

                    headerLabel() {
                        return this.targetType === 'directory'
                            ? @js(trans('dam::app.admin.dam.share.modal.title-directory'))
                            : @js(trans('dam::app.admin.dam.share.modal.title-asset'));
                    },
                },

                mounted() {
                    this.expiryOption = this.expiryOptions[0];
                },

                methods: {
                    editShare(record) {
                        this.targetType = (record.share_type || '').toLowerCase();
                        this.targetId = Number(record.target_id);
                        this.currentShare = null;
                        this.showAdvanced = false;
                        this.advancedName = '';
                        this.expiryOption = this.expiryOptions[0];
                        this.$refs.shareModal.toggle();
                        this.loadShares();
                    },

                    copyShareLink(url) {
                        if (!url) return;
                        const success = () => this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: @js(trans('dam::app.admin.dam.share.modal.copied')),
                        });
                        const fallback = () => {
                            const tmp = document.createElement('textarea');
                            tmp.value = url;
                            tmp.style.position = 'fixed';
                            tmp.style.opacity = '0';
                            document.body.appendChild(tmp);
                            tmp.focus();
                            tmp.select();
                            try { document.execCommand('copy'); success(); } catch (_) {}
                            document.body.removeChild(tmp);
                        };
                        if (navigator.clipboard?.writeText) {
                            navigator.clipboard.writeText(url).then(success).catch(fallback);
                        } else {
                            fallback();
                        }
                    },

                    revokeShare(url) {
                        if (!url) return;
                        this.$axios.patch(url)
                            .then(({ data }) => {
                                this.$emitter.emit('add-flash', {
                                    type: data?.success ? 'success' : 'error',
                                    message: data?.message ?? @js(trans('dam::app.admin.dam.share.modal.revoke-failed')),
                                });
                                if (data?.success) {
                                    this.$refs.datagrid.get();
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.revoke-failed')),
                                });
                            });
                    },

                    loadShares() {
                        if (!this.targetType || !this.targetId) return;
                        this.isLoading = true;
                        const url = `{{ route('admin.dam.shares.active_for_target', ['type' => '__type', 'targetId' => '__id']) }}`
                            .replace('__type', this.targetType)
                            .replace('__id', this.targetId);

                        this.$axios.get(url)
                            .then(({ data }) => {
                                const shares = data?.shares ?? [];
                                this.currentShare = shares.length ? shares[0] : null;
                                if (this.currentShare) {
                                    this.advancedName = this.currentShare.name || '';
                                    this.expiryOption = this.currentShare.expires_at
                                        ? (this.expiryOptions.find(o => o.value !== '') ?? this.expiryOptions[1])
                                        : this.expiryOptions[0];
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.load-failed')),
                                });
                            })
                            .finally(() => {
                                this.isLoading = false;
                            });
                    },

                    createShare() {
                        if (this.isCreating || !this.targetId) return;
                        this.isCreating = true;

                        const noExpiry = this.showAdvanced ? (this.expiryOption?.value === '') : true;
                        const payload = {
                            share_type: this.targetType,
                            target_id: this.targetId,
                            no_expiry: noExpiry ? 1 : 0,
                            expiry_days: noExpiry ? null : this.expiryOption?.value,
                            name: this.showAdvanced ? (this.advancedName?.trim() || null) : null,
                        };

                        this.$axios.post(`{{ route('admin.dam.shares.store') }}`, payload)
                            .then(({ data }) => {
                                if (data?.share) {
                                    this.currentShare = data.share;
                                    this.advancedName = data.share.name || '';
                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: @js(trans('dam::app.admin.dam.share.modal.created')),
                                    });
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.create-failed')),
                                });
                            })
                            .finally(() => {
                                this.isCreating = false;
                            });
                    },

                    saveAdvanced() {
                        if (this.isSaving || !this.currentShare?.update_url) return;
                        this.isSaving = true;

                        const noExpiry = this.expiryOption?.value === '';
                        this.$axios.patch(this.currentShare.update_url, {
                                name: this.advancedName?.trim() || null,
                                no_expiry: noExpiry ? 1 : 0,
                                expiry_days: noExpiry ? null : this.expiryOption?.value,
                            })
                            .then(({ data }) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: data?.message ?? @js(trans('dam::app.admin.dam.share.updated')),
                                });
                                this.$refs.shareModal.toggle();
                                this.$refs.datagrid.get();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.update-failed')),
                                });
                            })
                            .finally(() => {
                                this.isSaving = false;
                            });
                    },

                    reauthorize() {
                        if (this.isReauthorizing || !this.currentShare?.reauthorize_url) return;
                        this.isReauthorizing = true;

                        this.$axios.patch(this.currentShare.reauthorize_url)
                            .then(({ data }) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: data?.message ?? @js(trans('dam::app.admin.dam.share.reauthorized')),
                                });
                                this.$refs.shareModal.toggle();
                                this.$refs.datagrid.get();
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.reauthorize-failed')),
                                });
                            })
                            .finally(() => {
                                this.isReauthorizing = false;
                            });
                    },

                    revokeFromModal(share) {
                        if (this.isRevoking) return;
                        this.isRevoking = true;
                        const url = `{{ route('admin.dam.shares.revoke', ':id') }}`.replace(':id', share.id);

                        this.$axios.patch(url)
                            .then(({ data }) => {
                                if (data?.success) {
                                    this.$emitter.emit('add-flash', {
                                        type: 'success',
                                        message: data.message ?? @js(trans('dam::app.admin.dam.share.modal.revoked')),
                                    });
                                    this.$refs.shareModal.toggle();
                                    this.$refs.datagrid.get();
                                }
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.revoke-failed')),
                                });
                            })
                            .finally(() => {
                                this.isRevoking = false;
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
