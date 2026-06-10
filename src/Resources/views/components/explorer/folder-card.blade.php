@once('v-dam-explorer-folder-card')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-folder-card-template">
    <div
        class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl text-center cursor-pointer transition-colors select-none min-w-0"
        :class="isDropTarget
            ? 'bg-violet-200 dark:bg-violet-900/60 ring-2 ring-violet-400'
            : 'hover:bg-violet-100 dark:hover:bg-violet-800/50'"
        draggable="true"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
        @dragover.prevent="$emit('drag-over', $event)"
        @dragleave="onDragLeave"
        @drop.prevent.stop="$emit('drop', $event)"
        @click="$emit('navigate', dir)"
        @contextmenu.prevent.stop="$emit('ctx', { event: $event, dir })"
    >
        <i class="icon-dam-folder text-6xl text-violet-400 dark:text-violet-500 shrink-0 leading-none"></i>
        <div class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2 break-all w-full leading-tight" :title="dir.name">@{{ dir.name }}</div>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer-folder-card', {
    template: '#v-dam-explorer-folder-card-template',
    emits: ['navigate', 'ctx', 'drag-start', 'drag-end', 'drag-over', 'drag-leave', 'drop'],

    props: {
        dir:          { type: Object, required: true },
        isDropTarget: { type: Boolean, default: false },
    },

    methods: {
        onDragStart(e) {
            const ghost = document.createElement('div');
            ghost.style.cssText = 'position:fixed;top:-200px;left:-200px;width:96px;display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border-radius:12px;background:rgba(237,233,254,0.95);box-shadow:0 2px 8px rgba(0,0,0,0.12);pointer-events:none;';
            const icon = document.createElement('i');
            icon.className = 'icon-dam-folder';
            icon.style.cssText = 'font-size:60px;color:#a78bfa;line-height:1;';
            const label = document.createElement('span');
            label.style.cssText = 'font-size:11px;color:#374151;word-break:break-all;text-align:center;line-height:1.2;max-width:80px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;';
            label.textContent = this.dir.name;
            ghost.appendChild(icon);
            ghost.appendChild(label);
            document.body.appendChild(ghost);
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
