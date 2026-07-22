<div class="flex flex-col items-center justify-center gap-4 py-16 text-center">
    <img
        src="{{ unopim_asset('images/no-records-found.svg', 'dam') }}"
        alt=""
        class="w-32 h-32 opacity-60"
    />
    <p class="text-xl font-bold text-zinc-800 dark:text-slate-50">
        @lang('admin::app.components.datagrid.table.no-records-available')
    </p>
</div>
