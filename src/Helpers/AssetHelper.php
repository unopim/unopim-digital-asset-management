<?php

namespace Webkul\DAM\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\DAM\Models\Directory;

class AssetHelper
{
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

    public static function getFileType($file)
    {
        $mimeType = $file->getMimeType();

        if (str_contains($mimeType, 'image')) {
            return 'image';
        } elseif (str_contains($mimeType, 'video')) {
            return 'video';
        } elseif (str_contains($mimeType, 'audio')) {
            return 'audio';
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['mp3', 'wav', 'aac', 'flac', 'ogg', 'm4a', 'wma'])) {
            return 'audio';
        }

        if (in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'flv', 'webm', 'wmv'])) {
            return 'video';
        }

        return 'document';
    }

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

    public static function getDisplayFileName(string $fileName): string
    {
        if (strlen($fileName) > 29) {
            $fileName = substr($fileName, 0, 20).'...'.substr($fileName, strrpos($fileName, '.'));
        }

        return $fileName;
    }

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

    public static function isSupportedMediaFile($mimeType)
    {
        return Str::startsWith($mimeType, 'image/') ||
            Str::startsWith($mimeType, 'application/pdf') ||
            Str::startsWith($mimeType, 'video/') ||
            Str::startsWith($mimeType, 'audio/');
    }

    public static function isInlineSafeMime(?string $mimeType): bool
    {
        $mimeType = strtolower(trim((string) $mimeType));

        if ($mimeType === '') {
            return false;
        }

        if ($mimeType === 'image/svg+xml') {
            return true;
        }

        return Str::startsWith($mimeType, ['image/', 'video/', 'audio/'])
            || $mimeType === 'application/pdf';
    }

    public static function assetResponseHeaders(): array
    {
        return [
            'X-Content-Type-Options'  => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline'; object-src 'none'; frame-ancestors 'self'",
        ];
    }

    public static function isForbiddenFile(?string $extension, ?string $mimeType, ?string $fileName = null, ?string $realPath = null): bool
    {
        $forbiddenExtensions = [
            'php',
            'phtml',
            'phar',
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
            'html',
            'htm',
            'xhtml',
            'shtml',
            'hta',
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
            'text/html',
            'application/xhtml+xml',
        ];

        $forbiddenFileNames = [
            '.DS_Store',
            '._.DS_Store',
            'Thumbs.db',
            'desktop.ini',
        ];

        if ($extension) {
            $extension = strtolower($extension);
        }

        if ($mimeType) {
            $mimeType = strtolower($mimeType);
        }

        if ($fileName && in_array(basename($fileName), $forbiddenFileNames, true)) {
            return true;
        }

        if (self::isPlaceholderImage($extension, $mimeType, $fileName, $realPath)) {
            return true;
        }

        return ($extension && in_array($extension, $forbiddenExtensions)) || ($mimeType && in_array($mimeType, $forbiddenMimeTypes));
    }

    public static function isPlaceholderImage(?string $extension, ?string $mimeType, ?string $fileName = null, ?string $realPath = null): bool
    {
        $isSvg = $extension === 'svg' || $mimeType === 'image/svg+xml';

        if (! $isSvg) {
            return false;
        }

        if ($fileName) {
            $stem = strtolower(pathinfo(basename($fileName), PATHINFO_FILENAME));

            if (Str::startsWith($stem, 'no-records-found')) {
                return true;
            }
        }

        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $contents = @file_get_contents($realPath, false, null, 0, 65536);

        if ($contents === false || $contents === '') {
            return false;
        }

        $normalize = fn (string $value): string => strtolower(preg_replace('/\s+/', '', $value));

        $haystack = $normalize($contents);

        if (! str_contains($haystack, '#7c3aec')) {
            return false;
        }

        $pathSignatures = [
            'M44 88H42.908C29.868 88 23.34 88 18.812 84.808',
            'M12 48C12 44.4641 13.4046 41.0731 15.9049 38.5729',
            'M76.9143 80.5715L84.1143 87.7715',
        ];

        foreach ($pathSignatures as $signature) {
            if (str_contains($haystack, $normalize($signature))) {
                return true;
            }
        }

        return false;
    }
}
