<?php

namespace Webkul\DAM\Support;

use Symfony\Component\Mime\MimeTypes;
use Webkul\DAM\Helpers\AssetHelper;

class ServableMedia
{
    protected const EXECUTABLE_EXTENSIONS = ['svg', 'svgz'];

    public static function permits(string $extension, string $sourceFile, ?string $fileName = null): bool
    {
        if (! is_file($sourceFile) || in_array($extension, self::EXECUTABLE_EXTENSIONS, true)) {
            return false;
        }

        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($sourceFile);

        if (AssetHelper::isForbiddenFile($extension, $mimeType, $fileName, $sourceFile)) {
            return false;
        }

        return in_array($extension, MimeTypes::getDefault()->getExtensions($mimeType), true);
    }
}
