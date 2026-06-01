<v-dam-explorer-pager
    :current-page="currentPage" :last-page="lastPage" :per-page="perPage"
    @page-change="$emit('page-change', $event)"
    @per-page-change="$emit('per-page-change', $event)"
></v-dam-explorer-pager>

@once('v-dam-explorer-pager')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-pager-template">
    <div class="flex items-center justify-between mt-4 text-sm text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-2">
            <span>@lang('dam::app.admin.explorer.pagination.per-page')</span>
            <select
                class="border border-gray-300 dark:border-cherry-700 rounded px-2 py-1 text-sm bg-white dark:bg-cherry-900"
                :value="perPage" @change="$emit('per-page-change', Number($event.target.value))"
            >
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button
                class="px-2 py-1 rounded border border-gray-300 dark:border-cherry-700 hover:bg-gray-100 dark:hover:bg-cherry-800 disabled:opacity-40"
                :disabled="currentPage <= 1" @click="$emit('page-change', currentPage - 1)"
            >←</button>
            <span>Page @{{ currentPage }} of @{{ lastPage }}</span>
            <button
                class="px-2 py-1 rounded border border-gray-300 dark:border-cherry-700 hover:bg-gray-100 dark:hover:bg-cherry-800 disabled:opacity-40"
                :disabled="currentPage >= lastPage" @click="$emit('page-change', currentPage + 1)"
            >→</button>
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
