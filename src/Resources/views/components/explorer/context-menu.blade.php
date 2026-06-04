@once('v-dam-explorer-ctx')
@push('scripts')
<script type="text/x-template" id="v-dam-explorer-ctx-template">
    <div
        class="fixed bg-white dark:bg-cherry-800 border border-gray-200 dark:border-cherry-600 rounded-lg shadow-2xl py-1 min-w-[185px]"
        style="z-index:9999;"
        :style="{ top: `${y}px`, left: `${x}px` }"
    >
        {{-- Directory actions --}}
        <template v-if="itemType === 'directory'">
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="doNavigate">
                <i class="icon-dam-folder text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.explorer.context.open')
            </button>
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="doOpenNewTab">
                <i class="icon-link text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.explorer.context.open-new-tab')
            </button>
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="uploadHere">
                <i class="icon-dam-upload text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.upload-files')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="folderUploadHere">
                <i class="icon-dam-add-folder text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.explorer.context.folder-upload')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.store'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="createHere">
                <i class="icon-dam-add-folder text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.add-directory')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.rename'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="renameDir">
                <i class="icon-dam-rename text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.rename')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.copy_structure'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="copyStructure">
                <i class="icon-dam-directory text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.copy-directory-structured')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.download_zip'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="downloadZip">
                <i class="icon-dam-zip text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.download-zip')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.share'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="share">
                <i class="icon-dam-link text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.share')
            </button>
            @endif
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="doBookmark">
                <span class="text-sm">🔖</span>
                @lang('dam::app.admin.explorer.context.bookmark')
            </button>
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="doCopy">
                <i class="icon-copy text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.explorer.context.copy')
            </button>
            @if (bouncer()->hasPermission('dam.directory.destroy'))
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-red-600 transition-colors hover:bg-red-50" @click="delDir">
                <i class="icon-dam-delete text-sm"></i>
                @lang('dam::app.admin.dam.index.directory.actions.delete')
            </button>
            @endif
        </template>

        {{-- Space actions (right-click on empty area in current directory) --}}
        <template v-else-if="itemType === 'space'">
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="uploadHere">
                <i class="icon-dam-upload text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.upload-files')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.upload'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="folderUploadHere">
                <i class="icon-dam-add-folder text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.explorer.context.folder-upload')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.directory.store'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="createHere">
                <i class="icon-dam-add-folder text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.add-directory')
            </button>
            @endif
            <template v-if="clipboard">
                <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
                <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="doPaste">
                    <i class="icon-paste text-sm text-zinc-600 dark:text-white"></i>
                    @lang('dam::app.admin.explorer.context.paste')
                </button>
            </template>
        </template>

        {{-- Asset actions --}}
        <template v-else-if="itemType === 'asset'">
            @if (bouncer()->hasPermission('dam.asset.view'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="preview">
                <i class="icon-dam-preview text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.asset.edit.preview-modal.card.preview')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.edit'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="edit">
                <i class="icon-edit text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.edit')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.rename'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="renameAsset">
                <i class="icon-dam-rename text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.rename')
            </button>
            @endif
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="doCopy">
                <i class="icon-copy text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.explorer.context.copy')
            </button>
            @if (bouncer()->hasPermission('dam.asset.download'))
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-cherry-700" @click="download">
                <i class="icon-import text-sm text-zinc-600 dark:text-white"></i>
                @lang('dam::app.admin.dam.index.directory.actions.download')
            </button>
            @endif
            @if (bouncer()->hasPermission('dam.asset.destroy'))
            <div class="border-t border-gray-100 dark:border-cherry-700 my-1"></div>
            <button class="flex items-center gap-2 w-full text-left px-4 py-1.5 text-sm text-red-600 transition-colors hover:bg-red-50" @click="delAsset">
                <i class="icon-dam-delete text-sm"></i>
                @lang('dam::app.admin.dam.index.directory.actions.delete')
            </button>
            @endif
        </template>
    </div>
</script>

<script type="module">
app.component('v-dam-explorer-ctx', {
    template: '#v-dam-explorer-ctx-template',
    emits: ['close','navigate','open-new-tab','bookmark','refresh'],
    props: { x: Number, y: Number, item: Object, itemType: String, tabId: { type: String, required: true }, clipboard: { type: Object, default: null } },

    mounted() {
        this.$nextTick(() => {
            const el = this.$el;
            if (! el) return;
            const r = el.getBoundingClientRect();
            if (r.bottom > window.innerHeight) el.style.top  = Math.max(4, window.innerHeight - r.height - 8) + 'px';
            if (r.right  > window.innerWidth)  el.style.left = Math.max(4, window.innerWidth  - r.width  - 8) + 'px';
        });
    },

    methods: {
        close()         { this.$emit('close'); },
        doNavigate()    { this.$emit('navigate', this.item); this.close(); },
        doOpenNewTab()  { this.$emit('open-new-tab', this.item); this.close(); },
        doBookmark()    { this.$emit('bookmark', this.item); this.close(); },

        preview()     { this.$emitter.emit('dam-open-preview', this.item.id); this.close(); },
        edit()        { window.location.href = `{{ route('admin.dam.assets.edit', ':id') }}`.replace(':id', this.item.id); },
        renameAsset() { this.$emitter.emit(`dam:explorer-rename-asset:${this.tabId}`, { asset: this.item }); this.close(); },
        download()    { window.open(`{{ route('admin.dam.assets.download', ':id') }}`.replace(':id', this.item.id), '_self'); this.close(); },
        ctxRefresh(delayed = false) {
            this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`);
            if (delayed) setTimeout(() => this.$emitter.emit(`dam:explorer-ctx-refresh:${this.tabId}`), 800);
        },
        delAsset() {
            this.close();
            this.$emitter.emit('open-delete-modal', {
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.assets.destroy', ':id') }}`.replace(':id', this.item.id))
                        .then(({ data }) => { this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.dam.index.directory.actions.delete')" }); this.ctxRefresh(); })
                        .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
                },
            });
        },
        renameDir()   { this.$emitter.emit(`dam:explorer-rename-dir:${this.tabId}`, { dir: this.item }); this.close(); },
        copyStructure() {
            const tabId = this.tabId;
            this.close();
            this.$axios.post("{{ route('admin.dam.directory.copy_structure') }}", this.item)
                .then(({ data }) => {
                    this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.explorer.context.copy-progress')" });
                    let attempts = 0;
                    const poll = () => {
                        if (++attempts > 15) return;
                        this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'copy_directory_structure'))
                            .then(({ data: d }) => {
                                if (d.status === 'completed') {
                                    this.$emitter.emit('add-flash', { type: 'success', message: 'Action completed successfully' });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                } else if (d.status === 'failed') {
                                    this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                    this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                } else { setTimeout(poll, 2000); }
                            }).catch(() => {});
                    };
                    setTimeout(poll, 1000);
                })
                .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
        },
        downloadZip() { this.close(); window.open(`{{ route('admin.dam.directory.zip_download', ':id') }}`.replace(':id', this.item.id), '_self'); },
        share()       { this.close(); this.$emitter.emit('open-share-modal', { targetType:'directory', targetId: this.item.id }); },
        uploadHere()       { this.close(); this.$emitter.emit(`dam:explorer-upload-here:${this.tabId}`, { directoryId: this.item.id }); },
        folderUploadHere() { this.close(); this.$emitter.emit(`dam:explorer-folder-upload-here:${this.tabId}`, { directoryId: this.item.id }); },
        createHere()       { this.close(); this.$emitter.emit(`dam:explorer-create-dir:${this.tabId}`, { parentId: this.item.id }); },
        doCopy() {
            this.$emitter.emit(`dam:explorer-copy:${this.tabId}`, { item: this.item, type: this.itemType });
            this.close();
        },
        doPaste() {
            this.$emitter.emit(`dam:explorer-paste:${this.tabId}`, { targetDirId: this.item.id });
            this.close();
        },
        delDir() {
            const tabId = this.tabId;
            this.close();
            this.$emitter.emit('open-delete-modal', {
                message: "@lang('dam::app.admin.components.modal.confirm.message')",
                agree: () => {
                    this.$axios.delete(`{{ route('admin.dam.directory.destroy', ':id') }}`.replace(':id', this.item.id))
                        .then(({ data }) => {
                            this.$emitter.emit('add-flash', { type: 'success', message: data.message ?? "@lang('dam::app.admin.dam.index.directory.deleting-in-progress')" });
                            let attempts = 0;
                            const poll = () => {
                                if (++attempts > 15) return;
                                this.$axios.get(`{{ route('admin.dam.action_request.status', ':et') }}`.replace(':et', 'delete_directory'))
                                    .then(({ data: d }) => {
                                        if (d.status === 'completed') {
                                            this.$emitter.emit('add-flash', { type: 'success', message: 'Action completed successfully' });
                                            this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                        } else if (d.status === 'failed') {
                                            this.$emitter.emit('add-flash', { type: 'error', message: d.message });
                                            this.$emitter.emit(`dam:explorer-ctx-refresh:${tabId}`);
                                        } else { setTimeout(poll, 2000); }
                                    }).catch(() => {});
                            };
                            setTimeout(poll, 1000);
                        })
                        .catch(e => this.$emitter.emit('add-flash', { type: 'error', message: e?.response?.data?.message ?? "@lang('dam::app.admin.dam.index.directory.something-wrong')" }));
                },
            });
        },
    },
});
</script>
@endpush
@endonce
