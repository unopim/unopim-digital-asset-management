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

    /**
     * Thumbnails and previews are addressed by the asset's path, which does not change
     * when the binary behind it does, so a browser holding a render has no reason to ask
     * for it again inside its cache window. Stamping the stored file's modification time
     * into the URL turns a replaced binary into a new address and the render is fetched
     * on the next paint instead of after a forced reload.
     */
    public static function getThumbnailUrl(string $path): string
    {
        return route('admin.dam.file.thumbnail', [
            'path' => urlencode($path),
            'v'    => self::getRenderVersion($path),
        ]);
    }

    /**
     * S3 is left unstamped: its media is served straight from the bucket or through a
     * signed URL that already carries its own query, so there is no local render to bust.
     */
    public static function getRenderVersion(string $path): ?int
    {
        $disk = Directory::getAssetDisk();

        if ($disk === Directory::ASSETS_DISK_AWS) {
            return null;
        }

        try {
            return Storage::disk($disk)->lastModified($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getPreviewUrl(string $path, ?int $size = null): string
    {
        $previewUrl = route('admin.dam.file.preview', [
            'path' => urlencode($path),
            'size' => $size,
            'v'    => self::getRenderVersion($path),
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

    /** PDF/SVG are trusted here via the upload-time content scan, not sandboxed serving — Chrome's PDF viewer won't load in a sandboxed iframe at all. */
    public static function isInlineSafeMime(?string $mimeType): bool
    {
        $mimeType = strtolower(trim((string) $mimeType));

        if ($mimeType === '') {
            return false;
        }

        if ($mimeType === 'image/svg+xml') {
            return true;
        }

        return Str::startsWith($mimeType, ['image/', 'video/', 'audio/', 'application/pdf']);
    }

    public static function assetResponseHeaders(): array
    {
        return [
            'X-Content-Type-Options'  => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline'; object-src 'none'; frame-ancestors 'self'",
        ];
    }

    /**
     * Extensions that must never be stored, regardless of where they appear in the
     * file name. Includes the numbered and alternate PHP handler suffixes, because a
     * server configured with `AddHandler php-script .php5` executes them exactly as
     * it would a `.php`.
     */
    public const FORBIDDEN_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'php7',
        'php8',
        'phps',
        'pht',
        'phtm',
        'phtml',
        'phar',
        'inc',
        'js',
        'mjs',
        'cjs',
        'py',
        'sh',
        'bash',
        'bat',
        'cmd',
        'pl',
        'cgi',
        'asp',
        'aspx',
        'asa',
        'asax',
        'ascx',
        'ashx',
        'asmx',
        'cfm',
        'cfml',
        'jsp',
        'jspx',
        'jspf',
        'exe',
        'msi',
        'com',
        'scr',
        'dll',
        'so',
        'dylib',
        'rb',
        'jar',
        'war',
        'ps1',
        'psm1',
        'vbs',
        'vbe',
        'wsf',
        'wsh',
        'jse',
        'reg',
        'lnk',
        'html',
        'htm',
        'xhtml',
        'shtml',
        'hta',
        'svgz',
        'zip',
        'rar',
        '7z',
        'tar',
        'gz',
        'tgz',
        'bz2',
        'xz',
        'iso',
        'dmg',
        'cab',
    ];

    /**
     * Detected media types that must never be stored whatever the file is called.
     *
     * `text/x-php` matters as much as `application/x-php`: libmagic reports the former
     * for a plain `<?php ...` script, so a backend script renamed `adminer.jpg` was
     * detected correctly and still passed, because only the `application/*` spelling was
     * listed.
     */
    public const FORBIDDEN_MIME_TYPES = [
        'application/x-php',
        'text/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'application/x-javascript',
        'text/javascript',
        'application/javascript',
        'text/x-python',
        'application/x-python-code',
        'application/x-sh',
        'text/x-sh',
        'text/x-shellscript',
        'application/x-shellscript',
        'application/x-bat',
        'application/x-msdos-program',
        'application/x-perl',
        'text/x-perl',
        'application/x-cgi',
        'text/x-asp',
        'application/x-aspx',
        'application/x-jsp',
        'application/x-msdownload',
        'application/x-dosexec',
        'application/x-executable',
        'application/x-sharedlib',
        'application/x-mach-binary',
        'application/java-archive',
        'application/x-java-applet',
        'application/x-ruby',
        'text/x-ruby',
        'text/html',
        'application/xhtml+xml',
        'application/zip',
        'application/x-rar-compressed',
        'application/vnd.rar',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-gzip',
        'application/x-bzip2',
        'application/x-xz',
    ];

    /**
     * Detected types that say nothing about what a file really is.
     *
     * libmagic falls back to these for short, empty or unrecognised payloads, so they
     * cannot contradict a declared extension — a `.jpg` reported as `text/plain` may be a
     * truncated fixture as easily as an attack. Anything genuinely dangerous in this
     * group is caught by FORBIDDEN_MIME_TYPES or by the executable-content scan instead.
     */
    private const INCONCLUSIVE_MIME_TYPES = [
        'text/plain',
        'application/octet-stream',
        'application/x-empty',
        'inode/x-empty',
    ];

    /**
     * Extension families used to check a file's real content against the name it was
     * given. Office and PDF types are deliberately absent: their detected types vary too
     * much across libmagic builds to gate on, and they are covered by the blocklists.
     */
    private const EXTENSION_MIME_PREFIXES = [
        'jpg'  => ['image/'],
        'jpeg' => ['image/'],
        'png'  => ['image/'],
        'gif'  => ['image/'],
        'webp' => ['image/'],
        'bmp'  => ['image/'],
        'tif'  => ['image/'],
        'tiff' => ['image/'],
        'avif' => ['image/'],
        'heic' => ['image/'],
        'heif' => ['image/'],
        'ico'  => ['image/', 'application/octet-stream'],
        'svg'  => ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'],
        'mp4'  => ['video/', 'application/mp4'],
        'mov'  => ['video/'],
        'webm' => ['video/', 'audio/'],
        'mkv'  => ['video/'],
        'avi'  => ['video/'],
        'flv'  => ['video/'],
        'wmv'  => ['video/'],
        'm4v'  => ['video/'],
        'mp3'  => ['audio/', 'application/octet-stream'],
        'wav'  => ['audio/'],
        'ogg'  => ['audio/', 'video/', 'application/ogg'],
        'flac' => ['audio/'],
        'aac'  => ['audio/'],
        'm4a'  => ['audio/', 'video/mp4'],
        'wma'  => ['audio/', 'video/x-ms-asf'],
    ];

    /** Cap for hasPdfActiveContent()/hasSvgActiveContent() scans — avoids reading multi-GB files fully into memory. */
    protected const MAX_CONTENT_SCAN_BYTES = 2 * 1024 * 1024;

    /** Matches the upload size limit already enforced before isForbiddenFile() runs, so padding past a fixed cap can't hide a marker. */
    protected static function maxScanBytes(): int
    {
        $maxUploadKb = self::getMaxUploadSizeKb();

        if ($maxUploadKb <= 0 || $maxUploadKb === PHP_INT_MAX) {
            return self::MAX_CONTENT_SCAN_BYTES;
        }

        return max(self::MAX_CONTENT_SCAN_BYTES, $maxUploadKb * 1024);
    }

    public static function isForbiddenFile(?string $extension, ?string $mimeType, ?string $fileName = null, ?string $realPath = null): bool
    {
        $forbiddenExtensions = self::FORBIDDEN_EXTENSIONS;

        $forbiddenMimeTypes = self::FORBIDDEN_MIME_TYPES;

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

        if (self::hasForbiddenExtensionSegment($fileName, $forbiddenExtensions)) {
            return true;
        }

        if (($extension && in_array($extension, $forbiddenExtensions)) || ($mimeType && in_array($mimeType, $forbiddenMimeTypes))) {
            return true;
        }

        $detected = self::detectMimeType($realPath) ?: $mimeType;

        if ($detected && in_array(strtolower($detected), $forbiddenMimeTypes, true)) {
            return true;
        }

        if (self::mismatchesDeclaredExtension($extension, $detected)) {
            return true;
        }

        $detectedLower = strtolower((string) $detected);

        if (($extension === 'pdf' || $detectedLower === 'application/pdf') && self::hasPdfActiveContent($realPath)) {
            return true;
        }

        if (($extension === 'svg' || $detectedLower === 'image/svg+xml') && self::hasSvgActiveContent($realPath)) {
            return true;
        }

        if ($extension === 'csv' && self::hasCsvActiveContent($realPath)) {
            return true;
        }

        if ($extension === 'rtf' && self::hasRtfActiveContent($realPath)) {
            return true;
        }

        if (in_array($extension, ['docx', 'xlsx', 'odt', 'ods'], true) && self::hasOfficeZipMacroContent($realPath)) {
            return true;
        }

        if (in_array($extension, ['doc', 'xls'], true) && self::hasLegacyOfficeMacroContent($realPath)) {
            return true;
        }

        return self::hasExecutableContent($realPath, $extension);
    }

    /** Reads up to self::MAX_CONTENT_SCAN_BYTES from the start of the file. */
    protected static function readScanPrefix(string $realPath): string
    {
        $handle = @fopen($realPath, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            return (string) @fread($handle, self::maxScanBytes());
        } finally {
            @fclose($handle);
        }
    }

    /** Rejects auto-run PDF scripting. */
    protected static function hasPdfActiveContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $content = self::readScanPrefix($realPath);

        if ($content === '') {
            return false;
        }

        return (bool) preg_match('/\/(OpenAction|AA|JavaScript|JS|Launch)\b/', $content);
    }

    /** SVG is served inline, so <script>/event-handlers/javascript: URIs run in-origin like HTML. */
    protected static function hasSvgActiveContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $content = self::readScanPrefix($realPath);

        if ($content === '') {
            return false;
        }

        if (preg_match('/<script[\s>]/i', $content)) {
            return true;
        }

        if (preg_match('/\son\w+\s*=/i', $content)) {
            return true;
        }

        if (preg_match('/(?:href|xlink:href)\s*=\s*["\']?\s*javascript:/i', $content)) {
            return true;
        }

        return (bool) preg_match('/<foreignObject[\s>]/i', $content);
    }

    /** Rejects a cell starting with =, +, -, or @ — Excel/Sheets treats it as a formula, enabling DDE/command injection on open (OWASP CSV Injection). */
    protected static function hasCsvActiveContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $content = self::readScanPrefix($realPath);

        if ($content === '') {
            return false;
        }

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            foreach (str_getcsv($line, escape: '') as $cell) {
                if (preg_match('/^[\s"\']*[=+\-@]/', (string) $cell)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Rejects an RTF carrying an embedded/linked OLE object (\object, \objdata, \objautlink) — a known parser-exploit and payload-delivery vector. */
    protected static function hasRtfActiveContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $content = self::readScanPrefix($realPath);

        if ($content === '') {
            return false;
        }

        return (bool) preg_match('/\\\\(object|objdata|objclass|objautlink|objupdate)\b/i', $content);
    }

    /** Rejects a macro-enabled OOXML/ODF file: word|xl/vbaProject.bin, or an ODF Basic/ auto-run event listener. */
    protected static function hasOfficeZipMacroContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath) || ! class_exists(\ZipArchive::class)) {
            return false;
        }

        $zip = new \ZipArchive;

        if ($zip->open($realPath) !== true) {
            return false;
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);

                if ($name === false) {
                    continue;
                }

                if (preg_match('#^(word|xl)/vbaProject\.bin$#i', $name) || str_starts_with($name, 'Basic/')) {
                    return true;
                }
            }

            foreach (['content.xml', 'settings.xml'] as $entry) {
                $xml = $zip->getFromName($entry);

                if (is_string($xml) && preg_match('/office:event-listeners|script:event-listener|vnd\.sun\.star\.script/i', $xml)) {
                    return true;
                }
            }

            return false;
        } finally {
            $zip->close();
        }
    }

    /** Rejects a legacy OLE2 .doc/.xls carrying a VBA project; stream names stay plaintext UTF-16LE even when p-code is compiled. */
    protected static function hasLegacyOfficeMacroContent(?string $realPath): bool
    {
        if (! $realPath || ! is_file($realPath)) {
            return false;
        }

        $content = self::readScanPrefix($realPath);

        if ($content === '' || ! str_starts_with($content, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return false;
        }

        foreach (['VBA_PROJECT', 'Macros', '_VBA_PROJECT'] as $marker) {
            if (str_contains($content, $marker) || str_contains($content, self::toUtf16Le($marker))) {
                return true;
            }
        }

        return false;
    }

    /** UTF-16LE re-encode, used to match OLE2 directory-sector stream names against ASCII markers. */
    protected static function toUtf16Le(string $ascii): string
    {
        return implode('', array_map(fn (string $char) => $char."\x00", str_split($ascii)));
    }

    /**
     * Read the media type from the bytes on disk rather than trusting the client.
     *
     * `UploadedFile::getMimeType()` already guesses from content, but not every caller
     * supplies one, and the browser-supplied type is attacker-controlled.
     */
    protected static function detectMimeType(?string $realPath): ?string
    {
        if (! $realPath || ! is_file($realPath) || ! function_exists('finfo_open')) {
            return null;
        }

        $finfo = @finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return null;
        }

        try {
            $detected = @finfo_file($finfo, $realPath);

            return is_string($detected) && $detected !== '' ? strtolower($detected) : null;
        } finally {
            @finfo_close($finfo);
        }
    }

    /**
     * Reject a file whose bytes disagree with the extension it claims.
     *
     * Renaming `adminer.php` to `adminer.jpg` defeats every name-based rule — the name
     * carries one plausible extension and nothing else looks wrong. Requiring a `.jpg` to
     * actually contain an image closes that, and closes the same trick for every other
     * media extension at once.
     *
     * Extensions outside the map are not gated here, so documents and archives keep
     * relying on the blocklists above.
     */
    protected static function mismatchesDeclaredExtension(?string $extension, ?string $detectedMime): bool
    {
        if (! $extension || ! $detectedMime) {
            return false;
        }

        $allowedPrefixes = self::EXTENSION_MIME_PREFIXES[strtolower($extension)] ?? null;

        if ($allowedPrefixes === null) {
            return false;
        }

        if (in_array($detectedMime, self::INCONCLUSIVE_MIME_TYPES, true)) {
            return false;
        }

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($detectedMime, $prefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reject media whose bytes carry a script the server could be tricked into running.
     *
     * A `GIF89a<?php ... ?>` polyglot is a genuine GIF by magic number, so the blocklists
     * and the extension check both pass it; only reading the bytes catches it.
     *
     * Scoped to the media extensions because that is where such a marker is anomalous,
     * and matched only against long markers. Scanning every type for short ones produced
     * false positives on legitimate binaries — `<%` occurs by chance at offset 1544 of a
     * perfectly ordinary generated PDF, and `<?=` is short enough to collide roughly once
     * in two thousand 8 KB files. A shebang only means anything at offset 0.
     */
    protected static function hasExecutableContent(?string $realPath, ?string $extension): bool
    {
        if (! $realPath || ! is_file($realPath) || ! $extension) {
            return false;
        }

        if (! array_key_exists(strtolower($extension), self::EXTENSION_MIME_PREFIXES)) {
            return false;
        }

        $handle = @fopen($realPath, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            $head = (string) @fread($handle, 8192);
        } finally {
            @fclose($handle);
        }

        if ($head === '') {
            return false;
        }

        if (str_starts_with($head, '#!/')) {
            return true;
        }

        if (stripos($head, '<?php') !== false) {
            return true;
        }

        return (bool) preg_match('/<script[\s>]/i', $head);
    }

    /**
     * Reject a name carrying a forbidden extension anywhere in it, not just as the
     * final segment.
     *
     * Callers derive `$extension` from `getClientOriginalExtension()` or
     * `pathinfo(PATHINFO_EXTENSION)`, both of which return only the last segment, so
     * `shell.php.jpg` presented as `jpg` passed every check and was stored verbatim.
     * Apache's `mod_mime` matches handlers against *any* extension segment, so that
     * file executes as PHP on a default LAMP host.
     *
     * Trailing dots and spaces are stripped per segment because Windows and some
     * upload paths silently discard them, turning `shell.php.` back into `shell.php`.
     */
    protected static function hasForbiddenExtensionSegment(?string $fileName, array $forbiddenExtensions): bool
    {
        if (! $fileName) {
            return false;
        }

        $segments = explode('.', strtolower(basename(str_replace('\\', '/', $fileName))));

        array_shift($segments);

        foreach ($segments as $segment) {
            if (in_array(trim($segment, " \t\n\r\0\x0B."), $forbiddenExtensions, true)) {
                return true;
            }
        }

        return false;
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
