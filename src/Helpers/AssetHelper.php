<?php

namespace Webkul\DAM\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\DAM\Models\Directory;

class AssetHelper
{
    /**
     * Effective max upload size in KB — derived entirely from PHP's runtime
     * upload_max_filesize and post_max_size so the validator honours whatever
     * the server is already configured to accept.
     * Returns PHP_INT_MAX when PHP imposes no limit, so Laravel's max: rule
     * effectively becomes a no-op and the server is the real gatekeeper.
     */
    public static function getMaxUploadSizeKb(): int
    {
        $phpLimitKb = self::iniValueToKb((string) ini_get('upload_max_filesize'));
        $postLimitKb = self::iniValueToKb((string) ini_get('post_max_size'));

        $candidates = array_filter([
            $phpLimitKb ?: null,
            $postLimitKb ?: null,
        ]);

        return $candidates ? (int) min($candidates) : PHP_INT_MAX;
    }

    /**
     * Format a kilobyte count into a human-readable string (e.g. "512 MB", "2 GB").
     */
    public static function humanReadableSize(int $kilobytes): string
    {
        if ($kilobytes >= 1024 * 1024) {
            return round($kilobytes / 1024 / 1024, 2).' GB';
        }

        if ($kilobytes >= 1024) {
            return round($kilobytes / 1024, 2).' MB';
        }

        return $kilobytes.' KB';
    }

    /**
     * Convert a php.ini shorthand size (e.g. "50M", "1G", "2048K") to kilobytes.
     */
    protected static function iniValueToKb(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g'     => $number * 1024 * 1024,
            'm'     => $number * 1024,
            'k'     => $number,
            default => ((float) $value) / 1024,
        };
    }

    /**
     * fetch file type based on the mime type
     *
     * @param [type] $file
     * @return void
     */
    public static function getFileType($file)
    {
        $mimeType = $file->getMimeType();

        if (str_contains($mimeType, 'image')) {
            return 'image';
        } elseif (str_contains($mimeType, 'video')) {
            return 'video';
        } elseif (str_contains($mimeType, 'audio')) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    /**
     * fetch file type based on the extension
     *
     * @param [type] $file
     * @return void
     */
    public static function getFileTypeUsingExtension(string $extension)
    {
        $extension = strtolower($extension);

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp', 'webp', 'tiff', 'tif', 'jfif'];
        $videoExtensions = ['mp4', 'mkv', 'avi', 'mov', 'flv'];
        $audioExtensions = ['mp3', 'wav', 'aac', 'flac'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
        $spreadsheetExtensions = ['xls', 'xlsx', 'ods', 'csv'];

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            return 'video';
        } elseif (in_array($extension, $audioExtensions)) {
            return 'audio';
        } elseif (in_array($extension, $spreadsheetExtensions)) {
            return 'sheet';
        } elseif (in_array($extension, $documentExtensions)) {
            return 'file';
        } else {
            return 'unspecified';
        }
    }

    /**
     * Displayable File name
     */
    public static function getDisplayFileName(string $fileName): string
    {
        if (strlen($fileName) > 29) {
            $fileName = substr($fileName, 0, 20).'...'.substr($fileName, strrpos($fileName, '.'));
        }

        return $fileName;
    }

    /**
     * Resolve the most appropriate preview URL for an asset.
     */
    public static function getPreviewUrl(string $path, ?int $size = null): string
    {
        $previewUrl = route('admin.dam.file.preview', [
            'path' => urlencode($path),
            'size' => $size,
        ]);

        $disk = Directory::getAssetDisk();

        if ($disk !== Directory::ASSETS_DISK_AWS) {
            return $previewUrl;
        }

        $awsDisk = Storage::disk($disk);

        if ($awsDisk->exists($path) && self::isSupportedMediaFile($awsDisk->mimeType($path))) {
            try {
                $visibility = $awsDisk->getVisibility($path);

                if ($visibility === 'public') {
                    return $awsDisk->url($path);
                }

                return $awsDisk->temporaryUrl($path, now()->addMinutes(10));
            } catch (\Throwable $exception) {
                return $previewUrl;
            }
        }

        return $previewUrl;
    }

    /**
     * Check if the MIME type corresponds to a supported media file
     *
     * Supported types include SVG images, PDF, video, and audio formats.
     */
    public static function isSupportedMediaFile($mimeType)
    {
        return Str::startsWith($mimeType, 'image/') ||
            Str::startsWith($mimeType, 'application/pdf') ||
            Str::startsWith($mimeType, 'video/') ||
            Str::startsWith($mimeType, 'audio/');
    }

    /**
     * Check if given extension or mime type is forbidden for upload
     */
    public static function isForbiddenFile(?string $extension, ?string $mimeType): bool
    {
        $forbiddenExtensions = [
            'php',
            'js',
            'py',
            'sh',
            'bat',
            'pl',
            'cgi',
            'asp',
            'aspx',
            'jsp',
            'exe',
            'rb',
            'jar',
        ];

        $forbiddenMimeTypes = [
            'application/x-php',
            'application/x-javascript',
            'text/javascript',
            'application/javascript',
            'text/x-python',
            'application/x-sh',
            'application/x-bat',
            'application/x-perl',
            'application/x-cgi',
            'text/x-asp',
            'application/x-aspx',
            'application/x-jsp',
            'application/x-msdownload',
            'application/java-archive',
            'application/x-ruby',
        ];

        if ($extension) {
            $extension = strtolower($extension);
        }

        if ($mimeType) {
            $mimeType = strtolower($mimeType);
        }

        return ($extension && in_array($extension, $forbiddenExtensions)) || ($mimeType && in_array($mimeType, $forbiddenMimeTypes));
    }
}
