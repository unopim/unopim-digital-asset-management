@pushOnce('scripts')
    <script type="text/x-template" id="v-dam-drop-upload-template">
        <div
            class="relative"
            @dragenter.prevent="onDragEnter"
            @dragover.prevent
            @dragleave="onDragLeave"
            @drop.prevent="onDrop"
        >
            <div
                v-if="isDragOver"
                class="absolute inset-0 z-50 backdrop-blur-sm border-2 border-dashed rounded-lg pointer-events-none"
                :class="canUpload
                    ? 'bg-white/90 dark:bg-cherry-800/95 border-violet-500 dark:border-violet-400'
                    : 'bg-red-50/80 dark:bg-red-950/30 border-red-400 dark:border-red-500'"
            ></div>

            <div
                v-if="isDragOver"
                :style="hintCardStyle"
                class="fixed z-[10011] -translate-x-1/2 -translate-y-1/2 flex flex-col items-center gap-3 rounded-2xl px-10 py-8 shadow-lg pointer-events-none"
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

            <slot></slot>

            <teleport to="body" v-if="isPrimary">
                <div
                    v-if="activeSessions.length || sessions.length"
                    data-dam-upload-panel
                    class="fixed bottom-4 ltr:right-4 rtl:left-4 z-[10005]"
                    style="display:flex; flex-direction:row-reverse; flex-wrap:wrap-reverse; align-items:flex-end; gap:0.75rem; max-width:calc(100vw - 2rem); pointer-events:none;"
                >
                    <div
                        v-for="session in sessions"
                        :key="session.id"
                        class="rounded-xl shadow-2xl overflow-hidden border border-gray-300 dark:border-cherry-600 bg-white dark:bg-cherry-800"
                        style="width:360px; max-width:calc(100vw - 2rem); flex-shrink:0; pointer-events:auto;"
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
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                >
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                <button type="button" class="p-0.5 text-white/70 hover:text-white rounded transition-colors" @click.stop="removeSession(session.id)">
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="!session.minimized"
                            class="max-h-40 overflow-y-auto"
                            @scroll="onJobScroll(session, $event)"
                        >
                            <div :style="{ height: jobWindow(session).padTop + 'px' }"></div>
                            <div
                                v-for="job in jobWindow(session).items"
                                :key="job.id"
                                data-dam-job-row
                                class="flex items-center gap-3 px-4 border-b border-gray-100 dark:border-cherry-700 hover:bg-gray-50 dark:hover:bg-cherry-700/50"
                                :style="{ height: rowHeight + 'px' }"
                            >
                                <div v-html="jobIconHtml(job)" class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 dark:text-gray-100 truncate leading-snug">@{{ job.name }}</p>
                                    <p v-if="job.parentPath" class="text-xs text-gray-400 dark:text-gray-500 truncate leading-snug">@{{ job.parentPath }}</p>
                                </div>
                                <div class="flex-shrink-0" v-html="jobStatusIcon(job)"></div>
                            </div>
                            <div :style="{ height: jobWindow(session).padBottom + 'px' }"></div>
                        </div>
                    </div>

                    <div
                        v-for="session in activeSessions"
                        :key="session.id"
                        class="rounded-xl shadow-2xl overflow-hidden border border-gray-300 dark:border-cherry-600 bg-white dark:bg-cherry-800"
                        style="width:360px; max-width:calc(100vw - 2rem); flex-shrink:0; pointer-events:auto;"
                    >
                        <div
                            class="flex items-center justify-between px-4 py-2.5 cursor-pointer select-none bg-violet-600 dark:bg-violet-700"
                            @click="session.minimized = !session.minimized"
                        >
                            <span class="text-sm font-semibold text-white truncate">@{{ sessionTitle(session) }}</span>
                            <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                                <button
                                    v-if="sessionRemaining(session) > 0 && !session.paused"
                                    type="button"
                                    class="px-2 py-0.5 text-xs font-medium text-white rounded transition-colors"
                                    style="background:rgba(255,255,255,0.18);"
                                    @click.stop="pauseSession(session)"
                                >@lang('dam::app.admin.dam.upload.pause')</button>
                                <button
                                    v-if="session.paused"
                                    type="button"
                                    class="px-2 py-0.5 text-xs font-medium text-white rounded transition-colors"
                                    style="background:rgba(255,255,255,0.18);"
                                    @click.stop="resumeSession(session)"
                                >@lang('dam::app.admin.dam.upload.resume')</button>
                                <button
                                    v-if="session.errorCount > 0 && sessionRemaining(session) === 0 && !session.paused"
                                    type="button"
                                    class="px-2 py-0.5 text-xs font-medium text-white rounded transition-colors"
                                    style="background:rgba(255,255,255,0.18);"
                                    @click.stop="retrySession(session)"
                                >@lang('dam::app.admin.dam.upload.retry')</button>
                                <button
                                    v-if="sessionOutstanding(session) > 0 || session.paused"
                                    type="button"
                                    class="px-2 py-0.5 text-xs font-medium text-white rounded transition-colors"
                                    style="background:rgba(255,255,255,0.18);"
                                    @click.stop="cancelSession(session)"
                                >@lang('dam::app.admin.dam.upload.cancel')</button>
                                <svg
                                    :class="session.minimized ? 'rotate-180' : ''"
                                    class="h-4 w-4 text-white/80 transition-transform"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                >
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                <button type="button" class="p-1 text-white/80 hover:text-white rounded transition-colors" @click.stop="clearSession(session)">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="sessionRemaining(session) > 0 && session.minimized" class="h-1 bg-gray-100 dark:bg-cherry-700">
                            <div class="h-full bg-violet-500 dark:bg-violet-400 transition-all duration-300" :style="{ width: session.overall + '%' }"></div>
                        </div>

                        <div
                            v-if="!session.minimized"
                            class="max-h-52 overflow-y-auto"
                            @scroll="onJobScroll(session, $event)"
                        >
                            <div :style="{ height: jobWindow(session).padTop + 'px' }"></div>
                            <div
                                v-for="job in jobWindow(session).items"
                                :key="job.id"
                                data-dam-job-row
                                class="flex items-center gap-3 px-4 border-b border-gray-100 dark:border-cherry-700 transition-colors hover:bg-gray-50 dark:hover:bg-cherry-700/50"
                                :style="{ height: rowHeight + 'px' }"
                            >
                                <div v-html="jobIconHtml(job)" class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate leading-snug">@{{ job.name }}</p>
                                    <p v-if="job.status === 'error'" class="text-xs text-red-500 dark:text-red-400 truncate leading-snug">@{{ job.error }}</p>
                                    <p v-else-if="job.status === 'interrupted'" class="text-xs text-amber-500 dark:text-amber-400 truncate leading-snug">@lang('dam::app.admin.explorer.upload.interrupted')</p>
                                    <p v-else-if="job.parentPath" class="text-xs text-gray-400 dark:text-gray-500 truncate leading-snug">@{{ job.parentPath }}</p>
                                    <div v-else-if="job.status === 'uploading'" class="mt-1 h-1 bg-gray-200 dark:bg-cherry-600 rounded-full overflow-hidden">
                                        <div class="h-full bg-violet-600 dark:bg-violet-500 transition-all duration-300 rounded-full" :style="{ width: job.progress + '%' }"></div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-xs text-gray-400 text-right min-w-[52px]">
                                    <span v-if="job.isFolder && job.status === 'creating'">@lang('dam::app.admin.explorer.upload.creating')</span>
                                    <span v-else-if="!job.isFolder && job.fileSize && job.status !== 'error'">@{{ formatFileSize(job.fileSize) }}</span>
                                </div>
                                <button
                                    v-if="job.status === 'error' && !job.isFolder"
                                    type="button"
                                    title="@lang('dam::app.admin.dam.upload.retry')"
                                    class="flex-shrink-0 p-1 text-violet-500 hover:text-violet-600 dark:text-violet-400 dark:hover:text-violet-300 rounded transition-colors"
                                    @click.stop="retryJob(session, job)"
                                >
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <div class="flex-shrink-0" v-html="jobStatusIcon(job)"></div>
                            </div>
                            <div :style="{ height: jobWindow(session).padBottom + 'px' }"></div>
                        </div>

                        <div v-if="!session.minimized" class="px-4 py-2.5 border-t border-gray-100 dark:border-cherry-700 bg-gray-50 dark:bg-cherry-900/40">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                                <span>@{{ session.doneCount }} of @{{ sessionFileJobCount(session) }} uploaded</span>
                                <span>@{{ session.overall }}%</span>
                            </div>
                            <div class="h-1.5 bg-gray-200 dark:bg-cherry-600 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="session.errorCount ? 'bg-red-500 dark:bg-red-600' : 'bg-violet-600 dark:bg-violet-500'"
                                    :style="{ width: session.overall + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </teleport>
        </div>
    </script>

    <script type="module">
        const damDropUploadFailedMsg   = @js(trans('dam::app.admin.dam.asset.datagrid.files-upload-failed'));
        const damDropUploadCompleteMsg = @js(trans('dam::app.admin.dam.index.upload-complete'));

        const DAM_UPLOAD_CONCURRENCY = Math.max(1, @js((int) config('dam.explorer.upload.concurrency', 4)));
        const DAM_RESUME_ENABLED     = @js((bool) config('dam.explorer.upload.resume_enabled', true));
        const DAM_RESUME_MAX_BYTES   = @js((int) config('dam.explorer.upload.resume_max_bytes', 524288000));
        const DAM_RESUME_STALE_MS    = @js((int) config('dam.explorer.upload.resume_stale_hours', 24)) * 3600 * 1000;
        const DAM_ROW_H              = 44;
        const DAM_UPLOAD_STATE_KEY   = 'dam_upload_state';

        const DAM_UPLOAD_ROUTES = {
            tracker:  "{{ route('admin.dam.assets.upload.tracker') }}",
            pause:    "{{ route('admin.dam.assets.upload.pause', ['uuid' => '__UUID__']) }}",
            resume:   "{{ route('admin.dam.assets.upload.resume', ['uuid' => '__UUID__']) }}",
            cancel:   "{{ route('admin.dam.assets.upload.cancel', ['uuid' => '__UUID__']) }}",
            retry:    "{{ route('admin.dam.assets.upload.retry', ['uuid' => '__UUID__']) }}",
            complete: "{{ route('admin.dam.assets.upload.complete', ['uuid' => '__UUID__']) }}",
        };

        let damPrimaryManager = null;


        const damFileBag = new Map();

        const DAM_IDB_NAME  = 'dam_uploads';
        const DAM_IDB_STORE = 'files';
        const damUploadStore = {
            available() { return typeof indexedDB !== 'undefined'; },
            _open() {
                return new Promise((resolve, reject) => {
                    const req = indexedDB.open(DAM_IDB_NAME, 1);
                    req.onupgradeneeded = () => {
                        const db = req.result;
                        if (! db.objectStoreNames.contains(DAM_IDB_STORE)) {
                            db.createObjectStore(DAM_IDB_STORE, { keyPath: 'id' });
                        }
                    };
                    req.onsuccess = () => resolve(req.result);
                    req.onerror   = () => reject(req.error);
                });
            },
            async put(id, blob, meta) {
                if (! this.available()) return;
                try {
                    const db = await this._open();
                    await new Promise((res, rej) => {
                        const tx = db.transaction(DAM_IDB_STORE, 'readwrite');
                        tx.objectStore(DAM_IDB_STORE).put({ id: String(id), blob, meta });
                        tx.oncomplete = res; tx.onerror = () => rej(tx.error);
                    });
                    db.close();
                } catch {}
            },
            async get(id) {
                if (! this.available()) return null;
                try {
                    const db = await this._open();
                    const rec = await new Promise((res, rej) => {
                        const tx = db.transaction(DAM_IDB_STORE, 'readonly');
                        const r  = tx.objectStore(DAM_IDB_STORE).get(String(id));
                        r.onsuccess = () => res(r.result || null); r.onerror = () => rej(r.error);
                    });
                    db.close();
                    return rec ? { blob: rec.blob, meta: rec.meta } : null;
                } catch { return null; }
            },
            async del(id) {
                if (! this.available()) return;
                try {
                    const db = await this._open();
                    await new Promise((res) => {
                        const tx = db.transaction(DAM_IDB_STORE, 'readwrite');
                        tx.objectStore(DAM_IDB_STORE).delete(String(id));
                        tx.oncomplete = res; tx.onerror = res;
                    });
                    db.close();
                } catch {}
            },
            async prune(olderThanMs) {
                if (! this.available()) return;
                const cutoff = Date.now() - olderThanMs;
                try {
                    const db = await this._open();
                    await new Promise((res) => {
                        const tx    = db.transaction(DAM_IDB_STORE, 'readwrite');
                        const store = tx.objectStore(DAM_IDB_STORE);
                        const cur   = store.openCursor();
                        cur.onsuccess = () => {
                            const c = cur.result;
                            if (! c) return;
                            if (c.value?.meta?.savedAt && c.value.meta.savedAt < cutoff) c.delete();
                            c.continue();
                        };
                        tx.oncomplete = res; tx.onerror = res;
                    });
                    db.close();
                } catch {}
            },
        };

        app.component('v-dam-drop-upload', {
            template: '#v-dam-drop-upload-template',

            props: {
                currentDirectory: { type: Object,  default: null },
                canUpload:        { type: Boolean, default: false },
            },

            emits: ['refresh-datagrid'],

            data() {
                return {
                    isPrimary: false,
                    isDragOver: false,
                    dragCounter: 0,
                    hintCardStyle: {},
                    activeSessions: [],    
                    sessions: [],          
                    nextSessionId: 1,
                    nextJobId: 1,
                    rowHeight: DAM_ROW_H,
                    _persistTimer: null,
                    _aggrRaf: null,
                    _datagridRefreshTimer: null,
                }
            },

            computed: {
                totalActiveCount() {
                    return this.activeSessions.reduce((sum, s) => sum + this.sessionActiveCount(s), 0);
                },
            },

            watch: {
                totalActiveCount(count) {
                    if (this.isPrimary) this.$emitter.emit('dam:drop-upload-active', count);
                },
            },

            mounted() {
                if (! damPrimaryManager) {
                    damPrimaryManager = this;
                    this.isPrimary = true;
                    if (DAM_RESUME_ENABLED) damUploadStore.prune(DAM_RESUME_STALE_MS);
                    this.restoreState();
                    this.$nextTick(() => this.resumeSessions());

                    this._onEnqueueEvent = (payload) => this.enqueueUpload(payload);
                    this.$emitter.on('dam:enqueue-upload', this._onEnqueueEvent);
                }
            },

            beforeUnmount() {
                clearTimeout(this._datagridRefreshTimer);
                clearTimeout(this._persistTimer);
                if (damPrimaryManager === this) {
                    if (this._onEnqueueEvent) this.$emitter.off('dam:enqueue-upload', this._onEnqueueEvent);
                    this.persistNow();
                    damPrimaryManager = null;
                }
            },

            methods: {
                enqueueUpload(payload) {
                    if (damPrimaryManager && damPrimaryManager !== this) {
                        return damPrimaryManager.enqueueUpload(payload);
                    }
                    return this.startSession(payload);
                },

                announceGrantedDirectories(ids) {
                    (ids || []).forEach((id) => this.$emitter.emit('dam:directory-granted', id));
                },

                async startSession({ items = [], folderPaths = [], targetDirId }) {
                    if (! targetDirId || (! items.length && ! folderPaths.length)) return;

                    let session = {
                        id: this.nextSessionId++,
                        uuid: this.newUuid(),
                        targetDirId,
                        minimized: false,
                        resumable: false,
                        paused: false,
                        cancelled: false,
                        uploadedAssetIds: [],
                        folderPaths: [...folderPaths],
                        jobs: [],
                        overall: 0,
                        doneCount: 0, errorCount: 0, queuedCount: 0, uploadingCount: 0,
                        bytesTotal: 0, bytesDone: 0,
                        scrollTop: 0, viewportH: 208,
                    };
                    this.activeSessions.push(session);
                    session = this.activeSessions[this.activeSessions.length - 1];

                    // Folder "creating" jobs (visual) for the directory structure.
                    for (const dirPath of [...folderPaths].sort()) {
                        const segs       = dirPath.split('/');
                        const name       = segs[segs.length - 1];
                        const parentPath = segs.length > 1 ? segs.slice(0, -1).join('/') + '/' : '';
                        session.jobs.push(this.makeJob({ name, parentPath, relativePath: dirPath, fileSize: 0, isFolder: true, status: 'creating' }));
                    }

                    // File jobs (queued). Bytes held non-reactively in damFileBag.
                    for (const item of items) {
                        const rel        = item.relativePath || item.file.name;
                        const segs       = rel.split('/');
                        const parentPath = segs.length > 1 ? segs.slice(0, -1).join('/') + '/' : '';
                        const job = this.makeJob({
                            name: item.file.name, parentPath, relativePath: rel,
                            fileSize: item.file.size, isFolder: false,
                            preserveRoot: !! item.preserveRoot, status: 'queued',
                        });
                        session.jobs.push(job);
                        damFileBag.set(job.id, item.file);
                        session.bytesTotal += item.file.size || 0;
                    }
                    session.queuedCount = items.length;

                    // Stash bytes for resume when the batch fits a safe quota.
                    session.resumable = await this.canStashBatch(session.bytesTotal);
                    if (session.resumable) {
                        for (const job of session.jobs) {
                            if (job.isFolder) continue;
                            const file = damFileBag.get(job.id);
                            if (file) damUploadStore.put(job.id, file, {
                                savedAt: Date.now(), sessionId: session.id,
                                name: job.name, relativePath: job.relativePath,
                                preserveRoot: job.preserveRoot, targetDirId,
                            });
                        }
                    }
                    this.persistState();

                    // Register the background upload session so it can be
                    // paused / cancelled / retried and survive a reload.
                    await this.startTracker(session);

                    // Phase 1: create the directory structure (idempotent).
                    if (folderPaths.length) {
                        try {
                            const res = await this.$axios.post("{{ route('admin.dam.directory.create_structure') }}", {
                                directory_id: targetDirId, paths: [...folderPaths],
                            });
                            this.announceGrantedDirectories(res.data?.granted_directory_ids);
                            session.jobs.forEach(j => { if (j.isFolder && j.status === 'creating') j.status = 'done'; });
                            this.$emitter.emit('dam:folder-drop-uploaded', { directoryId: targetDirId, count: 0 });
                        } catch {
                            session.jobs.forEach(j => { if (j.isFolder && j.status === 'creating') { j.status = 'error'; j.error = 'Failed to create folder'; } });
                        }
                        this.persistState();
                    }

                    // Phase 2: upload files through the concurrency pool.
                    await this.runWorkers(session);

                    await this.finishSession(session);
                },

                newUuid() {
                    if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
                    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                        const r = Math.random() * 16 | 0;
                        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                    });
                },

                async startTracker(session) {
                    if (! session.uuid || ! session.targetDirId) return;
                    try {
                        await this.$axios.post(DAM_UPLOAD_ROUTES.tracker, {
                            session_uuid: session.uuid,
                            directory_id: session.targetDirId,
                            total: session.jobs.filter(j => ! j.isFolder).length,
                        });
                    } catch {}
                },

                trackerAction(session, action) {
                    if (! session.uuid || ! DAM_UPLOAD_ROUTES[action]) return Promise.resolve();
                    const url = DAM_UPLOAD_ROUTES[action].replace('__UUID__', encodeURIComponent(session.uuid));
                    return this.$axios.post(url).catch(() => {});
                },

                // Called once the worker pool drains normally. A session that is
                // paused or cancelled is left untouched (its handler owns it); one
                // that finished with errors stays active so the user can Retry.
                async finishSession(session) {
                    if (session.cancelled || session.paused) return;

                    this.emitRefresh(session, true);
                    this.trackerAction(session, 'complete');

                    if (session.errorCount > 0) {
                        this.persistState();
                        return;
                    }

                    this.$emitter.emit('add-flash', { type: 'success', message: damDropUploadCompleteMsg });
                    this.archiveSession(session);
                    this.persistState();
                },

                // Pause: stop feeding the pool and abort any in-flight transfers
                // (they are re-queued). Active count falls to zero so the grid
                // unlocks and existing assets can be browsed mid-upload.
                pauseSession(session) {
                    session.paused = true;
                    session.jobs.forEach((j) => {
                        if (j.status === 'uploading' && j._abort) { try { j._abort.abort(); } catch {} }
                    });
                    this.trackerAction(session, 'pause');
                    this.persistState();
                },

                async resumeSession(session) {
                    if (! session.paused) return;
                    session.paused = false;
                    await this.trackerAction(session, 'resume');
                    await this.runWorkers(session);
                    await this.finishSession(session);
                },

                // Retry: re-queue transfer-failed files (their bytes are retained)
                // and ask the server to re-run any failed background finalisation.
                async retrySession(session) {
                    let requeued = 0;
                    for (const job of session.jobs) {
                        if (job.isFolder || job.status !== 'error') continue;
                        if (! damFileBag.has(job.id)) continue;
                        job.status = 'queued';
                        job.error = null;
                        job.progress = 0;
                        session.errorCount = Math.max(0, session.errorCount - 1);
                        requeued++;
                    }
                    this.recount(session);
                    this.trackerAction(session, 'retry');

                    session.paused = false;
                    if (requeued > 0) await this.runWorkers(session);
                    await this.finishSession(session);
                },

                // Retry a single failed file. Its bytes were retained (see
                // afterJob), so we just re-queue it and spin the worker pool.
                async retryJob(session, job) {
                    if (job.isFolder || job.status !== 'error' || ! damFileBag.has(job.id)) return;
                    job.status = 'queued';
                    job.error = null;
                    job.progress = 0;
                    session.errorCount = Math.max(0, session.errorCount - 1);
                    session.paused = false;
                    this.recount(session);
                    this.trackerAction(session, 'retry');
                    await this.runWorkers(session);
                    await this.finishSession(session);
                },

                cancelSession(session) {
                    session.cancelled = true;
                    session.paused = false;
                    session.jobs.forEach((j) => {
                        if (j._abort) { try { j._abort.abort(); } catch {} }
                        damFileBag.delete(j.id);
                        if (DAM_RESUME_ENABLED) damUploadStore.del(j.id);
                    });
                    this.trackerAction(session, 'cancel');
                    this.activeSessions = this.activeSessions.filter(s => s.id !== session.id);
                    this.persistState();
                },

                makeJob(overrides) {
                    return Object.assign({
                        id: this.nextJobId++, name: '', parentPath: '', relativePath: '',
                        fileSize: 0, isFolder: false, preserveRoot: false,
                        status: 'queued', progress: 0, error: null,
                    }, overrides);
                },

                async runWorkers(session) {
                    const queue = session.jobs.filter(j => ! j.isFolder && j.status === 'queued');
                    let cursor = 0;
                    const next = () => {
                        // Pause / cancel stops the pool from starting new transfers.
                        if (session.paused || session.cancelled) return Promise.resolve();
                        if (cursor >= queue.length) return Promise.resolve();
                        const job = queue[cursor++];
                        return this.uploadJob(session, job).then(next);
                    };
                    const pool = Math.min(DAM_UPLOAD_CONCURRENCY, queue.length || 1);
                    await Promise.all(Array.from({ length: pool }, next));
                },

                async uploadJob(session, job) {
                    const file = damFileBag.get(job.id);
                    if (! file) {
                        job.status = 'error';
                        job.error  = damDropUploadFailedMsg;
                        this.afterJob(session, job);
                        return;
                    }

                    job.status = 'uploading';
                    job.progress = 0;
                    this.recount(session);

                    const folder = !! job.preserveRoot;
                    const fd = new FormData();
                    fd.append('directory_id', session.targetDirId);
                    if (session.uuid) fd.append('session_uuid', session.uuid);
                    fd.append('files[]', file);
                    if (folder) {
                        fd.append('preserve_root', '1');
                        fd.append('relative_paths[]', job.relativePath);
                    }
                    const endpoint = folder
                        ? "{{ route('admin.dam.assets.upload_folder') }}"
                        : "{{ route('admin.dam.assets.upload') }}";

                    const controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
                    job._abort = controller;

                    try {
                        const res = await this.$axios.post(endpoint, fd, {
                            signal: controller ? controller.signal : undefined,
                            headers: { 'Content-Type': 'multipart/form-data' },
                            onUploadProgress: (e) => {
                                if (e.total && job.status === 'uploading') {
                                    job.progress = Math.min(99, Math.round((e.loaded / e.total) * 100));
                                    this.scheduleAggregate(session);
                                }
                            },
                        });
                        if (res.data?.success === false || (folder && (res.data.files || []).length === 0)) {
                            job.status = 'error';
                            job.error  = res.data?.message ?? damDropUploadFailedMsg;
                        } else {
                            job.status = 'done';
                            job.progress = 100;
                            if (folder) this.announceGrantedDirectories(res.data.granted_directory_ids);
                            // Track the ids of the assets this session created so
                            // consumers (e.g. the product edit asset picker) can
                            // auto-select freshly uploaded assets once it finishes.
                            if (! session.uploadedAssetIds) session.uploadedAssetIds = [];
                            (res.data.files || []).forEach(f => {
                                if (f && f.id != null) session.uploadedAssetIds.push(f.id);
                            });
                        }
                    } catch (error) {
                        job._abort = null;
                        // A pause/cancel abort is not a real failure: re-queue on
                        // pause, drop silently on cancel. Everything else is an error.
                        const aborted = this.$axios.isCancel?.(error)
                            || error?.code === 'ERR_CANCELED'
                            || error?.name === 'CanceledError'
                            || error?.name === 'AbortError';
                        if (aborted && session.cancelled) {
                            return;
                        }
                        if (aborted && session.paused) {
                            job.status = 'queued';
                            job.progress = 0;
                            this.recount(session);
                            return;
                        }
                        job.status = 'error';
                        job.error  = error?.response?.data?.message ?? damDropUploadFailedMsg;
                    }

                    job._abort = null;
                    this.afterJob(session, job);
                },

                afterJob(session, job) {
                    // Keep the bytes of a failed transfer so Retry can re-send it;
                    // only successful jobs release their buffered file.
                    if (job.status === 'done') {
                        damFileBag.delete(job.id);
                        if (DAM_RESUME_ENABLED) damUploadStore.del(job.id);
                    }
                    this.bumpCounters(session, job);
                    this.scheduleAggregate(session);
                    this.persistState();

                    // No mid-upload grid re-render: finalisation runs in the
                    // background and the grid refreshes once, when the session
                    // completes — so a bulk upload never disturbs existing assets.
                },

                // ── Progress counters (O(1) per tick instead of array re-scan) ──
                bumpCounters(session, job) {
                    if (job.status === 'done')  { session.doneCount++;  session.bytesDone += job.fileSize || 0; }
                    if (job.status === 'error') { session.errorCount++; }
                    this.recount(session);
                },

                recount(session) {
                    session.queuedCount    = session.jobs.filter(j => ! j.isFolder && j.status === 'queued').length;
                    session.uploadingCount = session.jobs.filter(j => ! j.isFolder && j.status === 'uploading').length;
                },

                aggregate(session) {
                    const fileTotal = session.jobs.filter(j => ! j.isFolder).length;
                    if (! fileTotal) return 100;
                    const settled = session.doneCount + session.errorCount;
                    const active  = session.jobs
                        .filter(j => ! j.isFolder && j.status === 'uploading')
                        .reduce((s, j) => s + (j.progress || 0), 0);
                    return Math.min(100, Math.round((settled * 100 + active) / fileTotal));
                },

                scheduleAggregate(session) {
                    if (this._aggrRaf) return;
                    const raf = (typeof requestAnimationFrame !== 'undefined') ? requestAnimationFrame : (cb) => setTimeout(cb, 16);
                    this._aggrRaf = raf(() => {
                        this._aggrRaf = null;
                        this.activeSessions.forEach(s => { s.overall = this.aggregate(s); });
                    });
                },

                onJobScroll(session, e) { session.scrollTop = e.target.scrollTop; },

                jobWindow(session) {
                    const total = session.jobs.length;
                    const start = Math.max(0, Math.floor((session.scrollTop || 0) / DAM_ROW_H) - 5);
                    const count = Math.ceil((session.viewportH || 208) / DAM_ROW_H) + 10;
                    const end   = Math.min(total, start + count);
                    return {
                        items:     session.jobs.slice(start, end),
                        padTop:    start * DAM_ROW_H,
                        padBottom: (total - end) * DAM_ROW_H,
                    };
                },

                emitRefresh(session, final) {
                    this.$emit('refresh-datagrid');
                    this.$emitter.emit('dam:uploads-refresh', {
                        directoryId: session.targetDirId,
                        assetIds: session.uploadedAssetIds ? [...session.uploadedAssetIds] : [],
                    });
                    this.$emitter.emit('dam:folder-drop-uploaded', { directoryId: session.targetDirId, count: session.doneCount });
                    if (final) this.$emitter.emit('dam:tree-reload');
                },

                sessionActiveCount(session) {
                    return session.jobs.filter(u => u.status === 'uploading' || u.status === 'creating').length;
                },
                // Files still to settle (queued or in-flight). Unlike the active
                // count this only decreases, so control buttons keyed off it don't
                // flicker between batches or when a paused job is re-queued.
                sessionRemaining(session) {
                    return session.jobs.filter(u => ! u.isFolder && (u.status === 'queued' || u.status === 'uploading')).length;
                },
                // Files left interrupted by a page reload whose bytes could not be
                // restored (large batches aren't stashed). They can't be resumed —
                // the user must re-add them — but the session can still be cancelled.
                sessionInterruptedCount(session) {
                    return session.jobs.filter(u => ! u.isFolder && u.status === 'interrupted').length;
                },
                // Anything not yet finished successfully/failed: drives whether the
                // Cancel control is offered (so a reload-interrupted session isn't
                // stuck with no controls but the close button).
                sessionOutstanding(session) {
                    return this.sessionRemaining(session) + this.sessionInterruptedCount(session);
                },
                sessionFileJobCount(session) {
                    return session.jobs.filter(u => ! u.isFolder).length;
                },
                sessionTitle(session) {
                    const total = this.sessionFileJobCount(session);
                    if (session.paused) {
                        return `Paused — ${session.doneCount} of ${total} uploaded`;
                    }
                    if (this.sessionRemaining(session) > 0) {
                        const pct = session.minimized ? ` ${session.overall}%` : '';
                        return `Uploading ${total} file${total !== 1 ? 's' : ''}…${pct}`;
                    }
                    const interrupted = this.sessionInterruptedCount(session);
                    if (interrupted > 0) {
                        return `${session.doneCount} of ${total} uploaded · ${interrupted} interrupted`;
                    }
                    if (session.errorCount > 0) return `${session.doneCount} uploaded, ${session.errorCount} failed`;
                    return `${session.doneCount} of ${total} uploaded`;
                },
                sessionSummary(session) {
                    const total = this.sessionFileJobCount(session);
                    if (session.errorCount > 0) return `${session.doneCount} uploaded, ${session.errorCount} failed`;
                    return `${session.doneCount} of ${total} uploaded`;
                },

                archiveSession(session) {
                    this.activeSessions = this.activeSessions.filter(s => s.id !== session.id);
                    this.sessions.push(Object.assign({}, session, { minimized: true, scrollTop: 0, viewportH: 160 }));
                },
                clearSession(session) {
                    if (this.sessionActiveCount(session) > 0) {
                        session.jobs = session.jobs.filter(u => u.status === 'uploading' || u.status === 'creating' || u.status === 'queued');
                    } else {
                        this.activeSessions = this.activeSessions.filter(s => s.id !== session.id);
                    }
                    this.persistState();
                },
                removeSession(sessionId) {
                    this.sessions = this.sessions.filter(s => s.id !== sessionId);
                    this.persistState();
                },

                async canStashBatch(totalBytes) {
                    if (! DAM_RESUME_ENABLED || ! damUploadStore.available()) return false;
                    if (totalBytes > DAM_RESUME_MAX_BYTES) return false;
                    try {
                        if (navigator.storage?.estimate) {
                            const { quota = 0, usage = 0 } = await navigator.storage.estimate();
                            if (totalBytes > Math.max(0, quota - usage) * 0.5) return false;
                        }
                    } catch {}
                    return true;
                },

                serializeSession(s) {
                    return {
                        id: s.id, uuid: s.uuid, targetDirId: s.targetDirId, minimized: s.minimized,
                        resumable: s.resumable, paused: !! s.paused, folderPaths: s.folderPaths || [],
                        doneCount: s.doneCount, errorCount: s.errorCount,
                        bytesTotal: s.bytesTotal, bytesDone: s.bytesDone, overall: s.overall,
                        jobs: s.jobs.map(j => ({
                            id: j.id, name: j.name, parentPath: j.parentPath, relativePath: j.relativePath,
                            fileSize: j.fileSize, isFolder: j.isFolder, preserveRoot: j.preserveRoot,
                            status: j.status, progress: j.progress, error: j.error,
                        })),
                    };
                },
                persistState() {
                    if (! this.isPrimary) return;
                    clearTimeout(this._persistTimer);
                    this._persistTimer = setTimeout(() => this.persistNow(), 200);
                },
                persistNow() {
                    if (! this.isPrimary) return;
                    try {
                        if (! this.activeSessions.length && ! this.sessions.length) {
                            localStorage.removeItem(DAM_UPLOAD_STATE_KEY);
                            return;
                        }
                        localStorage.setItem(DAM_UPLOAD_STATE_KEY, JSON.stringify({
                            savedAt: Date.now(),
                            nextSessionId: this.nextSessionId,
                            nextJobId: this.nextJobId,
                            activeSessions: this.activeSessions.map(s => this.serializeSession(s)),
                            sessions:       this.sessions.map(s => this.serializeSession(s)),
                        }));
                    } catch {}
                },
                restoreState() {
                    let raw = null;
                    try { raw = localStorage.getItem(DAM_UPLOAD_STATE_KEY); } catch {}
                    if (! raw) return;
                    let data;
                    try { data = JSON.parse(raw); } catch { return; }
                    if (data.savedAt && (Date.now() - data.savedAt) > DAM_RESUME_STALE_MS) {
                        try { localStorage.removeItem(DAM_UPLOAD_STATE_KEY); } catch {}
                        return;
                    }
                    this.nextSessionId = data.nextSessionId || 1;
                    this.nextJobId     = data.nextJobId || 1;

                    const rehydrate = (s, viewportH) => {
                        s.scrollTop = 0;
                        s.viewportH = viewportH;
                        s.cancelled = false;
                        s.paused = false;
                        s.jobs.forEach(j => {
                            // Unfinished jobs are provisionally interrupted; resumeSessions()
                            // promotes any whose bytes survive in IndexedDB back to queued.
                            if (j.status === 'uploading' || j.status === 'queued' || j.status === 'creating') {
                                j.status = 'interrupted';
                            }
                        });
                        s.doneCount  = s.jobs.filter(j => ! j.isFolder && j.status === 'done').length;
                        s.errorCount = s.jobs.filter(j => ! j.isFolder && j.status === 'error').length;
                        s.bytesTotal = s.jobs.reduce((a, j) => a + (j.fileSize || 0), 0);
                        s.bytesDone  = s.jobs.filter(j => j.status === 'done').reduce((a, j) => a + (j.fileSize || 0), 0);
                        this.recount(s);
                        s.overall = this.aggregate(s);
                        return s;
                    };

                    this.sessions       = (data.sessions || []).map(s => rehydrate(s, 160));
                    this.activeSessions = (data.activeSessions || []).map(s => rehydrate(s, 208));
                },
                async resumeSessions() {
                    if (! DAM_RESUME_ENABLED || ! damUploadStore.available()) return;
                    for (const session of this.activeSessions) {
                        const toResume = [];
                        for (const job of session.jobs) {
                            if (job.isFolder || job.status !== 'interrupted') continue;
                            const rec = await damUploadStore.get(job.id);
                            if (rec?.blob) {
                                damFileBag.set(job.id, new File([rec.blob], job.name, { type: rec.blob.type }));
                                job.status = 'queued';
                                job.progress = 0;
                                toResume.push(job);
                            }
                        }
                        if (! toResume.length) continue;

                        this.recount(session);
                        if (session.folderPaths?.length) {
                            try {
                                const res = await this.$axios.post("{{ route('admin.dam.directory.create_structure') }}", {
                                    directory_id: session.targetDirId, paths: [...session.folderPaths],
                                });
                                this.announceGrantedDirectories(res.data?.granted_directory_ids);
                            } catch {}
                        }
                        this.runWorkers(session).then(() => this.finishSession(session));
                    }
                },

                onDragEnter(e) {
                    if (! e.dataTransfer?.types?.includes('Files')) return;
                    this.dragCounter++;
                    this.isDragOver = true;
                    if (this.dragCounter === 1) {
                        this.$nextTick(() => {
                            const rect       = this.$el.getBoundingClientRect();
                            const visibleTop = Math.max(rect.top, 0);
                            const visibleBot = Math.min(rect.bottom, window.innerHeight);
                            this.hintCardStyle = {
                                top:  ((visibleTop + visibleBot) / 2) + 'px',
                                left: (rect.left + rect.width / 2) + 'px',
                            };
                        });
                    }
                },
                onDragLeave(e) {
                    if (! e.dataTransfer?.types?.includes('Files')) return;
                    this.dragCounter--;
                    if (this.dragCounter <= 0) { this.dragCounter = 0; this.isDragOver = false; }
                },
                async onDrop(event) {
                    this.dragCounter = 0;
                    this.isDragOver = false;
                    if (! event.dataTransfer?.types?.includes('Files')) return;
                    if (! this.canUpload || ! this.currentDirectory) return;

                    const targetDirId = this.currentDirectory.id;
                    const items = event.dataTransfer?.items;
                    if (! items || items.length === 0) return;

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

                    const payloadItems = flatFiles.map(f => ({ file: f, relativePath: f.name, preserveRoot: false }));
                    const folderPaths  = new Set();

                    for (const dirEntry of dirEntries) {
                        const { files, emptyDirs } = await this.readFolderEntries([dirEntry]);
                        emptyDirs.forEach(d => folderPaths.add(d));
                        for (const { file, relativePath } of files) {
                            payloadItems.push({ file, relativePath, preserveRoot: true });
                            const segs = relativePath.split('/');
                            for (let i = 1; i < segs.length; i++) folderPaths.add(segs.slice(0, i).join('/'));
                        }
                    }

                    this.enqueueUpload({ items: payloadItems, folderPaths: [...folderPaths], targetDirId });
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
                            batch = await new Promise(resolve => reader.readEntries(resolve, () => resolve([]))).catch(() => []);
                            allChildren.push(...batch);
                        } while (batch.length > 0);
                        if (allChildren.length === 0) { emptyDirs.push(path); return; }
                        await Promise.all(allChildren.map(child => walk(child, path)));
                    };
                    await Promise.all(entries.map(e => walk(e, '')));
                    return { files, emptyDirs };
                },

                jobStatusIcon(job) {
                    if (job.status === 'uploading' || job.status === 'creating') {
                        return `<svg class="animate-spin h-3.5 w-3.5 text-violet-500 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
                    }
                    if (job.status === 'done') {
                        return `<svg class="h-3.5 w-3.5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>`;
                    }
                    if (job.status === 'error') {
                        return `<svg class="h-3.5 w-3.5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`;
                    }
                    if (job.status === 'interrupted') {
                        return `<svg class="h-3.5 w-3.5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>`;
                    }
                    return `<svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>`;
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
            },
        });
    </script>
@endPushOnce
