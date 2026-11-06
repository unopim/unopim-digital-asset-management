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
        @lang('dam::app.data-transfer.importers.assets.options.import-directory.label')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        name="filters[import_directory]"
        :value="old('filters.import_directory') ?? ($damEditFilters['import_directory'] ?? $damDefaultDirectoryId)"
        :label="trans('dam::app.data-transfer.importers.assets.options.import-directory.label')"
        :options="$damDirectoryOptions"
        track-by="id"
        label-by="label"
    >
    </x-admin::form.control-group.control>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        @lang('dam::app.data-transfer.importers.assets.options.import-directory.hint')
    </p>
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
