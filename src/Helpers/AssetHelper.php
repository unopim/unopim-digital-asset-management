<?php

namespace Webkul\DAM\Helpers;

class AssetHelper
{
    /**
     * Maximum allowed asset upload size in kilobytes (50 MB).
     */
    public const MAX_UPLOAD_SIZE_KB = 51200;

    /**
     * Effective max upload size in KB — the lesser of the DAM cap
     * and PHP's runtime upload_max_filesize, so the validator can never
     * promise more than the server actually accepts.
     */
    public static function getMaxUploadSizeKb(): int
    {
        $phpLimitKb = self::iniValueToKb((string) ini_get('upload_max_filesize'));
        $postLimitKb = self::iniValueToKb((string) ini_get('post_max_size'));

        $candidates = array_filter([
            self::MAX_UPLOAD_SIZE_KB,
            $phpLimitKb ?: null,
            $postLimitKb ?: null,
        ]);

        return (int) min($candidates);
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
     * Check if given extension or mime type is forbidden for upload
     */
    public static function isForbiddenFile(?string $extension, ?string $mimeType): bool
    {
        $forbiddenExtensions = [
            'php', 'js', 'py', 'sh', 'bat', 'pl', 'cgi', 'asp', 'aspx', 'jsp', 'exe', 'rb', 'jar',
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
