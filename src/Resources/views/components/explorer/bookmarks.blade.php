<v-dam-bookmarks></v-dam-bookmarks>

@once('v-dam-bookmarks')
@push('scripts')
<script type="text/x-template" id="v-dam-bookmarks-template">
    <div
        class="relative flex flex-col gap-1 rounded-lg"
        @dragover.prevent="dragOver = true"
        @dragleave="onDragLeave"
        @drop.prevent="onDrop($event)"
    >
        {{-- Bookmarks --}}
        <div
            v-for="bm in bookmarks"
            :key="bm.id"
            class="group flex items-center gap-2 px-2 py-1.5 rounded-md cursor-pointer transition-colors"
            :class="activeId === bm.directory_id ? 'bg-violet-100 dark:bg-cherry-800 text-violet-700 dark:text-violet-400' : 'hover:bg-gray-100 dark:hover:bg-cherry-800 text-zinc-700 dark:text-white'"
            :data-bookmark-id="bm.id"
            @click="navigate(bm)"
        >
            <i class="icon-dam-folder text-lg text-violet-400 shrink-0"></i>
            <span class="text-sm flex-1 truncate">@{{ bm.name }}</span>
            <span
                class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 text-base leading-none px-1 rounded transition-colors shrink-0"
                :data-remove-bookmark="bm.id"
                @click.stop="remove(bm.id)"
                title="Remove bookmark"
            >×</span>
        </div>

        <div class="min-h-[160px]"></div>

        {{-- Drop overlay --}}
        <div
            v-if="dragOver"
            class="absolute inset-0 rounded-lg border-2 border-dashed border-violet-400 bg-violet-50/80 dark:bg-violet-900/40 flex flex-col items-center justify-center gap-2 pointer-events-none z-10"
        >
            <i class="icon-dam-folder text-3xl text-violet-400 dark:text-violet-500"></i>
            <span class="text-xs font-semibold text-violet-600 dark:text-violet-300 text-center px-2">
                @lang('dam::app.admin.explorer.bookmarks.drag-hint')
            </span>
        </div>
    </div>
</script>

<script type="module">
app.component('v-dam-bookmarks', {
    template: '#v-dam-bookmarks-template',

    data() {
        return {
            bookmarks: [],
            activeId: null,
            dragOver: false,
        };
    },

    mounted() {
        this.loadBookmarks();

        this.$emitter.on('dam:explorer-navigate', ({ directoryId }) => {
            this.activeId = directoryId;
        });

        this.$emitter.on('dam:add-bookmark', (dir) => {
            this.add(dir);
        });

        this.$emitter.on('dam:directory-deleted', () => {
            this.reloadBookmarks();
        });
    },

    methods: {
        loadBookmarks() {
            this.$axios.get("{{ route('admin.dam.explorer.bookmarks.index') }}")
                .then(({ data }) => { this.bookmarks = data; })
                .catch(() => { this.bookmarks = []; })
                .finally(() => { this.$emitter.emit('dam:bookmarks-ready'); });
        },

        reloadBookmarks() {
            this.$axios.get("{{ route('admin.dam.explorer.bookmarks.index') }}")
                .then(({ data }) => { this.bookmarks = data; })
                .catch(() => {});
        },

        add(dir) {
            if (this.bookmarks.find(b => b.directory_id === dir.id)) return;
            if (this.bookmarks.length >= 20) {
                this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('dam::app.admin.explorer.bookmarks.max')" });
                return;
            }
            this.$axios.post("{{ route('admin.dam.explorer.bookmarks.store') }}", { directory_id: dir.id, name: dir.name })
                .then(({ data }) => { this.bookmarks.push(data); })
                .catch(() => {});
        },

        remove(id) {
            this.$axios.delete(`{{ route('admin.dam.explorer.bookmarks.destroy', ':id') }}`.replace(':id', id))
                .then(() => { this.bookmarks = this.bookmarks.filter(b => b.id !== id); })
                .catch(() => {});
        },

        navigate(bm) {
            this.$emitter.emit('dam:explorer-navigate', { directoryId: bm.directory_id ?? bm.id, name: bm.name, source: 'bookmark' });
        },

        onDragLeave(event) {
            if (!this.$el.contains(event.relatedTarget)) {
                this.dragOver = false;
            }
        },

        onDrop(event) {
            this.dragOver = false;
            try {
                const data = JSON.parse(event.dataTransfer.getData('application/json'));
                if (data.type === 'dam-folder') this.add({ id: data.id, name: data.name });
            } catch {}
        },
    },
});
</script>
@endpush
@endonce
