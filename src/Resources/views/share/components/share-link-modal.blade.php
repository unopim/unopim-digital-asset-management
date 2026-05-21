{{--
    Singleton share-link modal. Triggered via:
        this.$emitter.emit('open-share-modal', { targetType: 'asset'|'directory', targetId: <id> })

    Provides:
      - Form to mint a new share (expiry: 1/7/30/365 days or never)
      - List of currently-active shares for that target with copy + revoke

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
                    <!-- Create new share -->
                    <div class="border border-gray-200 dark:border-cherry-800 rounded-md p-4">
                        <p class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">
                            @lang('dam::app.admin.dam.share.modal.create-new')
                        </p>

                        @include('dam::share.components._share-form-fields', [
                            'nameModel'        => 'newShareName',
                            'expiryModel'      => 'expiryOption',
                            'expiryOptionsRef' => 'expiryOptions',
                            'noExpiryModel'    => 'noExpiry',
                            'disabledExpr'     => 'isCreating',
                        ])

                        <div class="flex justify-end mt-3">
                            <button
                                type="button"
                                class="secondary-button h-10"
                                :disabled="isCreating || !targetId"
                                @click="createShare"
                            >
                                <span class="icon-dam-link text-base"></span>
                                <span v-if="!isCreating">@lang('dam::app.admin.dam.share.modal.create')</span>
                                <span v-else>@lang('dam::app.admin.dam.share.modal.creating')</span>
                            </button>
                        </div>
                    </div>

                    <!-- Active shares -->
                    <div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2">
                            @lang('dam::app.admin.dam.share.modal.active-links')
                            <span class="text-xs text-gray-500 dark:text-slate-400 font-normal">(@{{ activeShares.length }})</span>
                        </p>

                        <div v-if="isLoading" class="text-sm text-gray-500 dark:text-slate-400 py-4 text-center">
                            @lang('dam::app.admin.dam.share.modal.loading')
                        </div>

                        <div v-else-if="!activeShares.length" class="text-sm text-gray-500 dark:text-slate-400 italic py-4 text-center">
                            @lang('dam::app.admin.dam.share.modal.no-active-links')
                        </div>

                        <ul v-else class="flex flex-col gap-2">
                            <li
                                v-for="share in activeShares"
                                :key="share.id"
                                class="border border-gray-200 dark:border-cherry-800 rounded-md p-3 flex flex-col gap-2"
                            >
                                <div class="flex gap-2 items-center">
                                    <div v-if="renamingId === share.id" class="flex-1 min-w-0" @keydown.enter="saveRename(share)" @keydown.esc="cancelRename">
                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="rename_draft"
                                            v-model="renameDraft"
                                            :placeholder="trans('dam::app.admin.dam.share.modal.name-label')"
                                            ::disabled="savingRename"
                                        />
                                    </div>
                                    <span v-else class="flex-1 text-sm font-semibold text-gray-800 dark:text-white truncate" v-text="share.name || @js(trans('dam::app.admin.dam.share.modal.unnamed-share'))"></span>

                                    <button
                                        v-if="renamingId === share.id"
                                        type="button"
                                        class="secondary-button shrink-0"
                                        :disabled="savingRename"
                                        @click="saveRename(share)"
                                    >
                                        @lang('dam::app.admin.dam.share.modal.save')
                                    </button>
                                    <button
                                        v-if="renamingId === share.id"
                                        type="button"
                                        class="transparent-button shrink-0"
                                        @click="cancelRename"
                                    >
                                        @lang('dam::app.admin.dam.share.modal.cancel')
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="transparent-button shrink-0"
                                        title="@lang('dam::app.admin.dam.share.modal.edit-name')"
                                        @click="startRename(share)"
                                    >
                                        <span class="icon-edit text-lg"></span>
                                    </button>
                                </div>

                                <div class="flex gap-2 items-center">
                                    <div class="flex-1 min-w-0" @click.stop="$event.target.querySelector('input')?.select()">
                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="share_url"
                                            ::value="share.public_url"
                                            readonly
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="secondary-button shrink-0"
                                        @click="copyLink(share.public_url)"
                                    >
                                        @lang('dam::app.admin.dam.share.modal.copy')
                                    </button>
                                    <button
                                        type="button"
                                        class="secondary-button !text-red-600 dark:!text-red-400 shrink-0"
                                        :disabled="revokingId === share.id"
                                        @click="revoke(share)"
                                    >
                                        @lang('dam::app.admin.dam.share.modal.revoke')
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-slate-400">
                                    <span v-if="share.expires_at">
                                        @lang('dam::app.admin.dam.share.modal.expires-on') @{{ formatDate(share.expires_at) }}
                                    </span>
                                    <span v-else>@lang('dam::app.admin.dam.share.modal.never-expires')</span>
                                    <span>@lang('dam::app.admin.dam.share.modal.views'): @{{ share.view_count }}</span>
                                    <span>@lang('dam::app.admin.dam.share.modal.downloads'): @{{ share.download_count }}</span>
                                </div>
                            </li>
                        </ul>
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
                isCreating: false,
                revokingId: null,
                activeShares: [],
                expiryOptions: [
                    { value: 1,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-1d')) },
                    { value: 7,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-7d')) },
                    { value: 30,  label: @js(trans('dam::app.admin.dam.share.modal.expiry-30d')) },
                    { value: 365, label: @js(trans('dam::app.admin.dam.share.modal.expiry-365d')) },
                ],
                expiryOption: null,
                noExpiry: false,
                newShareName: '',
                renamingId: null,
                renameDraft: '',
                savingRename: false,
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
        },
        mounted() {
            this.expiryOption = this.expiryOptions.find(o => o.value === 7) ?? this.expiryOptions[0];

            this.openHandler = ({ targetType, targetId } = {}) => {
                if (!targetType || !targetId) return;
                this.targetType = targetType;
                this.targetId = Number(targetId);
                this.expiryOption = this.expiryOptions.find(o => o.value === 7) ?? this.expiryOptions[0];
                this.noExpiry = false;
                this.newShareName = '';
                this.renamingId = null;
                this.renameDraft = '';
                this.activeShares = [];
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
                        this.activeShares = data?.shares ?? [];
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

                const payload = {
                    share_type: this.targetType,
                    target_id: this.targetId,
                    no_expiry: this.noExpiry ? 1 : 0,
                    expiry_days: this.expiryDays,
                    name: this.newShareName?.trim() || null,
                };

                this.$axios.post(`{{ route('admin.dam.shares.store') }}`, payload)
                    .then(({ data }) => {
                        if (data?.share) {
                            this.activeShares.unshift(data.share);
                            this.copyLink(data.share.public_url);
                            this.newShareName = '';
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
            revoke(share) {
                if (this.revokingId) return;
                this.revokingId = share.id;
                const url = `{{ route('admin.dam.shares.destroy', ':id') }}`.replace(':id', share.id);

                this.$axios.delete(url)
                    .then(({ data }) => {
                        if (data?.success) {
                            this.activeShares = this.activeShares.filter(s => s.id !== share.id);
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
                        this.revokingId = null;
                    });
            },
            startRename(share) {
                this.renamingId = share.id;
                this.renameDraft = share.name || '';
            },
            cancelRename() {
                this.renamingId = null;
                this.renameDraft = '';
            },
            saveRename(share) {
                if (this.savingRename || !share.update_url) return;
                const trimmed = (this.renameDraft || '').trim();

                this.savingRename = true;
                this.$axios.patch(share.update_url, {
                        name: trimmed || null,
                    })
                    .then(({ data }) => {
                        if (data?.share) {
                            const idx = this.activeShares.findIndex(s => s.id === share.id);
                            if (idx !== -1) this.activeShares.splice(idx, 1, data.share);
                        }
                        this.renamingId = null;
                        this.renameDraft = '';
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
                        this.savingRename = false;
                    });
            },
            copyLink(url) {
                if (!url) return;
                const success = () => this.$emitter.emit('add-flash', {
                    type: 'success',
                    message: @js(trans('dam::app.admin.dam.share.modal.copied')),
                });

                const promise = navigator.clipboard?.writeText?.(url);
                if (promise && promise.then) {
                    promise.then(success).catch(() => {
                        const tmp = document.createElement('textarea');
                        tmp.value = url;
                        document.body.appendChild(tmp);
                        tmp.select();
                        try { document.execCommand('copy'); success(); } catch (_) {}
                        document.body.removeChild(tmp);
                    });
                }
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
