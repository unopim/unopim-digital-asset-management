@once('v-dam-explorer-folder-card')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-folder-card-template">
    <div
        class="relative flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl text-center cursor-pointer transition-colors select-none min-w-0"
        :class="isDropTarget
            ? 'bg-violet-200 dark:bg-violet-900/60 ring-2 ring-inset ring-violet-400'
            : 'hover:bg-violet-100 dark:hover:bg-violet-800/50'"
        draggable="true"
        @mouseenter="hovered = true"
        @mouseleave="hovered = false"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
        @dragover.prevent="$emit('drag-over', $event)"
        @dragleave="onDragLeave"
        @drop.prevent.stop="$emit('drop', $event)"
        @click="$emit('navigate', dir)"
        @contextmenu.prevent.stop="$emit('ctx', { event: $event, dir })"
    >
        <button
            type="button"
            class="dam-ctx-trigger absolute top-1 right-1 w-6 h-6 flex items-center justify-center rounded-md text-gray-500 dark:text-gray-300 bg-white/80 dark:bg-cherry-900/80 hover:bg-white dark:hover:bg-cherry-900 hover:text-violet-700 dark:hover:text-violet-400 shadow-sm transition-opacity"
            :class="(hovered || anySelected) ? 'opacity-100' : 'opacity-0'"
            :title="'@lang('dam::app.admin.explorer.list.header.actions')'"
            @click.stop="$emit('ctx', { event: $event, dir })"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <circle cx="12" cy="5" r="2"></circle>
                <circle cx="12" cy="12" r="2"></circle>
                <circle cx="12" cy="19" r="2"></circle>
            </svg>
        </button>

        <div
            v-show="anySelected || hovered"
            class="absolute top-1 left-1"
            @click.stop
        >
            <label :for="`sel-card-dir-${dir.id}`" class="flex items-center cursor-pointer">
                <input
                    type="checkbox"
                    class="peer hidden"
                    :id="`sel-card-dir-${dir.id}`"
                    :checked="isSelected"
                    @change="$emit('toggle-select')"
                >
                <span class="icon-checkbox-normal peer-checked:icon-checkbox-check peer-checked:text-violet-700 rounded-md text-2xl bg-white/80 dark:bg-cherry-900/80"></span>
            </label>
        </div>
        <i class="icon-dam-folder text-6xl text-violet-400 dark:text-violet-500 shrink-0 leading-none"></i>
        <div class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 break-all w-full leading-tight" :title="dir.name">@{{ dir.name }}</div>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer-folder-card', {
    template: '#v-dam-explorer-folder-card-template',
    emits: ['navigate', 'ctx', 'drag-start', 'drag-end', 'drag-over', 'drag-leave', 'drop', 'toggle-select'],

    props: {
        dir:          { type: Object, required: true },
        isDropTarget: { type: Boolean, default: false },
        isSelected:   { type: Boolean, default: false },
        anySelected:  { type: Boolean, default: false },
    },

    data() {
        return {
            hovered: false,
        };
    },

    methods: {
        onDragStart(e) {
            const ghost = document.createElement('div');
            ghost.style.cssText = 'position:fixed;top:0;left:0;width:96px;display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border-radius:12px;background:rgba(237,233,254,0.95);box-shadow:0 2px 8px rgba(0,0,0,0.12);pointer-events:none;';
            const icon = document.createElement('i');
            icon.className = 'icon-dam-folder';
            icon.style.cssText = 'font-size:60px;color:#a78bfa;line-height:1;';
            const label = document.createElement('span');
            label.style.cssText = 'font-size:11px;color:#374151;word-break:break-all;text-align:center;line-height:1.2;max-width:80px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;';
            label.textContent = this.dir.name;
            ghost.appendChild(icon);
            ghost.appendChild(label);
            document.body.appendChild(ghost);
            // Keep the drag image inside the viewport, anchored under the cursor.
            // Rendering it off-screen makes Chrome capture a clipped snapshot.
            ghost.style.left = `${Math.min(Math.max(0, e.clientX - 48), window.innerWidth - ghost.offsetWidth)}px`;
            ghost.style.top = `${Math.min(Math.max(0, e.clientY - 50), window.innerHeight - ghost.offsetHeight)}px`;
            e.dataTransfer.setDragImage(ghost, 48, 50);
            setTimeout(() => ghost.remove(), 0);
            e.currentTarget.style.opacity = '0.4';
            this.$emit('drag-start', e);
        },
        onDragEnd(e) {
            e.currentTarget.style.opacity = '';
            this.$emit('drag-end', e);
        },
        onDragLeave(e) {
            if (! this.$el.contains(e.relatedTarget)) {
                this.$emit('drag-leave', e);
            }
        },
    },
});
</script>
@endpush
@endonce
