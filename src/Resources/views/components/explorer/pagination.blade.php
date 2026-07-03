@once('v-dam-explorer-pager')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-pager-template">
    <div class="flex items-center gap-x-2">
        <div class="relative z-20">
        <x-admin::dropdown>
            <x-slot:toggle>
                <button
                    type="button"
                    class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-2.5 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400"
                >
                    <span v-text="perPage"></span>
                    <span class="icon-chevron-down text-2xl"></span>
                </button>
            </x-slot>
            <x-slot:menu>
                <x-admin::dropdown.menu.item v-for="opt in [50, 100, 150, 200, 250]" v-text="opt" @click="$emit('per-page-change', opt)"></x-admin::dropdown.menu.item>
            </x-slot>
        </x-admin::dropdown>
        </div>

        <p class="whitespace-nowrap text-gray-600 dark:text-gray-300">
            @lang('admin::app.components.datagrid.toolbar.per-page')
        </p>

        <input
            type="text"
            class="inline-flex min-h-[38px] max-w-[60px] appearance-none items-center justify-center gap-x-1 rounded-md border dark:border-cherry-800 bg-white dark:bg-cherry-900 px-3 py-1.5 text-center leading-6 text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
            :value="currentPage"
            @change="$emit('page-change', Math.max(1, Math.min(lastPage, parseInt($event.target.value) || 1)))"
        >

        <div class="flex items-center gap-1 whitespace-nowrap text-gray-600 dark:text-gray-300">
            <span>@lang('admin::app.components.datagrid.toolbar.of')</span>
            <span v-text="lastPage"></span>
        </div>

        <div class="flex items-center gap-1">
            <button
                type="button"
                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                :class="{ 'opacity-40 pointer-events-none': currentPage <= 1 }"
                @click="currentPage > 1 && $emit('page-change', 1)"
                title="@lang('admin::app.components.datagrid.toolbar.pagination.first-page')"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.first-page')"
            >
                <span class="text-2xl" aria-hidden="true">&#171;</span>
            </button>
            <button
                type="button"
                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                :class="{ 'opacity-40 pointer-events-none': currentPage <= 1 }"
                @click="currentPage > 1 && $emit('page-change', currentPage - 1)"
                title="@lang('admin::app.components.datagrid.toolbar.pagination.previous-page')"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.previous-page')"
            >
                <span class="text-2xl" aria-hidden="true">&#8249;</span>
            </button>
            <button
                type="button"
                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                :class="{ 'opacity-40 pointer-events-none': currentPage >= lastPage }"
                @click="currentPage < lastPage && $emit('page-change', currentPage + 1)"
                title="@lang('admin::app.components.datagrid.toolbar.pagination.next-page')"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.next-page')"
            >
                <span class="text-2xl" aria-hidden="true">&#8250;</span>
            </button>
            <button
                type="button"
                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent text-center text-gray-600 dark:text-gray-300 transition-all marker:shadow hover:bg-violet-100 dark:hover:bg-gray-800 active:border-gray-300"
                :class="{ 'opacity-40 pointer-events-none': currentPage >= lastPage }"
                @click="currentPage < lastPage && $emit('page-change', lastPage)"
                title="@lang('admin::app.components.datagrid.toolbar.pagination.last-page')"
                aria-label="@lang('admin::app.components.datagrid.toolbar.pagination.last-page')"
            >
                <span class="text-2xl" aria-hidden="true">&#187;</span>
            </button>
        </div>
    </div>
</script>
<script type="module">
app.component('v-dam-explorer-pager', {
    template: '#v-dam-explorer-pager-template',
    emits: ['page-change','per-page-change'],
    props: { currentPage: Number, lastPage: Number, perPage: Number },
});
</script>
@endpush
@endonce
