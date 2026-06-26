<x-admin::layouts>
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
            <div class="{{ config('dam.explorer.enabled') ? 'flex flex-col min-w-0' : '' }}">
                {!! view_render_event('dam.admin.main.form.before') !!}
                    <div class="{{ config('dam.explorer.enabled') ? 'flex gap-2.5 max-xl:flex-wrap items-start min-w-0' : 'flex gap-2.5 mt-3.5 max-xl:flex-wrap min-w-0' }}">
                        <!-- left side: stacked cards (visible when tree or bookmarks should appear) -->
                        @php
                            $showTree      = !config('dam.explorer.enabled') || config('dam.explorer.show_tree');
                            $showBookmarks = config('dam.explorer.bookmarks_enabled');
                            $showSidebar   = $showTree || $showBookmarks;
                        @endphp
                        @if ($showSidebar)
                        @if (config('dam.explorer.enabled'))
                        {{-- Mobile/tablet drawer backdrop (below lg) --}}
                        <div
                            v-show="drawerOpen"
                            @click="drawerOpen = false"
                            class="fixed inset-0 z-[1000] bg-black/40 lg:hidden"
                            aria-hidden="true"
                        ></div>
                        @endif
                        <div
                            class="flex flex-col gap-3 shrink-0 {{ config('dam.explorer.enabled')
                                ? 'lg:static lg:w-[280px] lg:max-w-full lg:translate-x-0 lg:bg-transparent lg:dark:bg-transparent lg:shadow-none lg:p-0 lg:overflow-visible max-lg:fixed max-lg:top-14 max-lg:bottom-0 max-lg:left-0 max-lg:z-[1001] max-lg:w-[280px] max-lg:max-w-[85vw] max-lg:bg-gray-50 dark:max-lg:bg-cherry-900 max-lg:shadow-2xl max-lg:overflow-y-auto max-lg:p-3 transition-transform duration-200'
                                : 'w-[280px] max-w-full max-sm:w-full' }}"
                            @if (config('dam.explorer.enabled'))
                            :class="[ showSidebar ? '' : 'lg:hidden', drawerOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full' ]"
                            @endif
                        >

                            <!-- directories card -->
                            @if ($showTree)
                            <div class="flex flex-col gap-5 p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
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
                                        <p class="text-base text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                                            @lang('dam::app.admin.dam.index.directory.title')
                                        </p>
                                        <x-dam::tree.damdirectories />
                                    </div>
                                @endif
                                {!! view_render_event('dam.admin.main.form.directory.after') !!}
                            </div>
                            @endif

                            <!-- bookmarks card (separate component below directories) -->
                            @if ($showBookmarks)
                            <div class="flex flex-col gap-3 p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                                <p class="text-base text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                                    @lang('dam::app.admin.explorer.bookmarks.title')
                                </p>
                                <div class="dark:bg-cherry-700 border-b dark:border-cherry-800"></div>
                                <x-dam::explorer.bookmarks />
                            </div>
                            @endif

                        </div>
                        @endif

                        {{-- Hidden tree mount: keeps Vue component (and its modal listeners) alive
                             when explorer is enabled but show_tree is off. Modals teleport to body. --}}
                        @if (config('dam.explorer.enabled') && !$showTree && bouncer()->hasPermission('dam.directory.index'))
                        <div class="hidden" aria-hidden="true">
                            <x-dam::tree.damdirectories :visible="false" />
                        </div>
                        @endif

                        <!-- right sub-component -->
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto min-w-0 p-4 bg-white dark:bg-cherry-900 rounded-lg box-shadow">
                            {!! view_render_event('dam.admin.main.form.grid.before') !!}
                            @if (config('dam.explorer.enabled'))
                                <x-dam::explorer.index />
                            @else
                                <v-dam-upload
                                    :acl-bypass="{{ dam_acl_bypass() ? 'true' : 'false' }}"
                                    :accessible-ids='@json(dam_accessible_dir_ids())'
                                ></v-dam-upload>
                            @endif
                            {!! view_render_event('dam.admin.main.form.grid.after') !!}
                        </div>
                    </div>
                {!! view_render_event('dam.admin.main.form.after') !!}
            </div>
        </script>

        <script type="module">
            app.component('v-dam-main', {
                template: '#v-dam-main-template',

                data() {
                    let showSidebar = true;
                    try {
                        const s = localStorage.getItem('dam_show_sidebar');
                        if (s !== null) showSidebar = s !== 'false';
                    } catch {}
                    // drawerOpen drives the off-canvas sidebar below the lg breakpoint;
                    // it is transient (never persisted) and always starts closed.
                    return { showSidebar, drawerOpen: false };
                },

                mounted() {
                    // Open the tree at the requested directory if landed here
                    // from a breadcrumb link on the asset edit page. Fired
                    // immediately — the tree component queues the request if
                    // its directories haven't finished loading yet, and the
                    // silent flag suppresses a flash if the directory turns
                    // out to be missing (e.g. it was deleted while we were
                    // away on the edit page).
                    let dirId = null;

                    // 1. Asset-edit breadcrumb return (highest priority).
                    try { dirId = sessionStorage.getItem('dam_return_dir'); sessionStorage.removeItem('dam_return_dir'); } catch {}

                    // 2. URL param — format is filters[directory_id][]=X (datagrid convention).
                    if (! dirId) {
                        try {
                            const urlDirId = new URLSearchParams(window.location.search).get('filters[directory_id][]');
                            if (urlDirId) dirId = urlDirId;
                        } catch {}
                    }

                    // 3. localStorage — the datagrid persists applied filters under key
                    //    'datagrids' as an array of {src, applied} objects. Find the DAM
                    //    assets datagrid entry and read its directory_id column filter.
                    if (! dirId) {
                        try {
                            const datagrids = JSON.parse(localStorage.getItem('datagrids') || '[]');
                            const damSrc = "{{ route('admin.dam.assets.index') }}";
                            const entry = datagrids.find(d => d.src === damSrc);
                            const col = entry?.applied?.filters?.columns?.find(c => c.index === 'directory_id');
                            if (col?.value?.[0]) dirId = String(col.value[0]);
                        } catch {}
                    }

                    if (dirId) {
                        this.$emitter.emit('dam:reveal-directory', { id: Number(dirId), silent: true });
                    }

                    // Below lg the sidebar is an off-canvas drawer: the toggle button
                    // opens/closes it. At lg+ the same button collapses the static
                    // sidebar (the persisted desktop behavior).
                    this.$emitter.on('dam:toggle-sidebar', () => {
                        if (this.isDesktop()) {
                            this.showSidebar = !this.showSidebar;
                            try { localStorage.setItem('dam_show_sidebar', this.showSidebar); } catch {}
                            this.$emitter.emit('dam:sidebar-visibility-changed', this.showSidebar);
                        } else {
                            this.drawerOpen = !this.drawerOpen;
                        }
                    });

                    // Close the mobile drawer on Escape, on navigating into a folder,
                    // and whenever the viewport grows back to desktop.
                    this._onSidebarKeydown = (e) => { if (e.key === 'Escape') this.drawerOpen = false; };
                    window.addEventListener('keydown', this._onSidebarKeydown);

                    this._onSidebarResize = () => { if (this.isDesktop()) this.drawerOpen = false; };
                    window.addEventListener('resize', this._onSidebarResize);

                    this.$emitter.on('current-directory', () => { if (! this.isDesktop()) this.drawerOpen = false; });
                },

                beforeUnmount() {
                    if (this._onSidebarKeydown) window.removeEventListener('keydown', this._onSidebarKeydown);
                    if (this._onSidebarResize) window.removeEventListener('resize', this._onSidebarResize);
                },

                methods: {
                    isDesktop() {
                        try { return window.matchMedia('(min-width: 1024px)').matches; } catch { return true; }
                    },
                }
            })
        </script>
    @endPushOnce

    <x-dam::asset.drop-upload />

    @pushOnce('scripts')
        {{-- v-dam-upload: upload button + datagrid (drag-drop delegated to v-dam-drop-upload) --}}
        <script
            type="text/x-template"
            id="v-dam-upload-template"
        >
            <div>
                <v-dam-drop-upload
                    :current-directory="currentDirectory"
                    :can-upload="canUploadHere"
                    @refresh-datagrid="$refs.datagrid?.get()"
                >
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
                            class="relative"
                            :class="{ 'pointer-events-none': isUploading || isFolderUploading || dropActiveCount > 0 || treeBusy }"
                            :aria-busy="isUploading || isFolderUploading || dropActiveCount > 0 || treeBusy"
                        >
                            <!-- Semi-transparent overlay while uploading / tree loading.
                                 Uses a child absolute element so the parent stays at z:auto
                                 (no stacking context), keeping the filter drawer's fixed
                                 elements in the root stacking context above the sticky navbar. -->
                            <div
                                v-if="isUploading || isFolderUploading || treeBusy"
                                class="absolute inset-0 bg-white/60 dark:bg-cherry-900/60 z-[1] rounded-lg"
                                aria-hidden="true"
                            ></div>

                            <x-dam::datagrid.dam
                                :src="route('admin.dam.assets.index')"
                                ref="datagrid"
                            />
                        </div>
                    @endif

                    {!! view_render_event('unopim.admin.dam.assets.list.after') !!}
                </v-dam-drop-upload>
            </div>
        </script>

    <script type="module">
        const damUploadFileTooLargeMsg = @js(trans('dam::app.admin.dam.asset.datagrid.file-too-large', ['size' => \Webkul\DAM\Helpers\AssetHelper::humanReadableSize(\Webkul\DAM\Helpers\AssetHelper::getMaxUploadSizeKb())]));
        const damUploadFailedMsg = @js(trans('dam::app.admin.dam.asset.datagrid.files-upload-failed'));
        const damUploadCompleteMsg     = @js(trans('dam::app.admin.dam.index.upload-complete'));
        const damItemUploadCompleteMsg = @js(trans('dam::app.admin.dam.index.item-upload-complete'));
        const damUploadInProgressTitle   = @js(trans('dam::app.admin.dam.index.upload-in-progress-title'));
        const damUploadInProgressMessage = @js(trans('dam::app.admin.dam.index.upload-in-progress-message'));
        const damUploadLeaveBtn          = @js(trans('dam::app.admin.dam.index.upload-leave-page'));
        const damUploadStayBtn           = @js(trans('dam::app.admin.dam.index.upload-stay-page'));

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
                    dropActiveCount: 0,
                    localAccessibleIds: [...(this.accessibleIds || [])],
                }
            },

            computed: {
                canUploadHere() {
                    if (this.aclBypass) return true;
                    if (! this.currentDirectory) return false;

                    return this.localAccessibleIds.map(Number).includes(Number(this.currentDirectory.id));
                },
            },

            mounted() {
                this._navigationConfirmed = false;

                this._beforeUnloadHandler = (e) => {
                    if (this._navigationConfirmed) return;
                    if (this.dropActiveCount > 0 || this.isUploading || this.isFolderUploading) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                };
                window.addEventListener('beforeunload', this._beforeUnloadHandler);

                this._linkClickHandler = (e) => {
                    if (! this.dropActiveCount && ! this.isUploading && ! this.isFolderUploading) return;

                    const anchor = e.target.closest('a[href]');
                    if (! anchor) return;

                    const raw = anchor.getAttribute('href');
                    if (! raw || raw === '#' || raw.startsWith('javascript:')) return;

                    const url = new URL(anchor.href, window.location.href);
                    if (
                        url.origin === window.location.origin
                        && url.pathname === window.location.pathname
                        && url.search === window.location.search
                    ) return;

                    e.preventDefault();
                    e.stopPropagation();

                    const target = anchor.href;
                    this.$emitter.emit('open-confirm-modal', {
                        title: damUploadInProgressTitle,
                        message: damUploadInProgressMessage,
                        options: {
                            btnDisagree: damUploadStayBtn,
                            btnAgree: damUploadLeaveBtn,
                            btnAgreeClass: 'danger-button',
                            btnDisagreeClass: 'transparent-button',
                        },
                        agree: () => {
                            this._navigationConfirmed = true;
                            window.location.href = target;
                        },
                        disagree: () => {},
                    });
                };
                document.addEventListener('click', this._linkClickHandler, true);

                this.$emitter.on('current-directory', (data) => {
                    this.currentDirectory = data;
                });

                this.$emitter.on('dam:tree-busy', (busy) => {
                    this.treeBusy = !! busy;
                });

                this.$emitter.on('dam:directory-granted', (id) => {
                    const numId = Number(id);
                    if (! this.localAccessibleIds.map(Number).includes(numId)) {
                        this.localAccessibleIds.push(numId);
                    }
                });

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

                this.$emitter.on('dam:drop-upload-active', (count) => {
                    this.dropActiveCount = count;
                });
            },

            watch: {
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
                        if (typeof response.data !== 'object' || response.data === null) {
                            this.$emitter.emit('add-flash', { type: 'error', message: damUploadFileTooLargeMsg });
                            return;
                        }
                        if (response.data.success === false) {
                            this.$emitter.emit('add-flash', { type: 'error', message: response.data.message ?? damUploadFailedMsg });
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
            },

            beforeUnmount() {
                window.removeEventListener('beforeunload', this._beforeUnloadHandler);
                document.removeEventListener('click', this._linkClickHandler, true);
            },
        })
    </script>
    @endPushOnce

    {{-- Directory breadcrumb shown at the top of the asset grid --}}
    @pushOnce('scripts')
        <script type="text/x-template" id="v-dam-breadcrumb-template">
            <nav class="flex items-center gap-1 flex-wrap text-sm" aria-label="Directory breadcrumb">
                <template v-if="loading">
                    <div class="shimmer h-4 w-10 rounded"></div>
                    <span class="text-gray-400 dark:text-gray-500">/</span>
                    <div class="shimmer h-4 w-24 rounded"></div>
                    <span class="text-gray-400 dark:text-gray-500">/</span>
                    <div class="shimmer h-4 w-16 rounded"></div>
                </template>
                <template v-else>
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
                </template>
            </nav>
        </script>

        <script type="module">
            app.component('v-dam-breadcrumb', {
                template: '#v-dam-breadcrumb-template',
                data() {
                    return { crumbs: [], loading: true };
                },
                mounted() {
                    this._onBreadcrumb = (crumbs) => {
                        this.loading = false;
                        this.crumbs = Array.isArray(crumbs) ? crumbs : [];
                    };
                    this.$emitter.on('current-directory-breadcrumb', this._onBreadcrumb);
                },
                beforeUnmount() {
                    if (this._onBreadcrumb) this.$emitter.off('current-directory-breadcrumb', this._onBreadcrumb);
                },
                methods: {
                    navigateTo(crumb) {
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

    {{-- Shared "Assign Tags" mass-action modal — used by both the legacy datagrid and the explorer --}}
    <x-dam::tag.assign-modal />
</x-admin::layouts>