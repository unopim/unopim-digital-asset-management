{{--
    Share edit modal. Triggered via:
        this.$emitter.emit('open-share-edit-modal', { url, record })

    Reuses the shared share-form partial so the create + edit experiences
    stay 1:1 in sync.
--}}
<v-share-edit-modal></v-share-edit-modal>

@pushOnce('scripts')
<script type="text/x-template" id="v-share-edit-modal-template">
    <div style="position: absolute; width: 0; height: 0; overflow: visible;">
        <x-admin::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
            ref="shareEditForm"
        >
            <form @submit.prevent="handleSubmit($event, save)" ref="shareEditFormEl">
                <x-admin::modal ref="shareEditModal">
                    <x-slot:header>
                        <p class="text-lg text-gray-800 dark:text-white font-bold">
                            @lang('dam::app.admin.dam.share.modal.edit-title')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @include('dam::share.components._share-form-fields', [
                            'nameModel'        => 'name',
                            'expiryModel'      => 'expiryOption',
                            'expiryOptionsRef' => 'expiryOptions',
                            'noExpiryModel'    => 'noExpiry',
                            'disabledExpr'     => 'isSaving',
                        ])
                    </x-slot>

                    <x-slot:footer>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="transparent-button" @click="close">
                                @lang('dam::app.admin.dam.share.modal.cancel')
                            </button>
                            <button
                                type="submit"
                                class="primary-button"
                                :disabled="isSaving"
                                :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isSaving }"
                            >
                                <span v-if="isSaving">@lang('dam::app.admin.dam.share.modal.saving')</span>
                                <span v-else>@lang('dam::app.admin.dam.share.modal.save')</span>
                            </button>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </form>
        </x-admin::form>
    </div>
</script>

<script type="module">
    app.component('v-share-edit-modal', {
        template: '#v-share-edit-modal-template',
        data() {
            return {
                url:      null,
                isSaving: false,
                name:     '',
                noExpiry: false,
                expiryOption: null,
                expiryOptions: [
                    { value: 1,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-1d')) },
                    { value: 7,   label: @js(trans('dam::app.admin.dam.share.modal.expiry-7d')) },
                    { value: 30,  label: @js(trans('dam::app.admin.dam.share.modal.expiry-30d')) },
                    { value: 365, label: @js(trans('dam::app.admin.dam.share.modal.expiry-365d')) },
                ],
            };
        },
        mounted() {
            this._onOpen = (payload) => this.open(payload);
            this.$emitter.on('open-share-edit-modal', this._onOpen);
        },
        beforeUnmount() {
            if (this._onOpen) {
                this.$emitter.off('open-share-edit-modal', this._onOpen);
            }
        },
        methods: {
            /**
             * Pick the nearest expiry option to a future ISO timestamp.
             * If the date has passed (revoked-edge case) or isn't parseable,
             * fall back to 7 days.
             */
            optionForExpiry(expiresAt) {
                if (!expiresAt) return this.expiryOptions.find(o => o.value === 7) ?? this.expiryOptions[0];

                const target = new Date(expiresAt).getTime();
                if (isNaN(target)) return this.expiryOptions.find(o => o.value === 7) ?? this.expiryOptions[0];

                const daysLeft = Math.max(1, Math.round((target - Date.now()) / 86_400_000));

                // Snap to the closest predefined option (1 / 7 / 30 / 365).
                return this.expiryOptions.reduce((best, current) =>
                    Math.abs(current.value - daysLeft) < Math.abs(best.value - daysLeft) ? current : best
                , this.expiryOptions[0]);
            },

            open(payload) {
                const record = payload?.record || {};

                this.url      = payload?.url || null;
                this.name     = record.share_name || record.name || '';
                this.noExpiry = !record.expires_at
                    || record.expires_at === @js(trans('dam::app.admin.dam.share.datagrid.never'));
                this.expiryOption = this.optionForExpiry(this.noExpiry ? null : record.expires_at);

                this.$refs.shareEditModal?.toggle?.();
            },

            close() {
                this.$refs.shareEditModal?.toggle?.();
            },

            save() {
                if (!this.url || this.isSaving) return;

                this.isSaving = true;

                const payload = {
                    name:      this.name || null,
                    no_expiry: this.noExpiry,
                };

                if (!this.noExpiry) {
                    payload.expiry_days = this.expiryOption?.value ?? 7;
                }

                this.$axios.patch(this.url, payload)
                    .then(response => {
                        this.$emitter.emit('add-flash', {
                            type:    'success',
                            message: response.data.message,
                        });
                        this.close();
                        this.$emitter.emit('share-link-changed');
                    })
                    .catch(error => {
                        this.$emitter.emit('add-flash', {
                            type:    'error',
                            message: error.response?.data?.message
                                || @js(trans('dam::app.admin.dam.share.update-failed')),
                        });
                    })
                    .finally(() => {
                        this.isSaving = false;
                    });
            },
        },
    });
</script>
@endPushOnce
