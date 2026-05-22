<x-admin::layouts>
    @push('styles')
    @unoPimVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'], 'admin')
    @endpush

    <x-slot:title>
        @lang('dam::app.admin.dam.index.title')
    </x-slot:title>

    {!! view_render_event('unopim.dam.admin.main.before') !!}

    <v-dam-main></v-dam-main>

    {!! view_render_event('unopim.dam.admin.main.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-dam-main-template"
        >
            <div>
                {!! view_render_event('dam.admin.main.form.before') !!}
                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <!-- left sub component -->
                        <div class="flex flex-col max-w-[360px] gap-5 h-full max-sm:w-full p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                            
                                {!! view_render_event('dam.admin.main.form.directory.before') !!}
                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-between items-center gap-2">
                                        <p class="text-xl text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                                            @lang('dam::app.admin.dam.index.title')
                                        </p>
                                    </div>
                                    <p class="text-sm text-zinc-600 !leading-normal dark:text-slate-300">
                                        @lang('dam::app.admin.dam.index.description')
                                    </p>
                                </div>

                                <div class="dark:bg-cherry-700 border-b dark:border-cherry-800"></div>
                                @if (bouncer()->hasPermission('dam.directory.index'))
                                    <div class="flex flex-col gap-5">
                                        <p class="text-base	text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                                            @lang('dam::app.admin.dam.index.directory.title')
                                        </p>
                                        <x-dam::tree.damdirectories />
                                    </div>
                                @endif
                                {!! view_render_event('dam.admin.main.form.directory.after') !!}
                        </div>

                        <!-- right sub-component -->
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                            {!! view_render_event('dam.admin.main.form.grid.before') !!}
                            <v-dam-upload
                        :acl-bypass="{{ dam_acl_bypass() ? 'true' : 'false' }}"
                        :accessible-ids='@json(dam_accessible_dir_ids())'
                    ></v-dam-upload>
                            {!! view_render_event('dam.admin.main.form.grid.before') !!}
                        </div>
                    </div>
                {!! view_render_event('dam.admin.main.form.after') !!}
            </div>
        </script>

        <script type="module">
            app.component('v-dam-main', {
                template: '#v-dam-main-template',

                data() {
                    return {}
                },

                mounted() {
                    // Open the tree at the requested directory if landed here
                    // from a breadcrumb link on the asset edit page. Fired
                    // immediately — the tree component queues the request if
                    // its directories haven't finished loading yet, and the
                    // silent flag suppresses a flash if the directory turns
                    // out to be missing (e.g. it was deleted while we were
                    // away on the edit page).
                    const params = new URLSearchParams(window.location.search);
                    const dirId = params.get('directory_id');
                    if (dirId) {
                        this.$emitter.emit('dam:reveal-directory', { id: Number(dirId), silent: true });
                    }
                },

                methods: {

                }
            })
        </script>
    @endPushOnce

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-dam-upload-template"
        >
            <div
                class="relative"
                @dragenter.prevent="onDragEnter"
                @dragover.prevent
                @dragleave="onDragLeave"
                @drop.prevent="onDrop"
            >
                <!-- Drop overlay -->
                <div
                    v-if="isDragOver"
                    class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/90 dark:bg-cherry-800/95 backdrop-blur-sm border-2 border-dashed border-violet-500 dark:border-violet-400 rounded-lg pointer-events-none"
                >
                    <div class="flex flex-col items-center gap-3 rounded-2xl bg-violet-50 dark:bg-violet-950/80 border border-violet-200 dark:border-violet-700 px-10 py-8 shadow-lg">
                        <i class="icon-dam-upload text-6xl text-violet-500 dark:text-violet-400 block"></i>
                        <p class="text-violet-700 dark:text-violet-300 font-semibold text-base text-center">
                            @lang('dam::app.admin.dam.index.drop-zone-hint')
                        </p>
                    </div>
                </div>

                <div class="flex justify-between items-center w-full">
                    <v-dam-breadcrumb></v-dam-breadcrumb>
                    @if (bouncer()->hasPermission('dam.asset.upload') && bouncer()->hasPermission('dam.directory.index'))
                        <div class="flex items-center gap-2" v-if="canUploadHere">
                            <input type="file"
                                multiple="multiple"
                                name="files[]"
                                id="file-upload"
                                class="hidden"
                                :disabled="isUploading || treeBusy"
                                @change="onFileChange"
                            />
                            <label
                                for="file-upload"
                                class="secondary-button cursor-pointer"
                                :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isUploading || isFolderUploading || treeBusy }"
                                :aria-disabled="isUploading || isFolderUploading || treeBusy"
                            >
                                <svg
                                    v-if="isUploading || isFolderUploading"
                                    class="align-center inline-block animate-spin h-5 w-5 text-violet-700"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="#8A2BE2"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span v-else class="icon-dam-upload" style="color: inherit;"></span>
                                <span v-if="isUploading || isFolderUploading">@lang('dam::app.admin.dam.index.uploading')</span>
                                <span v-else>@lang('dam::app.admin.dam.index.upload')</span>
                            </label>

                            <button
                                v-if="isUploading || isFolderUploading"
                                type="button"
                                class="secondary-button"
                                @click="isUploading ? cancelUpload() : cancelFolderUpload()"
                            >
                                @lang('dam::app.admin.dam.index.cancel')
                            </button>
                        </div>
                    @endif

                </div>

                {!! view_render_event('unopim.admin.dam.assets.list.before') !!}

                @if (bouncer()->hasPermission('dam.asset.view'))
                    <div
                        :class="{ 'pointer-events-none opacity-60': isUploading || treeBusy }"
                        :aria-busy="isUploading || treeBusy"
                    >
                        <x-dam::datagrid.dam
                            :src="route('admin.dam.assets.index')"
                            ref="datagrid"
                        />
                    </div>
                @endif

                {!! view_render_event('unopim.admin.dam.assets.list.after') !!}

                <!-- Drop-upload progress panel -->
                <div
                    v-if="dropUploads.length"
                    class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 w-full max-w-xl bg-white dark:bg-cherry-800 rounded-xl shadow-2xl border-2 border-gray-300 dark:border-violet-700 overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-cherry-700 cursor-pointer select-none"
                        @click="dropPanelMinimized = !dropPanelMinimized"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                v-if="activeDropUploadCount > 0"
                                class="animate-spin h-4 w-4 text-violet-600 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg
                                v-else-if="dropUploads.every(u => u.status === 'done')"
                                class="h-4 w-4 text-green-500 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <svg
                                v-else
                                class="h-4 w-4 text-red-500 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white truncate">@{{ dropPanelTitle }}</span>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                            <svg
                                :class="dropPanelMinimized ? 'rotate-180' : ''"
                                class="h-4 w-4 text-gray-400 dark:text-gray-300 transition-transform"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <button
                                type="button"
                                class="p-1 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-white rounded"
                                @click.stop="clearDropUploads"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="!dropPanelMinimized" class="max-h-60 overflow-y-auto divide-y divide-gray-100 dark:divide-cherry-700">
                        <div v-for="job in dropUploads" :key="job.id" class="flex items-start gap-3 px-4 py-3">
                            <div class="flex-shrink-0 mt-0.5 w-5 h-5 flex items-center justify-center">
                                <svg
                                    v-if="job.status === 'uploading'"
                                    class="animate-spin h-4 w-4 text-violet-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg
                                    v-else-if="job.status === 'done'"
                                    class="h-4 w-4 text-green-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg
                                    v-else
                                    class="h-4 w-4 text-red-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700 dark:text-gray-200 truncate font-medium">@{{ job.name }}</p>
                                <div v-if="job.status === 'uploading'" class="mt-2 h-2 bg-gray-200 dark:bg-cherry-600 rounded-full overflow-hidden border border-gray-300 dark:border-cherry-500">
                                    <div
                                        class="h-full bg-violet-500 transition-all duration-300 rounded-full"
                                        :style="{ width: job.progress + '%' }"
                                    ></div>
                                </div>
                                <p v-else-if="job.status === 'error'" class="text-xs text-red-400 mt-0.5 truncate">@{{ job.error }}</p>
                                <p v-else class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">@lang('dam::app.admin.dam.index.upload-complete')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </script>
    <script type="module">
        const damUploadFileTooLargeMsg = @js(trans('dam::app.admin.dam.asset.datagrid.file-too-large', ['size' => \Webkul\DAM\Helpers\AssetHelper::humanReadableSize(\Webkul\DAM\Helpers\AssetHelper::getMaxUploadSizeKb())]));
        const damUploadFailedMsg = @js(trans('dam::app.admin.dam.asset.datagrid.files-upload-failed'));
        const damUploadCompleteMsg = @js(trans('dam::app.admin.dam.index.upload-complete'));
        const damMaxFileUploads = @js((int) ini_get('max_file_uploads'));

        app.component('v-dam-upload', {
            template: '#v-dam-upload-template',

            props: {
                aclBypass: {
                    type: Boolean,
                    default: false,
                },
                accessibleIds: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    currentDirectory: null,
                    isUploading: false,
                    isFolderUploading: false,
                    abortController: null,
                    treeBusy: false,
                    isDragOver: false,
                    dragCounter: 0,
                    dropUploads: [],
                    dropPanelMinimized: false,
                    nextDropJobId: 1,
                    _autoHideTimer: null,
                }
            },

            computed: {
                // Upload button shows only when the currently-selected directory
                // is directly granted to the admin's role. Bypass roles (all /
                // anonymous / API) keep the original behaviour.
                canUploadHere() {
                    if (this.aclBypass) return true;
                    if (! this.currentDirectory) return false;

                    return this.accessibleIds.map(Number).includes(Number(this.currentDirectory.id));
                },

                activeDropUploadCount() {
                    return this.dropUploads.filter(u => u.status === 'uploading').length;
                },

                dropPanelTitle() {
                    const active = this.activeDropUploadCount;
                    if (active > 0) return `${active} item${active > 1 ? 's' : ''} uploading…`;
                    const errors = this.dropUploads.filter(u => u.status === 'error').length;
                    if (errors > 0) return `${errors} upload${errors > 1 ? 's' : ''} failed`;
                    return damUploadCompleteMsg;
                },
            },

            mounted() {
                this.$emitter.on('current-directory', (data) => {
                    this.currentDirectory = data;
                });

                // Tree broadcasts busy when an async dir mutation
                // (delete/move/copy) is in flight — gate the asset grid
                // so user can't act on assets mid-job.
                this.$emitter.on('dam:tree-busy', (busy) => {
                    this.treeBusy = !! busy;
                });

                // Tree's right-click "Upload Files" routes through here so
                // the spinner, cancel button, and error handling stay unified
                // with the toolbar upload.
                this.$emitter.on('dam:upload-files', (formData) => {
                    if (this.isUploading) return;
                    this.handleFileUpload(formData);
                });

                this.$emitter.on('dam:folder-upload-start', () => {
                    this.isFolderUploading = true;
                });

                this.$emitter.on('dam:folder-upload-end', () => {
                    this.isFolderUploading = false;
                });
            },

            watch: {
                // Mirror tree-lock direction: when an upload is running, freeze
                // the directory tree so the user can't move folders out from
                // under the in-flight upload target.
                isUploading(value) {
                    this.$emitter.emit('dam:grid-busy', !! value);
                },
            },

            methods: {
                onFileChange(e) {
                    e.preventDefault();

                    if (this.isUploading) {
                        e.target.value = null;
                        return;
                    }

                    let fileInput = e.target.files;

                    if (fileInput.length > 0) {
                        let formData = new FormData();

                        for (let index = 0; index < fileInput.length; index++) {
                            formData.append('files[]', fileInput[index]);
                        }

                        if (this.currentDirectory) {
                            formData.append('directory_id', this.currentDirectory.id);
                        }

                        this.handleFileUpload(formData);
                    }

                    e.target.value = null;
                },

                cancelUpload() {
                    if (this.abortController) {
                        this.abortController.abort();
                        this.abortController = null;
                    }
                },

                cancelFolderUpload() {
                    this.$emitter.emit('dam:cancel-folder-upload');
                },

                handleFileUpload(formData) {
                    this.isUploading = true;
                    this.abortController = new AbortController();

                    this.$axios.post("{{ route('admin.dam.assets.upload') }}", formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                        signal: this.abortController.signal,
                    }).then((response) => {
                        // Server-level errors (e.g. post_max_size exceeded) return 200 with an
                        // HTML body instead of JSON. Detect by checking the data type.
                        if (typeof response.data !== 'object' || response.data === null) {
                            this.$emitter.emit('add-flash', { type: 'error', message: damUploadFileTooLargeMsg });
                            return;
                        }
                        this.$refs.datagrid.get();
                        this.$emitter.emit('uploaded-assets', response.data.files);
                        this.$emitter.emit('add-flash', {
                            type: 'success',
                            message: response.data.message
                        });
                    }).catch((error) => {
                        if (this.$axios.isCancel(error) || error.code === 'ERR_CANCELED') {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: @js(trans('dam::app.admin.dam.index.upload-cancelled')),
                            });
                            return;
                        }
                        const message = error.response?.status === 413
                            ? damUploadFileTooLargeMsg
                            : (error.response?.data?.message ?? damUploadFailedMsg);
                        this.$emitter.emit('add-flash', { type: 'error', message });
                    }).finally(() => {
                        this.isUploading = false;
                        this.abortController = null;
                    });
                },

                onDragEnter() {
                    this.dragCounter++;
                    this.isDragOver = true;
                },

                onDragLeave() {
                    this.dragCounter--;
                    if (this.dragCounter <= 0) {
                        this.dragCounter = 0;
                        this.isDragOver = false;
                    }
                },

                async onDrop(event) {
                    this.dragCounter = 0;
                    this.isDragOver = false;

                    if (! this.currentDirectory) return;

                    const items = event.dataTransfer?.items;
                    if (! items || items.length === 0) return;

                    const flatFiles = [];
                    const dirEntries = [];

                    for (let i = 0; i < items.length; i++) {
                        const entry = items[i].webkitGetAsEntry ? items[i].webkitGetAsEntry() : null;
                        if (entry && entry.isDirectory) {
                            dirEntries.push(entry);
                        } else {
                            const file = items[i].getAsFile();
                            if (file) flatFiles.push(file);
                        }
                    }

                    if (dirEntries.length === 0) {
                        // Flat files only — use the regular upload endpoint with progress
                        const formData = new FormData();
                        flatFiles.forEach(f => formData.append('files[]', f));
                        formData.append('directory_id', this.currentDirectory.id);
                        const label = flatFiles.length === 1 ? flatFiles[0].name : `${flatFiles.length} files`;
                        this.runDropFileUpload(formData, label);
                        return;
                    }

                    // Flat files alongside folders — upload to root of target dir
                    if (flatFiles.length > 0) {
                        const fileObjects = flatFiles.map(f => ({ file: f, relativePath: f.name }));
                        const label = flatFiles.length === 1 ? flatFiles[0].name : `${flatFiles.length} files`;
                        this.runChunkedFolderUpload(fileObjects, label);
                    }

                    // Each dragged folder: one progress row per immediate subdirectory,
                    // uploaded SEQUENTIALLY to avoid backend race on directory creation.
                    for (const dirEntry of dirEntries) {
                        const allFiles = await this.readFolderEntries([dirEntry]);
                        if (allFiles.length === 0) continue;

                        // Group by immediate child of the dragged folder.
                        // relativePath is like "Pictures/Screenshots/img.jpg" or "Pictures/img.jpg"
                        const groupMap = new Map();
                        for (const entry of allFiles) {
                            const segments = entry.relativePath.split('/');
                            // segments[0] = dragged folder name, segments[1] = subdir or file
                            const groupKey = segments.length > 2 ? segments[1] : '';
                            if (! groupMap.has(groupKey)) groupMap.set(groupKey, []);
                            groupMap.get(groupKey).push(entry);
                        }

                        for (const [groupKey, files] of groupMap) {
                            const label = groupKey
                                ? `${dirEntry.name}/${groupKey}`
                                : dirEntry.name;
                            await this.runChunkedFolderUpload(files, label, { silent: true });
                        }
                    }

                    // Single success flash after all folders/groups are done
                    const anyError = this.dropUploads.some(u => u.status === 'error');
                    if (! anyError) {
                        this.$emitter.emit('add-flash', { type: 'success', message: damUploadCompleteMsg });
                    }
                },

                async readFolderEntries(entries) {
                    const files = [];

                    const walk = async (entry, pathPrefix) => {
                        const path = pathPrefix ? `${pathPrefix}/${entry.name}` : entry.name;

                        if (entry.isFile) {
                            await new Promise(resolve => {
                                entry.file(file => { files.push({ file, relativePath: path }); resolve(); }, resolve);
                            });
                            return;
                        }

                        // Exhaust all batches first (readEntries returns ≤100 per call)
                        const reader = entry.createReader();
                        const allChildren = [];
                        let batch;
                        do {
                            batch = await new Promise(
                                resolve => reader.readEntries(resolve, () => resolve([]))
                            ).catch(() => []);
                            allChildren.push(...batch);
                        } while (batch.length > 0);

                        // Process all children in parallel once the directory is fully read
                        await Promise.all(allChildren.map(child => walk(child, path)));
                    };

                    await Promise.all(entries.map(e => walk(e, '')));
                    return files;
                },

                runDropFileUpload(formData, label) {
                    const jobId = this.nextDropJobId++;
                    this.dropPanelMinimized = false;
                    clearTimeout(this._autoHideTimer);
                    this._autoHideTimer = null;
                    this.dropUploads.push({ id: jobId, name: label, status: 'uploading', progress: 0, error: null });

                    this.$axios.post("{{ route('admin.dam.assets.upload') }}", formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                        onUploadProgress: (e) => {
                            const job = this.dropUploads.find(u => u.id === jobId);
                            if (job && e.total) job.progress = Math.round((e.loaded / e.total) * 100);
                        },
                    })
                        .then(response => {
                            const job = this.dropUploads.find(u => u.id === jobId);
                            if (typeof response.data !== 'object' || response.data === null) {
                                if (job) { job.status = 'error'; job.error = damUploadFileTooLargeMsg; }
                                return;
                            }
                            if (job) { job.status = 'done'; job.progress = 100; }
                            this.maybeAutoHide();
                            this.$refs.datagrid?.get();
                            this.$emitter.emit('uploaded-assets', response.data.files);
                        })
                        .catch(error => {
                            const job = this.dropUploads.find(u => u.id === jobId);
                            if (job) {
                                job.status = 'error';
                                job.error = error.response?.status === 413
                                    ? damUploadFileTooLargeMsg
                                    : (error.response?.data?.message ?? damUploadFailedMsg);
                            }
                        });
                },

                async runChunkedFolderUpload(allFiles, label, { silent = false } = {}) {
                    const chunkSize = Math.max(1, damMaxFileUploads - 2);
                    const chunks = [];
                    for (let i = 0; i < allFiles.length; i += chunkSize) {
                        chunks.push(allFiles.slice(i, i + chunkSize));
                    }

                    const jobId = this.nextDropJobId++;
                    this.dropPanelMinimized = false;
                    clearTimeout(this._autoHideTimer);
                    this._autoHideTimer = null;
                    this.dropUploads.push({ id: jobId, name: label, status: 'uploading', progress: 0, error: null });

                    // Byte-accurate overall progress across all chunks
                    const totalBytes = allFiles.reduce((s, { file }) => s + file.size, 0);
                    let bytesCommitted = 0;

                    let totalUploaded = 0;

                    for (let ci = 0; ci < chunks.length; ci++) {
                        const formData = new FormData();
                        formData.append('directory_id', this.currentDirectory.id);
                        formData.append('preserve_root', '1');
                        chunks[ci].forEach(({ file, relativePath }) => {
                            formData.append('files[]', file);
                            formData.append('relative_paths[]', relativePath);
                        });

                        const chunkBytes = chunks[ci].reduce((s, { file }) => s + file.size, 0);
                        const committedAtStart = bytesCommitted;

                        try {
                            const response = await this.$axios.post(
                                "{{ route('admin.dam.assets.upload_folder') }}",
                                formData,
                                {
                                    headers: { 'Content-Type': 'multipart/form-data' },
                                    onUploadProgress: (e) => {
                                        const job = this.dropUploads.find(u => u.id === jobId);
                                        if (job && totalBytes > 0) {
                                            const inChunk = e.total ? (e.loaded / e.total) * chunkBytes : 0;
                                            job.progress = Math.min(99, Math.round(((committedAtStart + inChunk) / totalBytes) * 100));
                                        }
                                    },
                                }
                            );
                            bytesCommitted += chunkBytes;
                            totalUploaded += (response.data.files || []).length;
                        } catch (error) {
                            const job = this.dropUploads.find(u => u.id === jobId);
                            if (job) {
                                job.status = 'error';
                                job.error = error?.response?.data?.message ?? damUploadFailedMsg;
                            }
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error?.response?.data?.message ?? damUploadFailedMsg,
                            });
                            return;
                        }
                    }

                    const job = this.dropUploads.find(u => u.id === jobId);
                    if (job) { job.status = 'done'; job.progress = 100; }
                    this.maybeAutoHide();

                    this.$emitter.emit('dam:folder-drop-uploaded', {
                        directoryId: this.currentDirectory.id,
                        count: totalUploaded,
                    });
                    if (! silent) {
                        this.$emitter.emit('add-flash', { type: 'success', message: damUploadCompleteMsg });
                    }
                    this.$refs.datagrid?.get();
                },

                clearDropUploads() {
                    clearTimeout(this._autoHideTimer);
                    this._autoHideTimer = null;
                    if (this.activeDropUploadCount > 0) {
                        this.dropUploads = this.dropUploads.filter(u => u.status === 'uploading');
                    } else {
                        this.dropUploads = [];
                    }
                },

                maybeAutoHide() {
                    if (this.dropUploads.length === 0) return;
                    if (this.dropUploads.some(u => u.status === 'uploading' || u.status === 'error')) return;
                    clearTimeout(this._autoHideTimer);
                    this._autoHideTimer = setTimeout(() => {
                        this.dropUploads = [];
                        this._autoHideTimer = null;
                    }, 2000);
                },
            }
        })
    </script>
    @endPushOnce

    {{-- Directory breadcrumb shown at the top of the asset grid --}}
    @pushOnce('scripts')
        <script type="text/x-template" id="v-dam-breadcrumb-template">
            <nav class="flex items-center gap-1 flex-wrap text-sm" aria-label="Directory breadcrumb">
                <template v-for="(crumb, i) in crumbs" :key="crumb.id">
                    <span v-if="i > 0" class="text-gray-400 dark:text-gray-500">/</span>
                    <button
                        type="button"
                        class="px-1 py-0.5 rounded transition-colors"
                        :class="i === crumbs.length - 1
                            ? 'text-violet-700 dark:text-violet-300 font-semibold cursor-default'
                            : 'text-gray-600 dark:text-gray-300 hover:text-violet-700 dark:hover:text-violet-400 hover:underline cursor-pointer'"
                        :disabled="i === crumbs.length - 1"
                        @click="i === crumbs.length - 1 ? null : navigateTo(crumb)"
                    >@{{ crumb.name }}</button>
                </template>
                <span v-if="!crumbs.length" class="text-base text-gray-600 dark:text-gray-300 font-bold">@lang('dam::app.admin.dam.index.root')</span>
            </nav>
        </script>

        <script type="module">
            app.component('v-dam-breadcrumb', {
                template: '#v-dam-breadcrumb-template',
                data() {
                    return { crumbs: [] };
                },
                mounted() {
                    this._onBreadcrumb = (crumbs) => { this.crumbs = Array.isArray(crumbs) ? crumbs : []; };
                    this.$emitter.on('current-directory-breadcrumb', this._onBreadcrumb);
                },
                beforeUnmount() {
                    if (this._onBreadcrumb) this.$emitter.off('current-directory-breadcrumb', this._onBreadcrumb);
                },
                methods: {
                    navigateTo(crumb) {
                        // Crumbs are clickable — reveal the directory in the tree,
                        // which triggers setFilters() and reloads the grid.
                        // Silent: the crumb was built from the current tree
                        // state, so a "not found" here is a transient race
                        // (mid-refresh / deleted-elsewhere), not a real error.
                        this.$emitter.emit('dam:reveal-directory', { id: crumb.id, silent: true });
                    },
                },
            });
        </script>
    @endPushOnce

    {{-- Standalone preview modal launched from the grid's eye icon --}}
    @include('dam::asset.grid-preview-modal')

    {{-- Share-link modal singleton; opened via the `open-share-modal` emitter event --}}
    @pushOnce('scripts')
        @include('dam::share.components.share-link-modal')
    @endPushOnce

    <v-share-link-modal></v-share-link-modal>
</x-admin::layouts>