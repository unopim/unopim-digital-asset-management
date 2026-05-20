<!-- Fullscreen Preview Modal -->
<div
    v-if="isOpen"
    class="fixed inset-0 z-[10010] flex items-center justify-center"
>
    <!-- Backdrop -->
    <div
        class="absolute inset-0 bg-black/75"
        @click="closePreview"
    ></div>

    <template v-if="previewData.file_type === 'video' || previewData.extension === 'pdf' || previewData.file_type === 'image'">
        <!-- Large modal — video / pdf / image -->
        <div class="relative z-10 flex flex-col w-[85vw] h-[88vh] max-w-6xl rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10">

            <!-- Header -->
            <div class="flex items-center gap-3 px-5 py-3 shrink-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <span class="shrink-0 px-2 py-0.5 rounded text-xs font-semibold" :class="previewData.typeColor">
                    @{{ previewData.extension_upper }}
                </span>
                <p class="flex-1 text-sm font-semibold text-gray-800 dark:text-white truncate">
                    @{{ previewData.file_name }}
                </p>
                <span v-if="previewData.fileSize" class="shrink-0 text-xs text-gray-400 dark:text-gray-500 hidden sm:block">@{{ previewData.fileSize }}</span>
                <button
                    type="button"
                    class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                    @click="closePreview"
                    aria-label="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.close') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 min-h-0 overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-900">

                <template v-if="previewData.file_type === 'video'">
                    @include('dam::asset.preview-modal.video.video-player')
                </template>
                <template v-else-if="previewData.extension === 'pdf'">
                    @include('dam::asset.preview-modal.files.pdf-viewer')
                </template>
                <template v-else>
                    @include('dam::asset.preview-modal.image.image-viewer')
                </template>

            </div>
        </div>
    </template>
    <template v-else>
        <!-- Compact modal — audio / fallback -->
        <div class="relative z-10 w-full max-w-lg mx-4 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10">

            <!-- Header -->
            <div class="flex items-center gap-3 px-5 py-3 shrink-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                <span class="shrink-0 px-2 py-0.5 rounded text-xs font-semibold" :class="previewData.typeColor">
                    @{{ previewData.extension_upper }}
                </span>
                <p class="flex-1 text-sm font-semibold text-gray-800 dark:text-white truncate">
                    @{{ previewData.file_name }}
                </p>
                <span v-if="previewData.fileSize" class="shrink-0 text-xs text-gray-400 dark:text-gray-500 hidden sm:block">@{{ previewData.fileSize }}</span>
                <button
                    type="button"
                    class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                    @click="closePreview"
                    aria-label="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.close') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <template v-if="previewData.file_type === 'audio'">
                @include('dam::asset.preview-modal.audio.audio-player')
            </template>
            <template v-else>
                @include('dam::asset.preview-modal.fallback')
            </template>
        </div>
    </template>
</div>
