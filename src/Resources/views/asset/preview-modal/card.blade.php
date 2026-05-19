<!-- Top-right action icons -->
<div class="flex justify-end items-center gap-1 w-full">

    <!-- Info — hover tooltip + click modal -->
    <div class="relative" @mouseenter="infoHover = true" @mouseleave="infoHover = false">
        <button
            type="button"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            @click="isInfoOpen = true"
        >
            <span class="text-lg icon-information"></span>
        </button>
        <!-- Hover tooltip -->
        <div
            v-show="infoHover"
            class="absolute right-0 top-10 w-56 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg py-2 px-3 flex flex-col gap-1.5 text-xs pointer-events-none"
        >
            <p class="font-semibold text-gray-700 dark:text-gray-200 truncate">{{ $asset->file_name }}</p>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-1.5 py-0.5 rounded font-semibold {{ $typeColor }}">{{ strtoupper($asset->extension) }}</span>
                @if ($fileSize)<span class="text-gray-400 dark:text-gray-500">{{ $fileSize }}</span>@endif
                @if ($asset->file_type === 'image' && !empty($asset->width) && !empty($asset->height))<span class="text-gray-400 dark:text-gray-500">{{ $asset->width }}×{{ $asset->height }}px</span>@endif
            </div>
            <p class="text-gray-400 dark:text-gray-500 text-[11px]">{{ trans('dam::app.admin.dam.asset.edit.preview-modal.card.click-for-details') }}</p>
        </div>
    </div>

    <!-- Preview -->
    <button
        type="button"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
        title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.card.preview') }}"
        @click="openPreview"
    >
        <span class="text-lg icon-dam-preview"></span>
    </button>

    @if ($asset->file_type === 'image')
    <!-- Image editor -->
    <button
        type="button"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
        title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.card.edit-image') }}"
        @click="isEditOpen = true"
    >
        <span class="text-lg icon-edit"></span>
    </button>
    @endif
</div>

<!-- Thumbnail / placeholder -->
<div class="flex items-center justify-center w-full max-h-[300px] rounded-lg overflow-hidden">
    @if ($asset->file_type === 'image')
        <img
            src="{{ $asset->previewPath }}"
            alt="{{ $asset->file_name }}"
            class="max-h-full max-w-full object-contain"
        />
    @elseif ($coverArtUrl)
        <img
            src="{{ $coverArtUrl }}"
            alt="{{ $asset->file_name }}"
            class="max-h-full max-w-full object-cover rounded-lg"
        />
    @else
        <img
            src="{{ $placeholderSvg }}"
            alt="{{ $asset->file_name }}"
            class="max-h-full max-w-full object-contain opacity-60"
        />
    @endif
</div>
