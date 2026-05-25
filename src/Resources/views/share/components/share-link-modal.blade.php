{{--
    Share-link modal. Triggered via:
        this.$emitter.emit('open-share-modal', { targetType: 'asset'|'directory', targetId: <id> })
--}}
<script
    type="text/x-template"
    id="v-share-link-modal-template"
>
    <div style="position: absolute; width: 0; height: 0; overflow: visible;">
        <x-admin::modal ref="shareModal">
            <x-slot:header>
                <p class="text-lg text-gray-800 dark:text-white font-bold">
                    @{{ headerLabel }}
                </p>
            </x-slot>

            <x-slot:content>
                <div class="flex flex-col gap-4">
                    <!-- Loading -->
                    <div v-if="isLoading" class="text-sm text-gray-500 dark:text-slate-400 py-4 text-center">
                        @lang('dam::app.admin.dam.share.modal.loading')
                    </div>

                    <template v-else>
                        <!-- ── Active: show URL row ── -->
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
                                @click="copyLink(currentShare.public_url)"
                            >
                                @lang('dam::app.admin.dam.share.modal.copy')
                            </button>
                        </div>

                        <!-- ── Revoked: notice + reauthorize / create new ── -->
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

                        <!-- ── No share: create button ── -->
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
                            <!-- Custom name -->
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

                            <!-- Expiry -->
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

                            <!-- Actions -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <!-- Save (active share) -->
                                <button
                                    v-if="currentShare && currentShare.status === 'active'"
                                    type="button"
                                    class="secondary-button"
                                    :disabled="isSaving"
                                    @click="saveAdvanced"
                                >
                                    @lang('dam::app.admin.dam.share.modal.save')
                                </button>

                                <!-- Create (no share yet) -->
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

                                <!-- Reauthorize (revoked share) -->
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

                                <!-- Revoke (active share) -->
                                <button
                                    v-if="currentShare && currentShare.status === 'active'"
                                    type="button"
                                    class="secondary-button !text-red-600 dark:!text-red-500"
                                    :disabled="isRevoking"
                                    @click="revoke(currentShare)"
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
    app.component('v-share-link-modal', {
        template: '#v-share-link-modal-template',
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
                // Use '' (empty string) as the no-expiry sentinel so track-by="value" works
                expiryOptions: [
                    { value: '',  label: @js(trans('dam::app.admin.dam.share.modal.no-expiry')) },
                    { value: 1,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-1d')) },
                    { value: 7,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-7d')) },
                    { value: 30,  label: @js(trans('dam::app.admin.dam.share.modal.expiry-30d')) },
                    { value: 365, label: @js(trans('dam::app.admin.dam.share.modal.expiry-365d')) },
                ],
                expiryOption: null,
                openHandler: null,
            };
        },
        computed: {
            headerLabel() {
                return this.targetType === 'directory'
                    ? @js(trans('dam::app.admin.dam.share.modal.title-directory'))
                    : @js(trans('dam::app.admin.dam.share.modal.title-asset'));
            },
        },
        mounted() {
            this.expiryOption = this.expiryOptions[0]; // no expiry default

            this.openHandler = ({ targetType, targetId } = {}) => {
                if (!targetType || !targetId) return;
                this.targetType = targetType;
                this.targetId = Number(targetId);
                this.currentShare = null;
                this.showAdvanced = false;
                this.advancedName = '';
                this.expiryOption = this.expiryOptions[0];
                this.$refs.shareModal.toggle();
                this.loadShares();
            };
            this.$emitter.on('open-share-modal', this.openHandler);
        },
        unmounted() {
            if (this.openHandler) {
                this.$emitter.off('open-share-modal', this.openHandler);
            }
        },
        methods: {
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
                        if (data?.share) {
                            this.currentShare = data.share;
                        }
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: data?.message ?? @js(trans('dam::app.admin.dam.share.updated')),
                        });
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
                        if (data?.share) {
                            this.currentShare = data.share;
                        }
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: data?.message ?? @js(trans('dam::app.admin.dam.share.reauthorized')),
                        });
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
            revoke(share) {
                if (this.isRevoking) return;
                this.isRevoking = true;
                const url = `{{ route('admin.dam.shares.destroy', ':id') }}`.replace(':id', share.id);

                this.$axios.delete(url)
                    .then(({ data }) => {
                        if (data?.success) {
                            // Keep currentShare so reauthorize is available; reload to get fresh status.
                            this.loadShares();
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: data.message ?? @js(trans('dam::app.admin.dam.share.modal.revoked')),
                            });
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
            copyLink(url) {
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
            formatDate(value) {
                if (!value) return '';
                try { return new Date(value).toLocaleString(); } catch (_) { return value; }
            },
        },
    });
</script>
