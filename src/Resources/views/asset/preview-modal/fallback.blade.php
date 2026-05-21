<div class="flex flex-col items-center gap-4 text-center p-8">
    <img
        :src="previewData.placeholderSvg"
        :alt="previewData.file_name"
        class="h-20 w-20 object-contain opacity-40"
    />
    <p class="text-sm text-gray-500 dark:text-gray-400">
        @lang('dam::app.admin.dam.asset.edit.preview-modal.not-available')
    </p>
    <div class="flex items-center gap-3">
        <a
            :href="previewData.downloadUrl"
            class="primary-button inline-flex"
        >
            @lang('dam::app.admin.dam.asset.edit.preview-modal.download-file')
        </a>
        <template v-if="!['zip','rar','7z','gz','tar','bz2','xz'].includes((previewData.extension || '').toLowerCase())">
            <a
                :href="previewData.downloadCompressedUrl"
                class="secondary-button inline-flex"
            >
                @lang('dam::app.admin.dam.asset.edit.preview-modal.download-zip')
            </a>
        </template>
    </div>
</div>
