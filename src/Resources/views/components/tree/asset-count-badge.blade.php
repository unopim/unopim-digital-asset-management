{{-- Registers the v-asset-count-badge Vue component once per page.
     Usage in Vue templates: <v-asset-count-badge :count="item.assets_total_count ?? 0" /> --}}
@pushOnce('scripts')
    <script type="text/x-template" id="v-asset-count-badge-template">
        <span
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
                    default: 0,
                },
            },
        });
    </script>
@endPushOnce
