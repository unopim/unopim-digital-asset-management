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
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate text-right">{{ $asset->file_name }}</span>
            </div>
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.type')</span>
                <span class="text-xs px-1.5 py-0.5 rounded font-semibold {{ $typeColor }}">{{ strtoupper($asset->extension) }}</span>
            </div>
            @if ($fileSize)
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.size')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $fileSize }}</span>
            </div>
            @endif
            @if ($asset->file_type === 'image' && !empty($asset->width) && !empty($asset->height))
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.dimensions')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $asset->width }} × {{ $asset->height }}px</span>
            </div>
            @endif
            @if (!empty($asset->path))
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.path')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate text-right">{{ $asset->path }}</span>
            </div>
            @endif
            @if (!empty($asset->mime_type))
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">{{ trans('dam::app.admin.dam.asset.edit.preview-modal.mime') }}</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $asset->mime_type }}</span>
            </div>
            @endif
            @if (!empty($asset->created_at))
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.created-at')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $asset->created_at->format('d M Y, H:i') }}</span>
            </div>
            @endif
            @if (!empty($asset->updated_at))
            <div class="flex items-center justify-between py-3 gap-4">
                <span class="text-xs text-gray-700 dark:text-gray-200 shrink-0">@lang('dam::app.admin.dam.asset.edit.updated-at')</span>
                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $asset->updated_at->format('d M Y, H:i') }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
