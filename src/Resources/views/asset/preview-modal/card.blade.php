@php
    $crumbParts = $directoryAncestors->pluck('name')->toArray();
@endphp

<!-- Breadcrumb (left) + action icons (right) -->
<div class="flex justify-between items-center gap-2 w-full px-2 py-1 border-b border-gray-100 dark:border-gray-700">

    <nav class="flex items-center gap-1 flex-wrap text-sm min-w-0" aria-label="@lang('dam::app.admin.dam.asset.edit.directory-path')">
        @foreach ($crumbParts as $i => $name)
            @if ($i > 0)
                <span class="text-gray-400 dark:text-gray-500">/</span>
            @endif
            <a
                href="{{ route('admin.dam.assets.index').'?directory_id='.($directoryAncestors[$i]->id ?? '') }}"
                class="px-1 py-0.5 rounded text-gray-600 dark:text-gray-300 hover:text-violet-700 dark:hover:text-violet-400 hover:underline"
            >{{ $name }}</a>
        @endforeach
        @if (count($crumbParts) > 0)
            <span class="text-gray-400 dark:text-gray-500">/</span>
        @endif
        <span class="px-1 py-0.5 rounded text-violet-700 dark:text-violet-300 font-semibold truncate max-w-xs">{{ $asset->file_name }}</span>
    </nav>

    <div class="flex items-center gap-1 shrink-0">

    <!-- Info — hover tooltip + click modal -->
    <div class="relative" @mouseenter="infoHover = true" @mouseleave="infoHover = false">
        <button
            type="button"
            class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-400 dark:text-gray-50 hover:bg-violet-50 dark:hover:bg-cherry-800 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
            @click="isInfoOpen = true"
        >
            <span class="text-lg icon-information"></span>
        </button>
        <!-- Hover tooltip -->
        <div
            v-show="infoHover"
            class="absolute right-0 top-10 w-56 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg py-2 px-3 flex flex-col gap-1.5 text-xs pointer-events-none z-20"
        >
            <p class="font-semibold text-gray-700 dark:text-gray-200 truncate">@{{ previewData.file_name }}</p>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-1.5 py-0.5 rounded font-semibold" :class="previewData.typeColor">@{{ previewData.extension_upper }}</span>
                <span v-if="previewData.fileSize" class="text-gray-400 dark:text-gray-500">@{{ previewData.fileSize }}</span>
                <span v-if="previewData.file_type === 'image' && previewData.width && previewData.height" class="text-gray-400 dark:text-gray-500">@{{ previewData.width }}×@{{ previewData.height }}px</span>
            </div>
            <p class="text-gray-400 dark:text-gray-500 text-[11px]">{{ trans('dam::app.admin.dam.asset.edit.preview-modal.card.click-for-details') }}</p>
        </div>
    </div>

    <!-- Image editor (images only) -->
    <button
        v-if="previewData.file_type === 'image'"
        type="button"
        class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-violet-50 dark:hover:bg-cherry-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
        title="{{ trans('dam::app.admin.dam.asset.edit.preview-modal.card.edit-image') }}"
        @click="isEditOpen = true"
    >
        <span class="text-lg icon-edit"></span>
    </button>

    <!-- Fullscreen preview -->
    <button
        type="button"
        class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-400 dark:text-gray-500 hover:bg-violet-50 dark:hover:bg-cherry-800 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
        title="{{ trans('dam::app.admin.dam.asset.edit.tab.preview') }}"
        @click="openPreview"
    >
        <span class="text-lg icon-dam-preview"></span>
    </button>
    </div>
</div>

<!-- Inline asset preview — renders the actual media (no extra eye click) -->
<div class="flex items-center justify-center w-full rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900">
    <template v-if="previewData.file_type === 'image'">
        <div class="w-full" style="height: 70vh;">
            <v-zoomable-image
                :src="previewData.previewPath"
                :alt="previewData.file_name"
            ></v-zoomable-image>
        </div>
    </template>

    <template v-else-if="previewData.file_type === 'video'">
        <div class="w-full" style="height: 70vh;">
            @include('dam::asset.preview-modal.video.video-player')
        </div>
    </template>

    <template v-else-if="previewData.file_type === 'audio'">
        <div class="w-full flex justify-center py-4">
            @include('dam::asset.preview-modal.audio.audio-player')
        </div>
    </template>

    <template v-else-if="previewData.extension === 'pdf'">
        <div class="w-full" style="height: 70vh;">
            @include('dam::asset.preview-modal.files.pdf-viewer')
        </div>
    </template>

    <template v-else>
        <div class="p-10 text-center">
            <img
                :src="previewData.placeholderSvg"
                :alt="previewData.file_name"
                class="mx-auto max-h-32 max-w-full object-contain opacity-60"
            />
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-200 truncate max-w-md">@{{ previewData.file_name }}</p>
            <a
                v-if="previewData.downloadUrl"
                :href="previewData.downloadUrl"
                class="mt-2 inline-block text-violet-600 dark:text-violet-300 hover:underline text-sm"
            >@lang('dam::app.admin.dam.asset.edit.button.download')</a>
        </div>
    </template>
</div>
