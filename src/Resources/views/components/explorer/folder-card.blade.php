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
