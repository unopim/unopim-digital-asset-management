{{--
    Injected via `unopim.admin.settings.data_transfer.imports.create.card.accordion.settings.after`.
    The import edit blade reuses that same event name, so this one template serves both forms and
    branches on the route:
      - create: rendered inside the `v-import-profile` Vue scope, guarded by its `entityType`/
        `parseValue()` so it shows only when the assets importer is selected.
      - edit: no Vue component, so the import is resolved from the route and the card is guarded
        server-side on `entity_type === 'assets'`, pre-filled from the stored `filters`.
    Both post `filters[conflict_strategy]` and `filters[auto_tag]`, which the import controller
    already persists.
--}}
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
    $damEditIsAssets = $damImport && $damImport->entity_type === 'assets';
    $damEditFilters = $damImport->filters ?? [];
@endphp

@if ($damIsEdit)
    @if ($damEditIsAssets)
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                @lang('dam::app.data-transfer.importers.assets.options.title')
            </p>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('dam::app.data-transfer.importers.assets.options.conflict.label')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="filters[conflict_strategy]"
                    :value="old('filters.conflict_strategy') ?? ($damEditFilters['conflict_strategy'] ?? 'skip')"
                    :label="trans('dam::app.data-transfer.importers.assets.options.conflict.label')"
                    :options="$conflictOptions"
                    track-by="id"
                    label-by="label"
                >
                </x-admin::form.control-group.control>

                <x-admin::form.control-group.error control-name="filters[conflict_strategy]" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('dam::app.data-transfer.importers.assets.options.auto-tag.label')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="switch"
                    name="filters[auto_tag]"
                    value="1"
                    :label="trans('dam::app.data-transfer.importers.assets.options.auto-tag.label')"
                    :checked="(bool) old('filters.auto_tag', $damEditFilters['auto_tag'] ?? false)"
                />

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @lang('dam::app.data-transfer.importers.assets.options.auto-tag.hint')
                </p>
            </x-admin::form.control-group>
        </div>
    @endif
@else
    <div
        v-if="(parseValue(entityType)?.id ?? entityType) === 'assets'"
        class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow"
    >
        <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
            @lang('dam::app.data-transfer.importers.assets.options.title')
        </p>

        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('dam::app.data-transfer.importers.assets.options.conflict.label')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="filters[conflict_strategy]"
                :value="old('filters.conflict_strategy') ?? 'skip'"
                :label="trans('dam::app.data-transfer.importers.assets.options.conflict.label')"
                :options="$conflictOptions"
                track-by="id"
                label-by="label"
            >
            </x-admin::form.control-group.control>

            <x-admin::form.control-group.error control-name="filters[conflict_strategy]" />
        </x-admin::form.control-group>

        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('dam::app.data-transfer.importers.assets.options.auto-tag.label')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="switch"
                name="filters[auto_tag]"
                value="1"
                :label="trans('dam::app.data-transfer.importers.assets.options.auto-tag.label')"
                :checked="(bool) old('filters.auto_tag', false)"
            />

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @lang('dam::app.data-transfer.importers.assets.options.auto-tag.hint')
            </p>
        </x-admin::form.control-group>
    </div>
@endif
