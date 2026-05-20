<!-- Info Modal -->
<div
    v-if="isInfoOpen"
    class="fixed inset-0 z-[10010] flex items-center justify-center"
    @click.self="isInfoOpen = false"
>
    <div class="absolute inset-0 bg-black/60" @click="isInfoOpen = false"></div>
    <div class="relative z-10 w-96 mx-4 rounded-xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <span class="icon-information text-xl text-violet-600 dark:text-violet-400"></span>
            <p class="flex-1 text-sm font-semibold text-gray-800 dark:text-white">@lang('dam::app.admin.dam.asset.edit.file-info')</p>
            <button
                type="button"
                class="flex items-center justify-center w-7 h-7 rounded-full text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                @click="isInfoOpen = false"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <!-- Rows -->
        <div class="flex flex-col divide-y divide-gray-50 dark:divide-gray-800 px-5">
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.file-name')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate text-right">@{{ previewData.file_name }}</span>
            </div>
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.type')</span>
                <span class="text-xs px-1.5 py-0.5 rounded font-semibold" :class="previewData.typeColor">@{{ previewData.extension_upper }}</span>
            </div>
            <div v-if="previewData.fileSize" class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.size')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">@{{ previewData.fileSize }}</span>
            </div>
            <div v-if="previewData.file_type === 'image' && previewData.width && previewData.height" class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.dimensions')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">@{{ previewData.width }} × @{{ previewData.height }}px</span>
            </div>
            <div v-if="previewData.path" class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.path')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate text-right">@{{ previewData.path }}</span>
            </div>
            <div v-if="previewData.mime_type" class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">{{ trans('dam::app.admin.dam.asset.edit.preview-modal.mime') }}</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">@{{ previewData.mime_type }}</span>
            </div>
            <div v-if="previewData.created_at" class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.created-at')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">@{{ previewData.created_at }}</span>
            </div>
            <div v-if="previewData.updated_at" class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.updated-at')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">@{{ previewData.updated_at }}</span>
            </div>
        </div>
    </div>
</div>
