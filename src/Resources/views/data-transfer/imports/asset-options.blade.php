@php
    $conflictOptions = json_encode([
        ['id' => 'skip', 'label' => trans('dam::app.data-transfer.importers.assets.options.conflict.skip')],
        ['id' => 'overwrite', 'label' => trans('dam::app.data-transfer.importers.assets.options.conflict.overwrite')],
        ['id' => 'update_metadata', 'label' => trans('dam::app.data-transfer.importers.assets.options.conflict.update-metadata')],
    ]);

    $damIsEdit = request()->routeIs('admin.settings.data_transfer.imports.edit');
    $damImport = $damIsEdit
        ? app(\Webkul\DataTransfer\Repositories\JobInstancesRepository::class)->find(request()->route('id'))
        : null;
    $damEditIsAssets = $damImport && $damImport->entity_type === \Webkul\DAM\Helpers\Importers\Asset\Importer::ENTITY_TYPE;
    $damEditFilters = $damImport->filters ?? [];

    $damPermissions = app(\Webkul\DAM\Services\DirectoryPermissionService::class);
    $damBypass = $damPermissions->bypass();

    $damDirectoryQuery = \Webkul\DAM\Models\Directory::orderBy('_lft');

    if (! $damBypass) {
        $damDirectoryQuery->whereIn('id', $damPermissions->viewableIds());
    }

    $damDirectories = $damDirectoryQuery->get(['id', 'name', 'parent_id']);
    $damDirectoriesById = $damDirectories->keyBy('id');

    $damDirectoryPath = function ($directory) use ($damDirectoriesById) {
        $segments = [];
        $current = $directory;
        $guard = 0;

        while ($current && $guard++ < 100) {
            array_unshift($segments, $current->name);
            $current = $current->parent_id ? $damDirectoriesById->get($current->parent_id) : null;
        }

        return implode('/', $segments);
    };

    $damGrantedLookup = $damBypass ? null : array_flip($damPermissions->directlyGrantedIds());

    $damSelectableDirectories = $damDirectories->filter(
        fn ($directory) => $damBypass || isset($damGrantedLookup[$directory->id])
    );

    $damDirectoryOptions = json_encode(
        $damSelectableDirectories->map(fn ($directory) => [
            'id'    => (string) $directory->id,
            'label' => $damDirectoryPath($directory),
        ])->values()
    );

    $damDefaultDirectoryId = (string) (
        optional($damSelectableDirectories->firstWhere('parent_id', null))->id
        ?? optional($damSelectableDirectories->first())->id
        ?? ''
    );
@endphp

@if ($damIsEdit)
    @if ($damEditIsAssets)
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            @include('dam::data-transfer.imports.asset-options-fields')
        </div>
    @endif
@else
    <div
        v-if="(parseValue(entityType)?.id ?? entityType) === 'assets'"
        class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
    >
        @include('dam::data-transfer.imports.asset-options-fields')
    </div>
@endif
