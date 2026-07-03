@once('v-dam-explorer-view-toggle')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-view-toggle-template">
    <div class="flex border border-gray-300 dark:border-cherry-600 rounded-lg overflow-hidden bg-white dark:bg-cherry-900 shrink-0 ml-auto">
        <button
            type="button"
            class="flex items-center px-2.5 py-2 transition-colors"
            :class="modelValue === 'grid' ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-white' : 'text-gray-500 dark:text-white hover:bg-gray-50 dark:hover:bg-cherry-800'"
            data-view="grid"
            title="@lang('dam::app.admin.explorer.view.grid')"
            @click="$emit('update:modelValue', 'grid')"
        >
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1"/><rect x="9" y="1" width="6" height="6" rx="1"/><rect x="1" y="9" width="6" height="6" rx="1"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>
        </button>
        <button
            type="button"
            class="flex items-center px-2.5 py-2 border-l border-gray-200 dark:border-cherry-700 transition-colors"
            :class="modelValue === 'list' ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-white' : 'text-gray-500 dark:text-white hover:bg-gray-50 dark:hover:bg-cherry-800'"
            data-view="list"
            title="@lang('dam::app.admin.explorer.view.list')"
            @click="$emit('update:modelValue', 'list')"
        >
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="2" width="14" height="2.5" rx="1"/><rect x="1" y="6.75" width="14" height="2.5" rx="1"/><rect x="1" y="11.5" width="14" height="2.5" rx="1"/></svg>
        </button>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer-view-toggle', {
    template: '#v-dam-explorer-view-toggle-template',
    emits: ['update:modelValue'],

    props: {
        modelValue: { type: String, default: 'grid' },
    },
});
</script>
@endpush
@endonce
