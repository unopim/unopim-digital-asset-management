@once('v-dam-asset-card')
@push('scripts')
<script type="text/x-template" id="v-dam-asset-card-template">
    <div
        class="group rounded-lg border border-gray-300 dark:border-cherry-600 bg-white dark:bg-cherry-900 overflow-hidden transition-colors cursor-pointer"
        :class="{ 'ring-2 ring-violet-500': isSelected }"
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

        <div class="flex gap-1.5 items-center py-1.5 pl-1">
            {{-- Checkbox overlay for mass selection --}}
            <span
                class="z-10 opacity-0 group-hover:!opacity-100 transition-opacity"
                :class="{ '!opacity-100': anySelected || isSelected }"
                @click.stop
            >
                <label :for="`sel-card-asset-${asset.id}`" class="flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        class="peer hidden"
                        :id="`sel-card-asset-${asset.id}`"
                        :checked="isSelected"
                        @change="$emit('toggle-select')"
                    >
                    <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 rounded-md text-2xl"></span>
                </label>
            </span>
            <p class="flex-1 min-w-0 text-xs text-gray-600 dark:text-gray-300 truncate">@{{ asset.file_name }}</p>

            <button
                type="button"
                class="dam-ctx-trigger shrink-0 ltr:mr-1 rtl:ml-1 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 dark:text-gray-300 opacity-0 group-hover:opacity-100 hover:bg-gray-100 dark:hover:bg-cherry-800 hover:text-violet-700 dark:hover:text-violet-400 transition-opacity"
                :class="{ '!opacity-100': isSelected }"
                :title="'@lang('dam::app.admin.explorer.list.header.actions')'"
                @click.stop="$emit('ctx', { event: $event, asset })"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <circle cx="12" cy="5" r="2"></circle>
                    <circle cx="12" cy="12" r="2"></circle>
                    <circle cx="12" cy="19" r="2"></circle>
                </svg>
            </button>
        </div>
    </div>
</script>

<script type="module">
app.component('v-dam-asset-card', {
    template: '#v-dam-asset-card-template',
    emits: ['preview', 'edit', 'delete', 'ctx', 'toggle-select'],

    props: {
        asset: { type: Object, required: true },
        tabId: { type: String, required: true },
        isSelected: { type: Boolean, default: false },
        anySelected: { type: Boolean, default: false },
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
            // Preview just the thumbnail, not the whole card, so the drag image
            // is a clean square with no empty card footer. Anchored under the
            // cursor and kept on-screen — an off-screen ghost is captured
            // clipped by Chrome.
            const size = 96;
            const thumb = this.$el.querySelector('.image-card') ?? this.$el;
            const clone = thumb.cloneNode(true);
            const left = Math.min(Math.max(0, e.clientX - size / 2), window.innerWidth - size);
            const top = Math.min(Math.max(0, e.clientY - size / 2), window.innerHeight - size);
            clone.style.cssText = `position:fixed;top:${top}px;left:${left}px;pointer-events:none;width:${size}px;height:${size}px;overflow:hidden;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.12);`;
            document.body.appendChild(clone);
            e.dataTransfer.setDragImage(clone, size / 2, size / 2);
            setTimeout(() => clone.remove(), 0);
            requestAnimationFrame(() => { this.$el.style.opacity = '0.4'; });
        },
        onDragEnd(e) {
            e.currentTarget.style.opacity = '';
        },
    },
});
</script>
@endpush
@endonce
