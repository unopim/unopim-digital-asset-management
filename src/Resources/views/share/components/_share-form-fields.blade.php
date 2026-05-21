{{--
    Shared share-link form fields (custom name + expiry select + no-expiry toggle).
    Used by both the create section of share-link-modal and the share-edit-modal so
    the create and edit experiences stay 1:1 in sync.

    Each call passes the Vue data-paths to bind to, so the partial doesn't make
    assumptions about the host component's data shape.

    Required vars:
      $nameModel        — v-model path for the custom-name input            (e.g. 'newShareName')
      $expiryModel      — v-model path for the expiry multiselect option    (e.g. 'expiryOption')
      $expiryOptionsRef — Vue array reference for the expiry options        (e.g. 'expiryOptions')
      $noExpiryModel    — v-model path for the no-expiry checkbox           (e.g. 'noExpiry')
      $disabledExpr     — Vue expression that disables the form while busy  (e.g. 'isCreating')
--}}
<div class="flex flex-col gap-4">
    {{-- Custom name --}}
    <x-admin::form.control-group>
        <x-admin::form.control-group.label>
            @lang('dam::app.admin.dam.share.modal.name-label')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="text"
            name="share_name"
            v-model="{{ $nameModel }}"
            :placeholder="trans('dam::app.admin.dam.share.modal.name-hint')"
            ::disabled="{{ $disabledExpr }}"
            rules="max:255"
        />

        <x-admin::form.control-group.error control-name="share_name" />
    </x-admin::form.control-group>

    {{-- Link expires after --}}
    <x-admin::form.control-group>
        <x-admin::form.control-group.label>
            @lang('dam::app.admin.dam.share.modal.expiry')
        </x-admin::form.control-group.label>

        <v-multiselect
            v-model="{{ $expiryModel }}"
            :options="{{ $expiryOptionsRef }}"
            track-by="value"
            label="label"
            :allow-empty="false"
            :close-on-select="true"
            :clear-on-select="false"
            :searchable="false"
            :show-labels="false"
            :disabled="{{ $noExpiryModel }} || {{ $disabledExpr }}"
            :placeholder="@js(trans('dam::app.admin.dam.share.modal.expiry'))"
        ></v-multiselect>
    </x-admin::form.control-group>

    {{-- No expiry toggle (UnoPim switch component) --}}
    <x-admin::form.control-group>
        <div class="flex items-center gap-3">
            <x-admin::form.control-group.control
                type="switch"
                name="no_expiry"
                v-model="{{ $noExpiryModel }}"
                ::disabled="{{ $disabledExpr }}"
            />
            <x-admin::form.control-group.label class="!mb-0 cursor-pointer">
                @lang('dam::app.admin.dam.share.modal.no-expiry')
            </x-admin::form.control-group.label>
        </div>
    </x-admin::form.control-group>
</div>
