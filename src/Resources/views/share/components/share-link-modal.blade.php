{{--
    Singleton share-link modal. Triggered via:
        this.$emitter.emit('open-share-modal', { targetType: 'asset'|'directory', targetId: <id> })

    Each asset/directory has at most ONE share row. Modal renders one of four states:
      - no share yet         → Generate Link
      - active               → Revoke; Generate disabled
      - revoked + renewable  → Enable Link (keeps original token & URL)
      - expired              → Renew Link (mints new token; old URL dies)

    Included once on pages that need it (asset edit, DAM main page for directory tree).
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
                <div class="flex flex-col gap-5">
                    <!-- Create / Enable / Renew section -->
                    <div class="border border-gray-200 dark:border-cherry-800 rounded-md p-4">
                        <p class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">
                            @{{ topSectionLabel }}
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                            <!-- Link expires after -->
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <label class="text-xs font-medium text-gray-600 dark:text-slate-300">
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
                                    :searchable="true"
                                    :show-labels="false"
                                    :disabled="noExpiry || isBusy"
                                    :placeholder="@js(trans('dam::app.admin.dam.share.modal.expiry-search'))"
                                ></v-multiselect>
                            </div>

                            <!-- No expiry toggle -->
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <label class="text-xs font-medium text-gray-600 dark:text-slate-300">
                                    @lang('dam::app.admin.dam.share.modal.no-expiry')
                                </label>
                                <label class="flex items-center gap-2 h-10 px-3 rounded border border-gray-200 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="noExpiry"
                                        :disabled="isBusy"
                                        class="w-4 h-4 accent-violet-600"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-slate-200">
                                        @lang('dam::app.admin.dam.share.modal.no-expiry')
                                    </span>
                                </label>
                            </div>

                            <!-- Primary action button (Generate / Enable / Renew / Disabled) -->
                            <div class="flex flex-col gap-1.5 min-w-0">
                                <span class="text-xs font-medium text-gray-600 dark:text-slate-300 invisible select-none">&nbsp;</span>
                                <button
                                    type="button"
                                    class="secondary-button justify-center w-full h-10"
                                    :disabled="isBusy || !targetId || actionMode === 'disabled'"
                                    @click="primaryAction"
                                >
                                    <span class="icon-dam-link text-base"></span>
                                    <span v-if="isBusy">@{{ busyLabel }}</span>
                                    <span v-else>@{{ primaryLabel }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Current share -->
                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2">
                            @lang('dam::app.admin.dam.share.modal.current-link')
                        </p>

                        <div v-if="isLoading" class="text-sm text-gray-500 dark:text-slate-400 py-4 text-center">
                            @lang('dam::app.admin.dam.share.modal.loading')
                        </div>

                        <div v-else-if="!currentShare" class="text-sm text-gray-500 dark:text-slate-400 italic py-4 text-center">
                            @lang('dam::app.admin.dam.share.modal.no-active-links')
                        </div>

                        <div
                            v-else
                            class="border border-gray-200 dark:border-cherry-800 rounded-md p-3 flex flex-col gap-2"
                        >
                            <div class="flex gap-2 items-stretch">
                                <input
                                    type="text"
                                    readonly
                                    :value="currentShare.public_url"
                                    :class="[
                                        'flex-1 px-2 py-1.5 rounded border border-gray-200 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900 text-xs text-gray-700 dark:text-slate-200 truncate',
                                        currentShare.status !== 'active' ? 'opacity-60 line-through' : ''
                                    ]"
                                    @click="$event.target.select()"
                                />
                                <button
                                    type="button"
                                    class="secondary-button shrink-0"
                                    :disabled="currentShare.status !== 'active'"
                                    @click="copyLink(currentShare.public_url)"
                                >
                                    @lang('dam::app.admin.dam.share.modal.copy')
                                </button>
                                <button
                                    v-if="currentShare.status === 'active'"
                                    type="button"
                                    class="secondary-button !text-red-600 dark:!text-red-400 shrink-0"
                                    :disabled="isBusy"
                                    @click="revoke"
                                >
                                    @lang('dam::app.admin.dam.share.modal.revoke')
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-slate-400">
                                <span
                                    class="font-semibold"
                                    :class="{
                                        'text-emerald-600 dark:text-emerald-400': currentShare.status === 'active',
                                        'text-amber-600 dark:text-amber-400':     currentShare.status === 'revoked',
                                        'text-red-600 dark:text-red-400':         currentShare.status === 'expired',
                                    }"
                                >
                                    @{{ statusLabel(currentShare.status) }}
                                </span>
                                <span v-if="currentShare.expires_at">
                                    @lang('dam::app.admin.dam.share.modal.expires-on') @{{ formatDate(currentShare.expires_at) }}
                                </span>
                                <span v-else>@lang('dam::app.admin.dam.share.modal.never-expires')</span>
                                <span>@lang('dam::app.admin.dam.share.modal.views'): @{{ currentShare.view_count }}</span>
                                <span>@lang('dam::app.admin.dam.share.modal.downloads'): @{{ currentShare.download_count }}</span>
                            </div>
                        </div>
                    </div>
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
                isBusy: false,
                busyLabel: '',
                currentShare: null,
                expiryOptions: [
                    { value: 1,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-1d')) },
                    { value: 7,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-7d')) },
                    { value: 30,  label: @js(trans('dam::app.admin.dam.share.modal.expiry-30d')) },
                    { value: 365, label: @js(trans('dam::app.admin.dam.share.modal.expiry-365d')) },
                ],
                expiryOption: null,
                noExpiry: false,
                openHandler: null,
            };
        },
        computed: {
            headerLabel() {
                if (this.targetType === 'directory') {
                    return @js(trans('dam::app.admin.dam.share.modal.title-directory'));
                }
                return @js(trans('dam::app.admin.dam.share.modal.title-asset'));
            },
            expiryDays() {
                return this.expiryOption?.value ?? 7;
            },
            actionMode() {
                if (!this.currentShare) return 'generate';
                if (this.currentShare.status === 'active')  return 'disabled';
                if (this.currentShare.status === 'expired') return 'renew';
                if (this.currentShare.status === 'revoked') return this.currentShare.can_be_enabled ? 'enable' : 'renew';
                return 'generate';
            },
            primaryLabel() {
                switch (this.actionMode) {
                    case 'enable':   return @js(trans('dam::app.admin.dam.share.modal.enable'));
                    case 'renew':    return @js(trans('dam::app.admin.dam.share.modal.renew'));
                    case 'disabled': return @js(trans('dam::app.admin.dam.share.modal.link-generated'));
                    default:         return @js(trans('dam::app.admin.dam.share.modal.create'));
                }
            },
            topSectionLabel() {
                switch (this.actionMode) {
                    case 'enable':   return @js(trans('dam::app.admin.dam.share.modal.enable-section'));
                    case 'renew':    return @js(trans('dam::app.admin.dam.share.modal.renew-section'));
                    case 'disabled': return @js(trans('dam::app.admin.dam.share.modal.active-section'));
                    default:         return @js(trans('dam::app.admin.dam.share.modal.create-new'));
                }
            },
        },
        mounted() {
            this.expiryOption = this.expiryOptions.find(o => o.value === 7) ?? this.expiryOptions[0];

            this.openHandler = ({ targetType, targetId } = {}) => {
                if (!targetType || !targetId) return;
                this.targetType = targetType;
                this.targetId = Number(targetId);
                this.expiryOption = this.expiryOptions.find(o => o.value === 7) ?? this.expiryOptions[0];
                this.noExpiry = false;
                this.currentShare = null;
                this.$refs.shareModal.toggle();
                this.loadShare();
            };
            this.$emitter.on('open-share-modal', this.openHandler);
        },
        unmounted() {
            if (this.openHandler) {
                this.$emitter.off('open-share-modal', this.openHandler);
            }
        },
        methods: {
            primaryAction() {
                if (this.actionMode === 'enable') {
                    this.enable();
                    return;
                }
                this.createShare();
            },

            loadShare() {
                if (!this.targetType || !this.targetId) return;
                this.isLoading = true;
                const url = `{{ route('admin.dam.shares.active_for_target', ['type' => '__type', 'targetId' => '__id']) }}`
                    .replace('__type', this.targetType)
                    .replace('__id', this.targetId);

                this.$axios.get(url)
                    .then(({ data }) => {
                        this.currentShare = data?.share ?? null;
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
                if (this.isBusy || !this.targetId) return;
                this.isBusy = true;
                this.busyLabel = @js(trans('dam::app.admin.dam.share.modal.creating'));

                const payload = {
                    share_type:  this.targetType,
                    target_id:   this.targetId,
                    no_expiry:   this.noExpiry ? 1 : 0,
                    expiry_days: this.expiryDays,
                };

                this.$axios.post(`{{ route('admin.dam.shares.store') }}`, payload)
                    .then(({ data }) => {
                        if (data?.share) {
                            this.currentShare = data.share;
                            this.copyLink(data.share.public_url);
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
                        this.loadShare();
                    })
                    .finally(() => {
                        this.isBusy = false;
                        this.busyLabel = '';
                    });
            },

            enable() {
                if (this.isBusy || !this.currentShare) return;
                this.isBusy = true;
                this.busyLabel = @js(trans('dam::app.admin.dam.share.modal.enabling'));
                const url = `{{ route('admin.dam.shares.enable', ':id') }}`.replace(':id', this.currentShare.id);
                const payload = {
                    no_expiry:   this.noExpiry ? 1 : 0,
                    expiry_days: this.expiryDays,
                };

                this.$axios.post(url, payload)
                    .then(({ data }) => {
                        if (data?.share) {
                            this.currentShare = data.share;
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: data.message ?? @js(trans('dam::app.admin.dam.share.modal.enabled')),
                            });
                        }
                    })
                    .catch(error => {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.enable-failed')),
                        });
                        this.loadShare();
                    })
                    .finally(() => {
                        this.isBusy = false;
                        this.busyLabel = '';
                    });
            },

            revoke() {
                if (this.isBusy || !this.currentShare) return;
                this.isBusy = true;
                this.busyLabel = @js(trans('dam::app.admin.dam.share.modal.revoking'));
                const url = `{{ route('admin.dam.shares.destroy', ':id') }}`.replace(':id', this.currentShare.id);

                this.$axios.delete(url)
                    .then(({ data }) => {
                        if (data?.success) {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: data.message ?? @js(trans('dam::app.admin.dam.share.modal.revoked')),
                            });
                            this.loadShare();
                        }
                    })
                    .catch(error => {
                        this.$emitter.emit('add-flash', {
                            type: 'error',
                            message: error?.response?.data?.message ?? @js(trans('dam::app.admin.dam.share.modal.revoke-failed')),
                        });
                    })
                    .finally(() => {
                        this.isBusy = false;
                        this.busyLabel = '';
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
                    document.body.appendChild(tmp);
                    tmp.select();
                    try { document.execCommand('copy'); success(); } catch (_) {}
                    document.body.removeChild(tmp);
                };

                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(url).then(success).catch(fallback);
                } else {
                    fallback();
                }
            },

            statusLabel(status) {
                if (status === 'active')  return @js(trans('dam::app.admin.dam.share.modal.status-active'));
                if (status === 'revoked') return @js(trans('dam::app.admin.dam.share.modal.status-revoked'));
                if (status === 'expired') return @js(trans('dam::app.admin.dam.share.modal.status-expired'));
                return status;
            },

            formatDate(value) {
                if (!value) return '';
                try {
                    return new Date(value).toLocaleString();
                } catch (_) {
                    return value;
                }
            },
        },
    });
</script>
