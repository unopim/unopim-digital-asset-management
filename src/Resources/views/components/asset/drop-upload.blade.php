{{--
    v-dam-drop-upload component
    Handles drag-and-drop file/folder uploads:
    - Drag overlay + hint card
    - Session-based batch history (completed batches stack above active one)
    - Compact progress strip visible even when minimized
    - File-only counts in footer tracker
--}}
@pushOnce('scripts')
    <script type="text/x-template" id="v-dam-drop-upload-template">
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
                class="absolute inset-0 z-50 backdrop-blur-sm border-2 border-dashed rounded-lg pointer-events-none"
                :class="canUpload
                    ? 'bg-white/90 dark:bg-cherry-800/95 border-violet-500 dark:border-violet-400'
                    : 'bg-red-50/80 dark:bg-red-950/30 border-red-400 dark:border-red-500'"
            ></div>

            <!-- Drop hint card: fixed at visible viewport centre of the drag target -->
            <div
                v-if="isDragOver"
                :style="hintCardStyle"
                class="fixed z-[51] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center gap-3 rounded-2xl px-10 py-8 shadow-lg pointer-events-none"
                :class="canUpload
                    ? 'bg-violet-50 dark:bg-violet-950/80 border border-violet-200 dark:border-violet-700'
                    : 'bg-red-50 dark:bg-red-950/80 border border-red-200 dark:border-red-700'"
            >
                <template v-if="canUpload">
                    <i class="icon-dam-upload text-6xl text-violet-500 dark:text-violet-400 block"></i>
                    <p class="text-violet-700 dark:text-violet-300 font-semibold text-base text-center">
                        @lang('dam::app.admin.dam.index.drop-zone-hint')
                    </p>
                </template>
                <template v-else>
                    <svg class="h-14 w-14 text-red-400 dark:text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-red-600 dark:text-red-400 font-semibold text-base text-center">
                        @lang('dam::app.admin.dam.index.drop-zone-no-permission')
                    </p>
                </template>
            </div>

            <!-- Default slot: breadcrumb + upload button + datagrid -->
            <slot></slot>

            <!-- Upload panel -->
            <div
                v-if="dropUploads.length || sessions.length"
                class="fixed bottom-4 ltr:right-8 rtl:left-8 z-[10005] w-[460px] rounded-xl shadow-2xl overflow-hidden border border-gray-300 dark:border-cherry-600"
            >
                <!-- Previous completed sessions (stacked above, collapsed by default) -->
                <div
                    v-for="session in sessions"
                    :key="session.id"
                    class="bg-white dark:bg-cherry-800 border-b-4 border-gray-200 dark:border-cherry-600"
                >
                    <div
                        class="flex items-center justify-between px-4 py-2.5 bg-violet-600 dark:bg-violet-700 cursor-pointer select-none"
                        @click="session.minimized = !session.minimized"
                    >
                        <span class="text-sm font-semibold text-white truncate">@{{ sessionSummary(session) }}</span>
                        <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                            <svg
                                :class="session.minimized ? 'rotate-180' : ''"
                                class="h-3.5 w-3.5 text-white/70 transition-transform"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <button
                                type="button"
                                class="p-0.5 text-white/70 hover:text-white rounded transition-colors"
                                @click.stop="removeSession(session.id)"
                            >
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <!-- Expandable history row list -->
                    <div v-if="!session.minimized" class="max-h-40 overflow-y-auto divide-y divide-gray-100 dark:divide-cherry-700">
                        <div
                            v-for="job in session.jobs"
                            :key="job.id"
                            class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 dark:hover:bg-cherry-700/50"
                        >
                            <div v-html="jobIconHtml(job)" class="flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 dark:text-gray-100 truncate leading-snug">@{{ job.name }}</p>
                                <p v-if="job.parentPath" class="text-xs text-gray-400 dark:text-gray-500 truncate leading-snug">@{{ job.parentPath }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <svg v-if="job.status === 'done'" class="h-3.5 w-3.5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else-if="job.status === 'error'" class="h-3.5 w-3.5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active session -->
                <div v-if="dropUploads.length" class="bg-white dark:bg-cherry-800">
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between px-4 py-2.5 cursor-pointer select-none bg-violet-600 dark:bg-violet-700"
                        @click="dropPanelMinimized = !dropPanelMinimized"
                    >
                        <span class="text-sm font-semibold text-white truncate">@{{ dropPanelTitle }}</span>
                        <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                            <svg
                                :class="dropPanelMinimized ? 'rotate-180' : ''"
                                class="h-4 w-4 text-white/80 transition-transform"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            <button
                                type="button"
                                class="p-1 text-white/80 hover:text-white rounded transition-colors"
                                @click.stop="clearDropUploads"
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Compact progress strip — only visible when minimized -->
                    <div v-if="activeDropUploadCount > 0 && dropPanelMinimized" class="h-1 bg-gray-100 dark:bg-cherry-700">
                        <div
                            class="h-full bg-violet-500 dark:bg-violet-400 transition-all duration-300"
                            :style="{ width: overallProgress + '%' }"
                        ></div>
                    </div>

                    <!-- Row list -->
                    <div v-if="!dropPanelMinimized" class="max-h-52 overflow-y-auto divide-y divide-gray-100 dark:divide-cherry-700">
                        <div
                            v-for="job in dropUploads"
                            :key="job.id"
                            class="flex items-start gap-3 px-4 py-2.5 transition-colors hover:bg-gray-50 dark:hover:bg-cherry-700/50"
                        >
                            <!-- file-type icon -->
                            <div v-html="jobIconHtml(job)" class="flex-shrink-0 mt-0.5"></div>

                            <!-- name + parent path + inline progress bar -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate leading-snug">@{{ job.name }}</p>
                                <p v-if="job.parentPath" class="text-xs text-gray-400 dark:text-gray-500 truncate leading-snug">@{{ job.parentPath }}</p>
                                <p v-if="job.status === 'error'" class="text-xs text-red-500 dark:text-red-400 truncate leading-snug mt-0.5">@{{ job.error }}</p>
                                <div v-else-if="job.status === 'uploading'" class="mt-1.5 h-1 bg-gray-200 dark:bg-cherry-600 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-600 dark:bg-violet-500 transition-all duration-300 rounded-full" :style="{ width: job.progress + '%' }"></div>
                                </div>
                            </div>

                            <!-- size / Creating… label -->
                            <div class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-400 text-right pt-0.5 min-w-[52px]">
                                <span v-if="job.isFolder && job.status === 'creating'">Creating…</span>
                                <span v-else-if="!job.isFolder && job.fileSize && job.status !== 'error'">@{{ formatFileSize(job.fileSize) }}</span>
                            </div>

                            <!-- status indicator -->
                            <div class="flex-shrink-0 mt-0.5">
                                <svg v-if="job.status === 'uploading' || job.status === 'creating'" class="animate-spin h-3.5 w-3.5 text-violet-500 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else-if="job.status === 'done'" class="h-3.5 w-3.5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else-if="job.status === 'error'" class="h-3.5 w-3.5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else class="h-3.5 w-3.5 text-gray-300 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Footer: file-only counts -->
                    <div v-if="!dropPanelMinimized" class="px-4 py-2.5 border-t border-gray-100 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900/40">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                            <span>@{{ uploadedCount }} of @{{ fileJobCount }} uploaded</span>
                            <span>@{{ Math.round(overallProgress) }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-200 dark:bg-cherry-600 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="dropUploads.some(u => u.status === 'error') ? 'bg-red-500 dark:bg-red-600' : 'bg-violet-600 dark:bg-violet-500'"
                                :style="{ width: overallProgress + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        const damDropUploadFailedMsg   = @js(trans('dam::app.admin.dam.asset.datagrid.files-upload-failed'));
        const damDropUploadCompleteMsg = @js(trans('dam::app.admin.dam.index.upload-complete'));
        const damDropMaxFileUploads    = @js((int) ini_get('max_file_uploads'));

        app.component('v-dam-drop-upload', {
            template: '#v-dam-drop-upload-template',

            props: {
                currentDirectory: {
                    type: Object,
                    default: null,
                },

                canUpload: {
                    type: Boolean,
                    default: false,
                },
            },

            emits: ['refresh-datagrid'],

            data() {
                return {
                    isDragOver: false,
                    dragCounter: 0,
                    hintCardStyle: {},
                    dropUploads: [],       // jobs for the active (current) session
                    sessions: [],          // completed session history: [{ id, jobs[], minimized }]
                    nextSessionId: 1,
                    dropPanelMinimized: false,
                    nextDropJobId: 1,
                    _datagridRefreshTimer: null,
                }
            },

            computed: {
                activeDropUploadCount() {
                    return this.dropUploads.filter(u => u.status === 'uploading' || u.status === 'creating').length;
                },

                fileJobCount() {
                    return this.dropUploads.filter(u => ! u.isFolder).length;
                },

                overallProgress() {
                    const fileJobs = this.dropUploads.filter(u => ! u.isFolder);
                    if (fileJobs.length === 0) return 100;
                    const done    = fileJobs.filter(u => u.status === 'done').length;
                    const errors  = fileJobs.filter(u => u.status === 'error').length;
                    const active  = fileJobs.filter(u => u.status === 'uploading');
                    const progSum = active.reduce((s, u) => s + u.progress, 0);
                    return Math.min(100, Math.round(((done + errors) * 100 + progSum) / fileJobs.length));
                },

                uploadedCount() {
                    return this.dropUploads.filter(u => ! u.isFolder && u.status === 'done').length;
                },

                dropPanelTitle() {
                    const fileJobs = this.dropUploads.filter(u => ! u.isFolder);
                    const total    = fileJobs.length;
                    const done     = fileJobs.filter(u => u.status === 'done').length;
                    const skipped  = fileJobs.filter(u => u.status === 'error').length;
                    const active   = this.activeDropUploadCount;

                    if (active > 0) {
                        const pct = this.dropPanelMinimized ? ` ${Math.round(this.overallProgress)}%` : '';
                        return `Uploading ${total} file${total !== 1 ? 's' : ''}…${pct}`;
                    }
                    if (skipped > 0) return `${done} uploaded, ${skipped} skipped`;
                    return `${done} of ${total} uploaded`;
                },
            },

            watch: {
                activeDropUploadCount(count) {
                    this.$emitter.emit('dam:drop-upload-active', count);
                },
            },

            methods: {
                sessionSummary(session) {
                    const fileJobs = session.jobs.filter(u => ! u.isFolder);
                    const done     = fileJobs.filter(u => u.status === 'done').length;
                    const skipped  = fileJobs.filter(u => u.status === 'error').length;
                    if (skipped > 0) return `${done} uploaded, ${skipped} skipped`;
                    return `${done} of ${fileJobs.length} uploaded`;
                },

                removeSession(sessionId) {
                    this.sessions = this.sessions.filter(s => s.id !== sessionId);
                },

                archiveCurrentIfDone() {
                    if (this.dropUploads.length === 0) return;
                    if (this.activeDropUploadCount > 0) return;
                    this.sessions.push({
                        id: this.nextSessionId++,
                        jobs: [...this.dropUploads],
                        minimized: true,
                    });
                    this.dropUploads = [];
                },

                onDragEnter() {
                    this.dragCounter++;
                    this.isDragOver = true;
                    if (this.dragCounter === 1) {
                        this.$nextTick(() => {
                            const rect        = this.$el.getBoundingClientRect();
                            const visibleTop  = Math.max(rect.top, 0);
                            const visibleBot  = Math.min(rect.bottom, window.innerHeight);
                            this.hintCardStyle = {
                                top:  ((visibleTop + visibleBot) / 2) + 'px',
                                left: (rect.left + rect.width  / 2) + 'px',
                            };
                        });
                    }
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

                    if (! this.canUpload || ! this.currentDirectory) return;

                    const items = event.dataTransfer?.items;
                    if (! items || items.length === 0) return;

                    // Archive the previous completed session before starting fresh
                    this.archiveCurrentIfDone();

                    const flatFiles  = [];
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
                        this.runBatchUpload(flatFiles.map(f => ({ file: f, relativePath: f.name })), false);
                        return;
                    }

                    if (flatFiles.length > 0) {
                        this.runBatchUpload(flatFiles.map(f => ({ file: f, relativePath: f.name })), false);
                    }

                    const allFolderFiles = [];
                    const allEmptyDirs   = [];

                    for (const dirEntry of dirEntries) {
                        const { files, emptyDirs } = await this.readFolderEntries([dirEntry]);
                        allFolderFiles.push(...files);
                        allEmptyDirs.push(...emptyDirs);
                    }

                    const uniqueDirPaths = new Set(allEmptyDirs);
                    for (const { relativePath } of allFolderFiles) {
                        const segs = relativePath.split('/');
                        for (let i = 1; i < segs.length; i++) {
                            uniqueDirPaths.add(segs.slice(0, i).join('/'));
                        }
                    }

                    this.dropPanelMinimized = false;

                    const folderJobIds = [];
                    for (const dirPath of [...uniqueDirPaths].sort()) {
                        const segs       = dirPath.split('/');
                        const name       = segs[segs.length - 1];
                        const parentPath = segs.length > 1 ? segs.slice(0, -1).join('/') + '/' : '';
                        const jobId      = this.nextDropJobId++;
                        this.dropUploads.push({ id: jobId, name, parentPath, fileSize: 0, isFolder: true, status: 'creating', progress: 0, error: null });
                        folderJobIds.push(jobId);
                    }

                    if (uniqueDirPaths.size > 0) {
                        try {
                            await this.$axios.post(
                                "{{ route('admin.dam.directory.create_structure') }}",
                                { directory_id: this.currentDirectory.id, paths: [...uniqueDirPaths] }
                            );
                            folderJobIds.forEach(jid => {
                                const job = this.dropUploads.find(u => u.id === jid);
                                if (job) job.status = 'done';
                            });
                            this.$emitter.emit('dam:folder-drop-uploaded', { directoryId: this.currentDirectory.id, count: 0 });
                        } catch (e) {
                            folderJobIds.forEach(jid => {
                                const job = this.dropUploads.find(u => u.id === jid);
                                if (job) { job.status = 'error'; job.error = 'Failed to create folder'; }
                            });
                        }
                    }

                    if (allFolderFiles.length === 0) return;

                    await this.runBatchUpload(allFolderFiles, true);

                    const anyError = this.dropUploads.some(u => u.status === 'error');
                    if (! anyError) {
                        this.$emitter.emit('add-flash', { type: 'success', message: damDropUploadCompleteMsg });
                    }
                },

                async readFolderEntries(entries) {
                    const files     = [];
                    const emptyDirs = [];

                    const walk = async (entry, pathPrefix) => {
                        const path = pathPrefix ? `${pathPrefix}/${entry.name}` : entry.name;

                        if (entry.isFile) {
                            await new Promise(resolve => {
                                entry.file(file => { files.push({ file, relativePath: path }); resolve(); }, resolve);
                            });
                            return;
                        }

                        const reader      = entry.createReader();
                        const allChildren = [];
                        let batch;
                        do {
                            batch = await new Promise(
                                resolve => reader.readEntries(resolve, () => resolve([]))
                            ).catch(() => []);
                            allChildren.push(...batch);
                        } while (batch.length > 0);

                        if (allChildren.length === 0) {
                            emptyDirs.push(path);
                            return;
                        }

                        await Promise.all(allChildren.map(child => walk(child, path)));
                    };

                    await Promise.all(entries.map(e => walk(e, '')));
                    return { files, emptyDirs };
                },

                async runBatchUpload(fileEntries, isFolderUpload) {
                    if (fileEntries.length === 0) return;

                    this.dropPanelMinimized = false;

                    const jobIds = fileEntries.map(({ file, relativePath }) => {
                        const jobId      = this.nextDropJobId++;
                        const segs       = relativePath ? relativePath.split('/') : [file.name];
                        const parentPath = segs.length > 1 ? segs.slice(0, -1).join('/') + '/' : '';
                        this.dropUploads.push({ id: jobId, name: file.name, parentPath, fileSize: file.size, isFolder: false, status: 'uploading', progress: 0, error: null });
                        return jobId;
                    });

                    const endpoint = isFolderUpload
                        ? "{{ route('admin.dam.assets.upload_folder') }}"
                        : "{{ route('admin.dam.assets.upload') }}";

                    let successSinceLastEmit      = 0;
                    let completedSinceLastRefresh = 0;
                    const refreshChunkSize        = damDropMaxFileUploads > 0 ? damDropMaxFileUploads : 20;

                    for (let i = 0; i < fileEntries.length; i++) {
                        const entry = fileEntries[i];
                        const jobId = jobIds[i];

                        const formData = new FormData();
                        formData.append('directory_id', this.currentDirectory.id);
                        formData.append('files[]', entry.file);
                        if (isFolderUpload) {
                            formData.append('preserve_root', '1');
                            formData.append('relative_paths[]', entry.relativePath);
                        }

                        try {
                            const response = await this.$axios.post(endpoint, formData, {
                                headers: { 'Content-Type': 'multipart/form-data' },
                                onUploadProgress: (e) => {
                                    if (e.total) {
                                        const job = this.dropUploads.find(u => u.id === jobId);
                                        if (job && job.status === 'uploading') {
                                            job.progress = Math.min(99, Math.round((e.loaded / e.total) * 100));
                                        }
                                    }
                                },
                            });

                            const job = this.dropUploads.find(u => u.id === jobId);
                            if (! job) continue;

                            if (response.data?.success === false) {
                                job.status = 'error';
                                job.error  = response.data.message ?? damDropUploadFailedMsg;
                            } else if (isFolderUpload && (response.data.files || []).length === 0) {
                                job.status = 'error';
                                job.error  = response.data.message ?? damDropUploadFailedMsg;
                            } else {
                                job.status   = 'done';
                                job.progress = 100;
                                successSinceLastEmit++;
                            }
                        } catch (error) {
                            const job = this.dropUploads.find(u => u.id === jobId);
                            if (job) {
                                job.status = 'error';
                                job.error  = error?.response?.data?.message ?? damDropUploadFailedMsg;
                            }
                        }

                        completedSinceLastRefresh++;
                        if (completedSinceLastRefresh >= refreshChunkSize) {
                            this.scheduleDatagridRefresh();
                            this.$emitter.emit('dam:folder-drop-uploaded', {
                                directoryId: this.currentDirectory.id,
                                count: successSinceLastEmit,
                            });
                            successSinceLastEmit = 0;
                            completedSinceLastRefresh = 0;
                        }
                    }

                    // Final refresh — cancel debounce and fire immediately
                    clearTimeout(this._datagridRefreshTimer);
                    this._datagridRefreshTimer = null;
                    this.$emit('refresh-datagrid');

                    // Emit remainder count for tree
                    this.$emitter.emit('dam:folder-drop-uploaded', {
                        directoryId: this.currentDirectory.id,
                        count: successSinceLastEmit,
                    });
                },

                scheduleDatagridRefresh() {
                    clearTimeout(this._datagridRefreshTimer);
                    this._datagridRefreshTimer = setTimeout(() => {
                        this._datagridRefreshTimer = null;
                        this.$emit('refresh-datagrid');
                    }, 500);
                },

                jobIconHtml(job) {
                    if (job.isFolder) {
                        return `<svg class="h-5 w-5 text-amber-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>`;
                    }
                    const name = job.name || '';
                    const ext  = name.includes('.') ? name.split('.').pop().toLowerCase() : '';

                    const isImage = ['jpg','jpeg','png','gif','webp','svg','bmp','tiff','avif','ico'].includes(ext);
                    const isVideo = ['mp4','mov','avi','mkv','webm','flv','wmv','m4v'].includes(ext);
                    const isAudio = ['mp3','wav','ogg','flac','aac','m4a','wma'].includes(ext);
                    const isDoc   = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','rar','7z'].includes(ext);

                    if (isImage) return `<svg class="h-5 w-5 text-blue-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>`;
                    if (isVideo) return `<svg class="h-5 w-5 text-violet-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/></svg>`;
                    if (isAudio) return `<svg class="h-5 w-5 text-pink-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>`;
                    if (isDoc)   return `<svg class="h-5 w-5 text-blue-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>`;
                    return `<svg class="h-5 w-5 text-gray-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>`;
                },

                formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                },

                clearDropUploads() {
                    if (this.dropUploads.some(u => u.status === 'uploading' || u.status === 'creating')) {
                        this.dropUploads = this.dropUploads.filter(u => u.status === 'uploading' || u.status === 'creating');
                    } else {
                        this.dropUploads = [];
                    }
                },
            },

            beforeUnmount() {
                clearTimeout(this._datagridRefreshTimer);
            },
        });
    </script>
@endPushOnce
