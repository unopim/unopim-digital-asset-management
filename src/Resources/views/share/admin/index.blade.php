<x-admin::layouts>
    <x-slot:title>
        @lang('dam::app.admin.dam.share.index.title')
    </x-slot:title>

    <div class="flex justify-between items-center">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('dam::app.admin.dam.share.index.title')
        </p>
    </div>

    <p class="text-sm text-zinc-600 dark:text-slate-300 mt-2">
        @lang('dam::app.admin.dam.share.index.description')
    </p>

    {!! view_render_event('unopim.dam.shares.list.before') !!}

    <x-admin::datagrid src="{{ route('admin.dam.shares.index') }}" />

    @include('dam::share.components.share-edit-modal')

    {!! view_render_event('unopim.dam.shares.list.after') !!}
</x-admin::layouts>
