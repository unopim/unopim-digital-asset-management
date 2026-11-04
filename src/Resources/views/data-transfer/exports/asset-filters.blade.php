{{--
    Injected via `unopim.admin.settings.data_transfer.exports.create.card.scope.after`.
    Rendered inside the #v-export-profile-template Vue scope, so `filterFields` and
    `entityType` resolve against the parent `v-export-profile` component. The card only
    appears when the selected exporter actually exposes the asset filters, so non-DAM
    exporters are unaffected.
--}}
<div
    v-if="filterFields.some(field => ['file_type', 'directories', 'tags'].includes(field.name))"
    class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
>
    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
        @lang('dam::app.data-transfer.exporters.assets.filters.title')
    </p>

    <x-admin::data-transfer.filter-fields
        ::entity-type="entityType"
        :exporter-config="config('exporters')"
        only="file_type,directories,tags"
        grid-class="grid grid-cols-1"
    />
</div>
