<v-dam-asset-history-modal ref="historyModal"></v-dam-asset-history-modal>
<v-dam-hist-image-preview ref="histImagePreview"></v-dam-hist-image-preview>

@pushOnce('scripts')
    <style>
        .icon-dam-restore {
            font-family: unset !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }
        .icon-dam-restore::before {
            content: '' !important;
            display: inline-block;
            width: 0.8em;
            height: 0.8em;
            background-color: currentColor;
            -webkit-mask: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'><path d='M3 12a9 9 0 1 0 2.64-6.36L3 8'/><polyline points='3 3 3 8 8 8'/></svg>") no-repeat center / contain;
            mask: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'><path d='M3 12a9 9 0 1 0 2.64-6.36L3 8'/><polyline points='3 3 3 8 8 8'/></svg>") no-repeat center / contain;
        }
        .dam-hist-thumb {
            position: relative;
            display: inline-flex;
            width: 96px;
            height: 96px;
            border-radius: 8px;
            overflow: hidden;
            vertical-align: middle;
        }
        .dam-hist-thumb .dam-hist-img,
        .dam-hist-thumb .dam-hist-fileicon {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 8px;
            background: #f3f4f6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }
        .dam-hist-thumb .dam-hist-overlay {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(0, 0, 0, 0.55);
            border-radius: 8px;
        }
        .dam-hist-thumb:hover .dam-hist-overlay {
            display: flex;
        }
        .dam-hist-thumb .dam-hist-overlay button {
            background: rgba(255, 255, 255, 0.15);
            border: 0;
            color: #fff;
            cursor: pointer;
            padding: 6px;
            border-radius: 999px;
            line-height: 0;
            transition: background 0.15s;
        }
        .dam-hist-thumb .dam-hist-overlay button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .dam-hist-thumb .dam-hist-overlay button svg {
            display: block;
        }
        .dam-hist-preview-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(15, 18, 30, 0.92);
            display: flex;
            flex-direction: column;
        }
        .dam-hist-preview-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.4);
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .dam-hist-preview-header .dam-hist-preview-title {
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dam-hist-preview-close {
            background: transparent;
            border: 0;
            color: #fff;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .dam-hist-preview-close:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        .dam-hist-preview-stage {
            flex: 1;
            min-height: 0;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }
        .dam-hist-preview-stage img {
            max-width: 100%;
            max-height: 100%;
            pointer-events: none;
            display: block;
            transition: transform 0.15s ease;
        }
        .dam-hist-preview-stage.is-dragging img {
            transition: none;
        }
        .dam-hist-preview-stage.is-dragging {
            cursor: grabbing;
        }
        .dam-hist-preview-stage.can-pan {
            cursor: grab;
        }
        .dam-hist-preview-toolbar {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            background: rgba(0, 0, 0, 0.65);
            color: #fff;
            border-radius: 999px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            font-size: 12px;
        }
        .dam-hist-preview-toolbar button {
            background: transparent;
            border: 0;
            color: #fff;
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .dam-hist-preview-toolbar button:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .dam-hist-preview-toolbar .dam-hist-preview-divider {
            width: 1px;
            height: 16px;
            background: rgba(255, 255, 255, 0.3);
            margin: 0 4px;
        }
        .dam-hist-preview-toolbar .dam-hist-preview-zoom-label {
            min-width: 44px;
            text-align: center;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
    </style>

    <script type="text/x-template" id="v-dam-asset-history-modal-template">
        <div>
            <transition
                tag="div"
                name="modal-overlay"
                enter-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity z-[10002]"
                    v-show="isOpen"
                ></div>
            </transition>

            <transition
                tag="div"
                name="modal-content"
                enter-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-x-full"
                enter-to-class="opacity-100 translate-x-0"
                leave-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-full"
            >
                <div
                    class="fixed inset-0 z-[10002] transform left-20 right-20 top-24 bottom-4"
                    v-if="isOpen"
                >
                    <div class="fixed inset-0 z-[9999] flex items-center justify-center outline-none">
                        <div class="w-full max-w-[568px] z-[999] absolute ltr:left-1/2 rtl:right-1/2 top-1/2 rounded-lg bg-white dark:bg-gray-900 box-shadow max-md:w-[90%] ltr:-translate-x-1/2 rtl:translate-x-1/2 -translate-y-1/2">
                            <div class="flex justify-between items-center p-4 border-b dark:border-cherry-800 text-lg text-gray-800 dark:text-white font-bold">
                                <div>
                                    <h2 class="text-xl">@{{ title }}</h2>
                                    <p class="text-sm font-normal">@{{ subtitle }}</p>
                                </div>

                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="icon-cancel text-3xl cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 hover:rounded-md"
                                ></button>
                            </div>

                            <div class="px-4 pt-4 pb-0">
                                <div class="p-4 text-gray-600 dark:text-gray-300">
                                    <div class="flex gap-2.5">
                                        <span class="font-bold">@{{ versionLabel }} : </span>
                                        <span>@{{ version }}</span>
                                    </div>

                                    <div class="flex gap-2.5">
                                        <span class="font-bold">@{{ dateTimeLabel }} : </span>
                                        <span>@{{ dateTime }}</span>
                                    </div>

                                    <div class="flex gap-2.5">
                                        <span class="font-bold">@{{ userLabel }} : </span>
                                        <span>@{{ user }}</span>
                                    </div>
                                </div>

                                <div class="p-4 overflow-y-auto max-h-[50vh]">
                                    <div class="w-full bg-white dark:bg-cherry-800 dark:text-white rounded-lg overflow-hidden shadow-md">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="bg-gray-100 dark:bg-cherry-800">
                                                    <th class="py-2 px-4 text-left">
                                                        <span>@{{ nameLabel }}</span>
                                                    </th>
                                                    <th class="py-2 px-4 text-left">
                                                        <span class="text-red-500">@{{ oldValueLabel }}</span>
                                                    </th>
                                                    <th class="py-2 px-4 text-left">
                                                        <span class="text-violet-700">@{{ newValueLabel }}</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template v-if="versionHistory.length === 0">
                                                    <tr>
                                                        <td colspan="3">
                                                            <div class="flex items-center justify-center h-32">
                                                                <span class="text-gray-400 text-2xl">
                                                                    @lang('admin::app.components.modal.history.no-history')
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <template v-else-if="versionHistory">
                                                    <tr v-for="history in versionHistory" :key="history.id" class="border-t dark:border-gray-800">
                                                        <td class="py-2 px-4">@{{ history.name }}</td>
                                                        <td class="py-2 px-4 text-red-500 word-break" v-html="history.old"></td>
                                                        <td class="py-2 px-4 text-violet-700 word-break" v-html="history.new"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="canShowRestore"
                                class="flex justify-end gap-2.5 px-4 py-3 border-t dark:border-cherry-800"
                            >
                                <button
                                    type="button"
                                    class="primary-button inline-flex items-center gap-2"
                                    :disabled="isRestoring"
                                    @click="onRestoreClick"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                    @{{ restoreButtonLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </script>

    <script type="module">
        app.component('v-dam-asset-history-modal', {
            template: '#v-dam-asset-history-modal-template',

            data() {
                return {
                    isOpen: false,
                    title: '',
                    subtitle: '',
                    closeBtn: '',
                    versionLabel: '',
                    dateTimeLabel: '',
                    userLabel: '',
                    nameLabel: '',
                    oldValueLabel: '',
                    newValueLabel: '',
                    closeModalCallback: null,
                    version: '',
                    dateTime: '',
                    user: '',
                    versionHistory: [],
                    url: '',
                    cancelLabel: "{{ trans('dam::app.history.restore-disagree') }}",
                    restoreLabel: "{{ trans('dam::app.history.restore') }}",
                    confirmTitle: "{{ trans('dam::app.history.restore-confirm-title') }}",
                    confirmMessage: "{{ trans('dam::app.history.restore-confirm') }}",
                    agreeLabel: "{{ trans('dam::app.history.restore-agree') }}",
                    successFallback: "{{ trans('dam::app.history.restore-success') }}",
                    errorFallback: "{{ trans('dam::app.history.no-backup') }}",
                    restoreBase: "{{ url(config('app.admin_url').'/dam/history/restore') }}",
                    isRestoring: false,
                };
            },

            computed: {
                canShowRestore() {
                    if (! this.version) return false;
                    const rows = this.versionHistory && typeof this.versionHistory === 'object'
                        ? Object.values(this.versionHistory)
                        : [];
                    if (rows.length === 0) return false;

                    // Hide restore for the very first version (no prior data to revert to).
                    return rows.some((row) => {
                        if (! row) return false;
                        const oldVal = row.old;
                        if (oldVal === null || oldVal === undefined) return false;
                        if (typeof oldVal === 'string') return oldVal.trim() !== '';
                        return true;
                    });
                },
                restoreButtonLabel() {
                    return this.restoreLabel;
                },
                assetIdFromUrl() {
                    const match = window.location.pathname.match(/\/dam\/assets\/edit\/(\d+)/);
                    return match ? match[1] : null;
                },
            },

            created() {
                this.registerGlobalEvents();
            },

            mounted() {
                this.$nextTick(() => this.attachThumbHandlers());
            },

            updated() {
                this.$nextTick(() => this.attachThumbHandlers());
            },

            watch: {
                isOpen(newValue) {
                    if (newValue === true) {
                        this.fetchData();
                    }
                }
            },

            methods: {
                open({
                    title = "@lang('admin::app.components.modal.history.title')",
                    subtitle = "@lang('admin::app.components.modal.history.subtitle')",
                    closeBtn = "@lang('admin::app.components.modal.history.close-btn')",
                    versionLabel = "@lang('admin::app.components.modal.history.version-label')",
                    dateTimeLabel = "@lang('admin::app.components.modal.history.date-time-label')",
                    userLabel = "@lang('admin::app.components.modal.history.user-label')",
                    nameLabel = "@lang('admin::app.components.modal.history.name-label')",
                    oldValueLabel = "@lang('admin::app.components.modal.history.old-value-label')",
                    newValueLabel = "@lang('admin::app.components.modal.history.new-value-label')",
                    url = '',
                    closeModal = () => {},
                }) {
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                    this.title = title;
                    this.subtitle = subtitle;
                    this.closeBtn = closeBtn;
                    this.versionLabel = versionLabel;
                    this.dateTimeLabel = dateTimeLabel;
                    this.userLabel = userLabel;
                    this.nameLabel = nameLabel;
                    this.oldValueLabel = oldValueLabel;
                    this.newValueLabel = newValueLabel;
                    this.closeModalCallback = closeModal;
                    this.url = url;
                },

                closeModal() {
                    this.isOpen = false;
                    document.body.style.overflow = 'auto';
                    if (typeof this.closeModalCallback === 'function') {
                        this.closeModalCallback();
                    }
                },

                registerGlobalEvents() {
                    this.$emitter.on('open-v-confirm-modal', (data) => {
                        this.open(data);
                    });
                },

                fetchData() {
                    this.$axios.get(this.url)
                        .then(response => {
                            this.version = response.data?.version;
                            this.dateTime = response.data?.dateTime;
                            this.user = response.data?.user;
                            this.versionHistory = response.data?.versionHistory;
                        })
                        .catch(error => {
                            console.error(error);
                        });
                },

                onRestoreClick() {
                    if (this.isRestoring || !this.assetIdFromUrl) return;
                    const assetId = this.assetIdFromUrl;
                    const versionId = this.version;

                    this.$emitter.emit('open-confirm-modal', {
                        title: this.confirmTitle,
                        message: this.confirmMessage,
                        options: {
                            btnDisagree: this.cancelLabel,
                            btnAgree: this.agreeLabel,
                            btnAgreeClass: 'primary-button',
                            btnDisagreeClass: 'transparent-button',
                        },
                        agree: () => this.performRestore(assetId, versionId),
                        disagree: () => {},
                    });
                },

                performRestore(assetId, versionId) {
                    this.isRestoring = true;
                    this.$axios.post(this.restoreBase + '/' + assetId, { version_id: versionId })
                        .then((resp) => {
                            const ok = resp.data && resp.data.status === 'success';
                            this.$emitter.emit('add-flash', {
                                type:    ok ? 'success' : 'error',
                                message: (resp.data && resp.data.message) || (ok ? this.successFallback : this.errorFallback),
                            });
                            if (ok) {
                                setTimeout(() => window.location.reload(), 600);
                            } else {
                                this.isRestoring = false;
                            }
                        })
                        .catch((e) => {
                            const msg = (e.response && e.response.data && e.response.data.message)
                                ? e.response.data.message
                                : this.errorFallback;
                            this.$emitter.emit('add-flash', { type: 'error', message: msg });
                            this.isRestoring = false;
                        });
                },

                attachThumbHandlers() {
                    if (window.__damHistoryThumbInit) return;
                    window.__damHistoryThumbInit = true;

                    const self = this;

                    const previewBase = "{{ route('admin.dam.file.preview') }}";
                    const fetchBase = "{{ url(config('app.admin_url').'/dam/file/fetch') }}";

                    const buildSources = (path, mime) => {
                        const sep = previewBase.includes('?') ? '&' : '?';
                        const imageSrc = previewBase + sep + 'path=' + encodeURIComponent(path) + '&size=1356';
                        const rawSrc = fetchBase + '/' + path.split('/').map(encodeURIComponent).join('/');

                        let kind = 'image';
                        if (mime && mime.startsWith('video/'))      kind = 'video';
                        else if (mime === 'application/pdf')        kind = 'pdf';
                        else if (mime && mime.startsWith('audio/')) kind = 'audio';
                        else if (mime && mime.startsWith('image/')) kind = 'image';
                        else {
                            const ext = (path.split('.').pop() || '').toLowerCase();
                            if (['mp4', 'webm', 'mov', 'ogv'].includes(ext)) kind = 'video';
                            else if (ext === 'pdf')                          kind = 'pdf';
                            else if (['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) kind = 'audio';
                        }

                        return { kind, src: kind === 'image' ? imageSrc : rawSrc, mime: mime || '', name: path.split('/').pop() };
                    };

                    const openEye = (path, mime) => {
                        self.$emitter.emit('dam-hist-image-preview-open', buildSources(path, mime));
                    };

                    document.addEventListener('click', (e) => {
                        const target = e.target;
                        if (!target || !target.closest) return;

                        const thumb = target.closest('.dam-hist-thumb');
                        if (!thumb) return;

                        const eyeBtn = target.closest('.dam-hist-eye');
                        if (eyeBtn) {
                            e.preventDefault();
                            const path = thumb.getAttribute('data-path');
                            const mime = thumb.getAttribute('data-mime') || '';
                            if (path) openEye(path, mime);
                        }
                    }, false);
                },
            }
        });
    </script>

    <script type="text/x-template" id="v-dam-hist-image-preview-template">
        <div v-if="isOpen" class="dam-hist-preview-overlay" @keydown.esc="close" tabindex="-1">
            <div class="dam-hist-preview-header">
                <span class="dam-hist-preview-title">@{{ name }}</span>
                <a
                    :href="src"
                    target="_blank"
                    rel="noopener"
                    class="dam-hist-preview-close"
                    :title="openInNewTabLabel"
                    style="margin-right: 4px;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <button type="button" class="dam-hist-preview-close" @click="close" :title="closeLabel" aria-label="close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- IMAGE -->
            <div
                v-if="kind === 'image'"
                class="dam-hist-preview-stage"
                :class="{ 'is-dragging': isDragging, 'can-pan': zoom > 1 && !isDragging }"
                @wheel.prevent="onWheel"
                @mousedown="onMouseDown"
            >
                <img
                    :src="src"
                    :alt="name"
                    :style="{ transform: transformStyle, transformOrigin: 'center center' }"
                    draggable="false"
                />

                <div class="dam-hist-preview-toolbar">
                    <button type="button" @click="rotateLeft" :title="t.rotateLeft">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                    <button type="button" @click="rotateRight" :title="t.rotateRight">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    </button>
                    <span class="dam-hist-preview-divider"></span>
                    <button type="button" @click="zoomOut" :title="t.zoomOut">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>
                    <span class="dam-hist-preview-zoom-label">@{{ zoomPercent }}%</span>
                    <button type="button" @click="zoomIn" :title="t.zoomIn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>
                    <span class="dam-hist-preview-divider"></span>
                    <button type="button" @click="fitToScreen" :title="t.fitToScreen">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    </button>
                    <button type="button" @click="actualSize" :title="t.actualSize" style="font-weight: 700; font-size: 11px;">1:1</button>
                    <button type="button" @click="reset" :title="t.reset">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                    </button>
                </div>
            </div>

            <!-- VIDEO -->
            <div v-else-if="kind === 'video'" class="dam-hist-preview-stage">
                <video
                    :src="src"
                    controls
                    autoplay
                    controlsList="nodownload"
                    style="max-width: 100%; max-height: 100%; background: #000;"
                ></video>
            </div>

            <!-- PDF -->
            <div v-else-if="kind === 'pdf'" class="dam-hist-preview-stage" style="background: #fff;">
                <iframe
                    :src="src"
                    style="width: 100%; height: 100%; border: 0; background: #fff;"
                    :title="name"
                ></iframe>
            </div>

            <!-- AUDIO -->
            <div v-else-if="kind === 'audio'" class="dam-hist-preview-stage" style="flex-direction: column; gap: 16px;">
                <div style="color: #fff; opacity: 0.7; font-size: 14px;">@{{ name }}</div>
                <audio :src="src" controls autoplay style="width: min(480px, 90%);"></audio>
            </div>

            <!-- FALLBACK -->
            <div v-else class="dam-hist-preview-stage" style="flex-direction: column; gap: 12px; color: #fff;">
                <div>@{{ name }}</div>
                <a :href="src" target="_blank" rel="noopener" class="primary-button">@{{ openInNewTabLabel }}</a>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-dam-hist-image-preview', {
            template: '#v-dam-hist-image-preview-template',

            data() {
                return {
                    isOpen: false,
                    src: '',
                    name: '',
                    kind: 'image',
                    mime: '',
                    zoom: 1,
                    rotation: 0,
                    panX: 0,
                    panY: 0,
                    isDragging: false,
                    dragStartX: 0,
                    dragStartY: 0,
                    panStartX: 0,
                    panStartY: 0,
                    closeLabel: "{{ trans('dam::app.history.preview-close') }}",
                    openInNewTabLabel: "{{ trans('dam::app.history.preview-open-new-tab') }}",
                    t: {
                        rotateLeft:  "{{ trans('dam::app.history.preview-rotate-left') }}",
                        rotateRight: "{{ trans('dam::app.history.preview-rotate-right') }}",
                        zoomIn:      "{{ trans('dam::app.history.preview-zoom-in') }}",
                        zoomOut:     "{{ trans('dam::app.history.preview-zoom-out') }}",
                        fitToScreen: "{{ trans('dam::app.history.preview-fit') }}",
                        actualSize:  "{{ trans('dam::app.history.preview-actual') }}",
                        reset:       "{{ trans('dam::app.history.preview-reset') }}",
                    },
                };
            },

            computed: {
                transformStyle() {
                    return `translate(${this.panX}px, ${this.panY}px) scale(${this.zoom}) rotate(${this.rotation}deg)`;
                },
                zoomPercent() {
                    return Math.round(this.zoom * 100);
                },
            },

            created() {
                this.$emitter.on('dam-hist-image-preview-open', this.open);
            },

            mounted() {
                window.addEventListener('mousemove', this.onMouseMove);
                window.addEventListener('mouseup', this.onMouseUp);
                window.addEventListener('keydown', this.onKeyDown);
            },

            beforeUnmount() {
                window.removeEventListener('mousemove', this.onMouseMove);
                window.removeEventListener('mouseup', this.onMouseUp);
                window.removeEventListener('keydown', this.onKeyDown);
            },

            methods: {
                open({ src, name, kind, mime }) {
                    this.src = src;
                    this.name = name || '';
                    this.kind = kind || 'image';
                    this.mime = mime || '';
                    this.resetState();
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                },
                close() {
                    this.isOpen = false;
                    document.body.style.overflow = 'auto';
                    this.resetState();
                },
                resetState() {
                    this.zoom = 1;
                    this.rotation = 0;
                    this.panX = 0;
                    this.panY = 0;
                    this.isDragging = false;
                },
                zoomIn() {
                    this.zoom = Math.min(10, parseFloat((this.zoom + 0.25).toFixed(2)));
                },
                zoomOut() {
                    this.zoom = Math.max(0.1, parseFloat((this.zoom - 0.25).toFixed(2)));
                },
                rotateLeft() {
                    this.rotation = (this.rotation - 90 + 360) % 360;
                },
                rotateRight() {
                    this.rotation = (this.rotation + 90) % 360;
                },
                fitToScreen() {
                    this.zoom = 1;
                    this.panX = 0;
                    this.panY = 0;
                },
                actualSize() {
                    this.zoom = 1;
                    this.panX = 0;
                    this.panY = 0;
                },
                reset() {
                    this.resetState();
                },
                onWheel(e) {
                    const factor = e.deltaY < 0 ? 1.1 : 0.9;
                    this.zoom = Math.min(10, Math.max(0.1, parseFloat((this.zoom * factor).toFixed(3))));
                },
                onMouseDown(e) {
                    if (e.button !== 0) return;
                    if (this.zoom <= 1) return;
                    this.isDragging = true;
                    this.dragStartX = e.clientX;
                    this.dragStartY = e.clientY;
                    this.panStartX = this.panX;
                    this.panStartY = this.panY;
                    e.preventDefault();
                },
                onMouseMove(e) {
                    if (!this.isDragging) return;
                    this.panX = this.panStartX + (e.clientX - this.dragStartX);
                    this.panY = this.panStartY + (e.clientY - this.dragStartY);
                },
                onMouseUp() {
                    this.isDragging = false;
                },
                onKeyDown(e) {
                    if (!this.isOpen) return;
                    if (e.key === 'Escape') { this.close(); return; }
                    if (e.key === '+' || e.key === '=') { this.zoomIn(); return; }
                    if (e.key === '-' || e.key === '_') { this.zoomOut(); return; }
                    if (e.key === '0') { this.reset(); return; }
                },
            },
        });
    </script>
@endPushOnce
