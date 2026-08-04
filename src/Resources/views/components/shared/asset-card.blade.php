@once('v-dam-asset-card')
@push('scripts')
<script type="text/x-template" id="v-dam-asset-card-template">
    <div
        class="image-card relative overflow-hidden rounded-lg border border-gray-200 dark:border-cherry-700 bg-white dark:bg-cherry-900 transition-colors group{{ bouncer()->hasPermission('dam.asset.view') ? ' cursor-pointer' : '' }}"
        :draggable="draggable"
        @if (bouncer()->hasPermission('dam.asset.view'))
        @click="$emit('preview')"
        @endif
        @dragstart="draggable && $emit('dragstart', $event)"
        @dragend="draggable && $emit('dragend', $event)"
        @contextmenu.prevent.stop="$emit('contextmenu', $event)"
    >
        <img
            :src="assetSrc"
            :alt="asset.file_name"
            class="w-full h-full"
            :class="asset.file_type === 'image' ? 'object-cover object-center' : 'object-contain p-4 sm:p-6'"
            v-on:error="onImgErr($event)"
        />

        <span
            v-if="asset.extension"
            class="absolute top-1.5 right-1.5 z-10 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white shadow-md"
            :class="badgeClass"
            v-text="(asset.extension || '').toUpperCase()"
        ></span>

        <div
            v-if="asset.file_type === 'video' || asset.file_type === 'audio'"
            class="absolute inset-0 flex items-center justify-center pointer-events-none"
        >
            <span
                class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-black/55 text-white text-xl sm:text-2xl shadow-lg"
                :class="asset.file_type === 'video' ? 'icon-play' : 'icon-information'"
                aria-hidden="true"
            ></span>
        </div>

        <div class="absolute inset-0 flex items-center justify-center bg-black/80 dark:bg-cherry-800/90 transition-opacity max-sm:opacity-100 opacity-0 group-hover:opacity-100">
            <div class="flex gap-1">
                @if (bouncer()->hasPermission('dam.asset.view'))
                <button
                    type="button"
                    class="icon-dam-preview text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-primary-600 transition-colors"
                    title="@lang('dam::app.admin.dam.asset.edit.preview-modal.card.preview')"
                    @click.stop="$emit('preview')"
                ></button>
                @endif

                @if (bouncer()->hasPermission('dam.asset.edit'))
                <button
                    type="button"
                    class="icon-edit text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-primary-600 transition-colors"
                    title="@lang('dam::app.admin.dam.index.directory.actions.edit')"
                    @click.stop="$emit('edit')"
                ></button>
                @endif

                @if (bouncer()->hasPermission('dam.asset.destroy'))
                <button
                    type="button"
                    class="icon-delete text-xl sm:text-2xl p-1.5 rounded-md cursor-pointer text-white hover:bg-red-600 transition-colors"
                    title="@lang('dam::app.admin.dam.index.directory.actions.delete')"
                    @click.stop="$emit('delete')"
                ></button>
                @endif
            </div>
        </div>
    </div>
</script>

<script type="module">
app.component('v-dam-asset-card', {
    template: '#v-dam-asset-card-template',
    emits: ['preview', 'edit', 'delete', 'dragstart', 'dragend', 'contextmenu'],

    props: {
        asset:     { type: Object, required: true },
        draggable: { type: Boolean, default: false },
    },

    data() {
        return {
            placeholders: {
                video:       '{{ unopim_asset('images/grid/video.svg', 'dam') }}',
                audio:       '{{ unopim_asset('images/grid/audio.svg', 'dam') }}',
                pdf:         '{{ unopim_asset('images/grid/file.svg', 'dam') }}',
                spreadsheet: '{{ unopim_asset('images/grid/sheet.svg', 'dam') }}',
                csv:         '{{ unopim_asset('images/grid/csv.svg', 'dam') }}',
                document:    '{{ unopim_asset('images/grid/file.svg', 'dam') }}',
                image:       '{{ unopim_asset('images/grid/image.svg', 'dam') }}',
            },
            fallback: '{{ unopim_asset('images/grid/unspecified.svg', 'dam') }}',
        };
    },

    computed: {
        assetSrc() {
            return this.asset.path || this.placeholders[this.asset.file_type] || this.fallback;
        },

        badgeClass() {
            const ext = (this.asset.extension || '').toLowerCase();
            if (ext === 'pdf') return 'bg-red-600';
            if (this.asset.file_type === 'video' || this.asset.file_type === 'audio') return 'bg-primary-600';
            return 'bg-gray-600';
        },
    },

    methods: {
        onImgErr(event) {
            event.target.src = this.placeholders[this.asset.file_type] ?? this.fallback;
            event.target.classList.remove('object-cover', 'object-center');
            event.target.classList.add('object-contain', 'p-4');
        },
    },
});
</script>
@endpush
@endonce
