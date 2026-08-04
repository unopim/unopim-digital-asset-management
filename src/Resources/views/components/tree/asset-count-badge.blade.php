
@pushOnce('scripts')
    <script type="text/x-template" id="v-asset-count-badge-template">
        <span
            v-if="count !== null && count !== undefined"
            class="text-xs text-gray-500 dark:text-slate-400 ms-1 select-none"
            data-asset-total-count
        >(@{{ count }})</span>
    </script>

    <script type="module">
        app.component('v-asset-count-badge', {
            template: '#v-asset-count-badge-template',
            props: {
                count: {
                    type: Number,
                    default: null,
                },
            },
        });
    </script>
@endPushOnce
