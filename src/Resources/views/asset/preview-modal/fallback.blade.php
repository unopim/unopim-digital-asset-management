@php
    $archiveExtensions = ['zip', 'rar', '7z', 'gz', 'tar', 'bz2', 'xz'];
    $isArchive = in_array(strtolower($asset->extension ?? ''), $archiveExtensions);
@endphp

<div class="flex flex-col items-center gap-4 text-center p-8">
    <img
        src="{{ $placeholderSvg }}"
        alt="{{ $asset->file_name }}"
        class="h-20 w-20 object-contain opacity-40"
    />
    <p class="text-sm text-gray-500 dark:text-gray-400">
        @lang('dam::app.admin.dam.asset.edit.preview-modal.not-available')
    </p>
    <div class="flex items-center gap-3">
        <a
            href="{{ route('admin.dam.assets.download', $asset->id) }}"
            class="primary-button inline-flex"
        >
            @lang('dam::app.admin.dam.asset.edit.preview-modal.download-file')
        </a>
        @if (! $isArchive)
            <a
                href="{{ route('admin.dam.assets.download_compressed', $asset->id) }}"
                class="secondary-button inline-flex"
            >
                @lang('dam::app.admin.dam.asset.edit.preview-modal.download-zip')
            </a>
        @endif
    </div>
</div>
