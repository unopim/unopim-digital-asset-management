@php
    $damExporterConfig = config('exporters');
    $damExport = app(\Webkul\DataTransfer\Repositories\JobInstancesRepository::class)->find(request()->route('id'));
    $damEntityType = $damExport?->entity_type;
    $damAssetFilterFields = collect($damExporterConfig[$damEntityType]['filters']['fields'] ?? [])
        ->pluck('name')
        ->intersect(['file_type', 'directories', 'tags']);
@endphp

@if ($damExport && $damAssetFilterFields->isNotEmpty())
    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
        <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
            @lang('dam::app.data-transfer.exporters.assets.filters.title')
        </p>

        <x-admin::data-transfer.filter-fields
            :entity-type="$damEntityType"
            :values="$damExport->filters ?? []"
            :exporter-config="$damExporterConfig"
            only="file_type,directories,tags"
            grid-class="grid grid-cols-1"
        />
    </div>
@endif
