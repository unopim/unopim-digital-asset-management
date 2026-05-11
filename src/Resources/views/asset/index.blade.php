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
            <div>
                <div class="flex justify-between items-center w-full">
                    <p
                        class="text-base text-gray-600 dark:text-gray-300 font-bold"
                        v-if="currentDirectory"
                    >
                        @{{currentDirectory.name}}
                    </p>
                    <p
                        class="text-base text-gray-600 dark:text-gray-300 font-bold"
                        v-else
                    >
                        @lang('dam::app.admin.dam.index.root')
                    </p>
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
                                :class="{ 'opacity-60 pointer-events-none cursor-not-allowed': isUploading || treeBusy }"
                                :aria-disabled="isUploading || treeBusy"
                            >
                                <svg
                                    v-if="isUploading"
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
                                <span v-if="isUploading">@lang('dam::app.admin.dam.index.uploading')</span>
                                <span v-else>@lang('dam::app.admin.dam.index.upload')</span>
                            </label>

                            <button
                                v-if="isUploading"
                                type="button"
                                class="secondary-button"
                                @click="cancelUpload"
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
            </div>
    
        </script>
    <script type="module">
        const damUploadFileTooLargeMsg = @js(trans('dam::app.admin.dam.asset.datagrid.file-too-large', ['size' => \Webkul\DAM\Helpers\AssetHelper::humanReadableSize(\Webkul\DAM\Helpers\AssetHelper::getMaxUploadSizeKb())]));
        const damUploadFailedMsg = @js(trans('dam::app.admin.dam.asset.datagrid.files-upload-failed'));

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
                    abortController: null,
                    treeBusy: false,
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
                }
            }
        })
    </script>
    @endPushOnce
</x-admin::layouts>