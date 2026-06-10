@once('v-dam-asset-card')
@push('scripts')
<script type="text/x-template" id="v-dam-asset-card-template">
    <div
        class="group rounded-lg border border-gray-300 dark:border-cherry-600 bg-white dark:bg-cherry-900 overflow-hidden transition-colors cursor-pointer"
        style="box-shadow:0 1px 3px rgba(0,0,0,.08);"
        draggable="true"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
        @click="$emit('preview', asset.id)"
        @contextmenu.prevent.stop="$emit('ctx', { event: $event, asset })"
    >
        {{-- Thumbnail --}}
        <div class="image-card relative overflow-hidden">
            <img
                :src="asset.path"
                :alt="asset.file_name"
                class="w-full h-full object-cover object-center"
                draggable="false"
                @@error="onImgErr"
            />

            {{-- Extension badge --}}
            <span
                v-if="asset.extension"
                class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase text-white shadow"
                style="z-index:2;"
                :class="{
                    'bg-violet-600': asset.file_type==='video'||asset.file_type==='audio',
                    'bg-red-600':    (asset.extension||'').toLowerCase()==='pdf',
                    'bg-gray-600':   asset.file_type!=='video'&&asset.file_type!=='audio'&&(asset.extension||'').toLowerCase()!=='pdf',
                }"
            >@{{ (asset.extension||'').toUpperCase() }}</span>

            {{-- Play / audio overlay --}}
            <div
                v-if="asset.file_type==='video'||asset.file_type==='audio'"
                class="absolute inset-0 flex items-center justify-center pointer-events-none"
            >
                <span
                    class="flex items-center justify-center w-10 h-10 rounded-full bg-black/55 text-white text-xl shadow-lg"
                    :class="asset.file_type==='video' ? 'icon-play' : 'icon-information'"
                    aria-hidden="true"
                ></span>
            </div>

            {{-- Hover action overlay --}}
            <div class="absolute inset-0 flex items-center justify-center bg-black/80 dark:bg-cherry-800/90 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                <div class="flex gap-1">
                    @if (bouncer()->hasPermission('dam.asset.view'))
                    <button type="button" class="icon-dam-preview text-xl p-1.5 rounded-md text-white hover:bg-violet-600 transition-colors pointer-events-none group-hover:pointer-events-auto" @click.stop="$emit('preview', asset.id)"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.asset.edit'))
                    <button type="button" class="icon-edit text-xl p-1.5 rounded-md text-white hover:bg-violet-600 transition-colors pointer-events-none group-hover:pointer-events-auto" @click.stop="$emit('edit', asset.id)"></button>
                    @endif
                    @if (bouncer()->hasPermission('dam.asset.destroy'))
                    <button type="button" class="icon-delete text-xl p-1.5 rounded-md text-white hover:bg-red-600 transition-colors pointer-events-none group-hover:pointer-events-auto" @click.stop="$emit('delete', asset)"></button>
                    @endif
                </div>
            </div>
        </div>

        <div class="px-2 py-1.5">
            <p class="text-xs text-gray-600 dark:text-gray-300 truncate">@{{ asset.file_name }}</p>
        </div>
    </div>
</script>

<script type="module">
app.component('v-dam-asset-card', {
    template: '#v-dam-asset-card-template',
    emits: ['preview', 'edit', 'delete', 'ctx'],

    props: {
        asset: { type: Object, required: true },
        tabId: { type: String, required: true },
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

    methods: {
        onImgErr(e) {
            e.target.src = this.placeholders[this.asset.file_type] ?? this.fallback;
            e.target.className = 'w-full h-full object-contain p-4';
        },
        onDragStart(e) {
            e.dataTransfer.setData('application/json', JSON.stringify({
                type:  'dam-asset',
                id:    this.asset.id,
                name:  this.asset.file_name,
                tabId: this.tabId,
            }));
            const el = this.$el;
            const clone = el.cloneNode(true);
            const ghostW = 96;
            const scale = ghostW / el.offsetWidth;
            const ghostH = Math.round(el.offsetHeight * scale);
            clone.style.cssText = `position:fixed;top:-200px;left:-200px;pointer-events:none;width:${ghostW}px;height:${ghostH}px;transform-origin:top left;overflow:hidden;border-radius:8px;opacity:1;`;
            document.body.appendChild(clone);
            e.dataTransfer.setDragImage(clone, ghostW / 2, ghostH / 2);
            setTimeout(() => clone.remove(), 0);
            requestAnimationFrame(() => { el.style.opacity = '0.4'; });
        },
        onDragEnd(e) {
            e.currentTarget.style.opacity = '';
        },
    },
});
</script>
@endpush
@endonce
