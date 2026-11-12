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
