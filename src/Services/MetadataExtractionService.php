<?php

namespace Webkul\DAM\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Webkul\DAM\Helpers\AssetHelper;

class MetadataExtractionService
{
    /** Keys to strip from exiftool output before returning. */
    private array $strippedExiftoolKeys = [
        'ExifToolVersion'     => true,
        'SourceFile'          => true,
        'Directory'           => true,
        'FilePermissions'     => true,
        'FileAccessDate'      => true,
        'FileInodeChangeDate' => true,
    ];

    /** Create a new instance. */
    public function __construct() {}

    /**
     * Extract metadata from a file, dispatching by detected media type.
     */
    public function extractMetadata(string $path, string $disk = 'local', ?string $localPath = null, bool $isPartial = false, ?string $originalFileName = null): array
    {
        if (empty($path) && empty($localPath)) {
            return [];
        }

        $tempPath = $localPath ?: $this->getFileTempPath($path, $disk, $isPartial);
        $originalFileName = $originalFileName ?: ($path !== '' ? basename($path) : '');

        try {
            if (! $tempPath) {
                return [];
            }

            $mime = @mime_content_type($tempPath) ?: '';
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            \Log::info(sprintf('MetadataExtractionService: detected mime "%s" for %s', $mime, $path));

            if (AssetHelper::isForbiddenFile($ext, $mime, $originalFileName)) {
                \Log::info(sprintf('MetadataExtractionService: skipping forbidden file (ext="%s", mime="%s") for %s', $ext, $mime, $path));

                return [];
            }

            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tif', 'tiff', 'svg', 'heic', 'heif', 'avif'];
            $videoExts = ['mp4', 'mov', 'mkv', 'webm', 'avi', 'wmv', 'flv', 'm4v', '3gp', 'mpg', 'mpeg'];
            $audioExts = ['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma', 'opus'];

            if (str_starts_with($mime, 'image/') || in_array($ext, $imageExts, true)) {
                $fullMetadata = $this->extractFullMetadata($path, $disk, $tempPath, $isPartial, $originalFileName);

                if ($this->isErrorResponse($fullMetadata)) {
                    $fullMetadata = [];
                }

                return array_merge($this->handleArrayMetadata($fullMetadata), ['exif' => $fullMetadata]);
            }

            if (str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/')
                || in_array($ext, $videoExts, true) || in_array($ext, $audioExts, true)) {
                return $this->extractMediaMetadata($tempPath, $originalFileName);
            }

            return $this->extractDocumentMetadata($tempPath, $originalFileName);
        } finally {
            if (! $localPath) {
                $this->cleanupTempFile($tempPath, $disk);
            }
        }
    }

    /**
     * Run exiftool against a local temp path and read the first JSON record.
     */
    private function runExiftool(string $tempPath, string $context, ?string $originalFileName = null): array
    {
        if (! $this->exiftoolExists()) {
            return [];
        }

        $command = sprintf(
            'exiftool -j -f -q -charset iptc=utf8 %s',
            escapeshellarg($tempPath)
        );

        $outputLines = [];
        $returnVar = 0;
        exec($command, $outputLines, $returnVar);

        if ($returnVar !== 0) {
            \Log::error(sprintf('runExiftool[%s]: exiftool failed (exit %d) for %s', $context, $returnVar, $tempPath));

            return [];
        }

        $output = implode("\n", $outputLines);

        if ($output === '') {
            \Log::error(sprintf('runExiftool[%s]: empty output for %s', $context, $tempPath));

            return [];
        }

        $json = json_decode($output, true);

        if (! is_array($json) || ! isset($json[0]) || ! is_array($json[0])) {
            \Log::error(sprintf('runExiftool[%s]: non-array JSON for %s', $context, $tempPath));

            return [];
        }

        $data = array_diff_key($json[0], $this->strippedExiftoolKeys);

        if (! empty($originalFileName) && array_key_exists('FileName', $data)) {
            $data['FileName'] = $originalFileName;
        }

        return $data;
    }

    /**
     * Extract metadata for video/audio files using exiftool.
     */
    protected function extractMediaMetadata(string $tempPath, ?string $originalFileName = null): array
    {
        $data = $this->runExiftool($tempPath, 'media', $originalFileName);

        return $data ? array_merge($this->handleArrayMetadata($data), ['exif' => $data]) : [];
    }

    /**
     * Extract metadata for files that are not image/video/audio via exiftool.
     */
    protected function extractDocumentMetadata(string $tempPath, ?string $originalFileName = null): array
    {
        $data = $this->runExiftool($tempPath, 'generic', $originalFileName);

        return $data ? array_merge($this->handleArrayMetadata($data), ['exif' => $data]) : [];
    }

    /**
     * Determine whether the given data represents an error response.
     */
    protected function isErrorResponse($data): bool
    {
        return is_array($data) && isset($data['success']) && $data['success'] === false;
    }

    /**
     * Optimized array metadata handler.
     */
    protected function handleArrayMetadata(array $metadata): array
    {
        $result = [];

        foreach ($metadata as $key => $value) {
            if (! is_array($value)) {
                $result[$key] = $value;

                continue;
            }

            if (array_is_list($value)) {
                $result[$key] = implode(',', $value);
            } else {
                foreach ($value as $subKey => $subValue) {
                    $result["{$key}:{$subKey}"] = $subValue;
                }
            }
        }

        return $result;
    }

    /**
     * Optimized EXIF metadata extraction.
     */
    public function getExifMetadata(string $path, string $disk = 'local', ?string $localPath = null, bool $isPartial = false): array
    {
        try {
            $tempPath = $localPath ?: $this->getFileTempPath($path, $disk, $isPartial);

            if (! $tempPath) {
                return $this->errorResponse("File not found: $path");
            }

            $image = (new ImageManager(new Driver))->read($tempPath);
            $exif = $image->exif();

            if ($exif && ! is_array($exif)) {
                $exif = $exif->toArray();
            }

            if (is_array($exif) && array_key_exists('ExtensibleMetadataPlatform', $exif)) {
                unset($exif['ExtensibleMetadataPlatform']);
                unset($exif['ImageResourceInformation']);
                unset($exif['ICC_Profile']);
            }

            if (! $localPath) {
                $this->cleanupTempFile($tempPath, $disk);
            }

            return $exif ?: [];
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to read EXIF metadata: '.$e->getMessage());
        }
    }

    /**
     * Optimized full metadata extraction.
     */
    public function extractFullMetadata(string $path, string $disk = 'local', ?string $localPath = null, bool $isPartial = false, ?string $originalFileName = null): array
    {
        try {
            $tempPath = $localPath ?: $this->getFileTempPath($path, $disk, $isPartial);

            if (! $tempPath) {
                return $this->errorResponse("File not found: $path");
            }

            $originalFileName = $originalFileName ?: ($path !== '' ? basename($path) : '');

            $data = $this->runExiftool($tempPath, 'image', $originalFileName);

            if (! $localPath) {
                $this->cleanupTempFile($tempPath, $disk);
            }

            if (empty($data)) {
                return $this->errorResponse('ExifTool failed to extract metadata');
            }

            return $this->expandExifGroupAliases($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to extract metadata: '.$e->getMessage());
        }
    }

    /**
     * Expand exiftool group-prefixed keys to their alternative casings.
     */
    private function expandExifGroupAliases(array $data): array
    {
        $exifGroups = ['IFD0', 'ExifIFD', 'GPS', 'InteropIFD', 'IPTC', 'XMP', 'Composite'];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'System:')) {
                $fileKey = 'File:'.substr($key, 7);
                if (! isset($data[$fileKey])) {
                    $data[$fileKey] = $value;
                }
            }

            foreach ($exifGroups as $group) {
                if (str_starts_with($key, $group.':')) {
                    $normalizedKey = strtoupper($group).':'.substr($key, strlen($group) + 1);
                    if (! isset($data[$normalizedKey])) {
                        $data[$normalizedKey] = $value;
                    }
                }
            }
        }

        return $data;
    }

    private $curlHandle = null;

    private $s3Disk = null;

    /**
     * Get temporary file path with optimized storage handling.
     */
    public function getFileTempPath(string $path, string $disk, bool $isPartial = false): ?string
    {
        if ($disk !== 's3') {
            return file_exists($path) ? $path : null;
        }

        $startTime = microtime(true);
        try {
            if (! $this->s3Disk) {
                $this->s3Disk = Storage::disk('s3');
            }

            $url = $this->s3Disk->temporaryUrl($path, now()->addMinutes(10));

            $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'tmp';
            $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'s3img_'.uniqid('', true).'.'.$extension;

            if (! $this->curlHandle) {
                $this->curlHandle = curl_init();
            } else {
                curl_reset($this->curlHandle);
            }

            $fp = fopen($tempPath, 'w+');

            $curlOptions = [
                CURLOPT_URL            => $url,
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $isPartial ? 10 : 0,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_ENCODING       => '',
                CURLOPT_TCP_FASTOPEN   => 1,
                CURLOPT_BUFFERSIZE     => 1048576,
            ];

            if ($isPartial) {
                $curlOptions[CURLOPT_RANGE] = '0-65535';
            }

            curl_setopt_array($this->curlHandle, $curlOptions);
            curl_exec($this->curlHandle);

            $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
            fclose($fp);

            if (! in_array($httpCode, [200, 206]) || ! file_exists($tempPath) || filesize($tempPath) === 0) {
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }

                return null;
            }

            \Log::info(sprintf('Time taken getFileTempPath (%s): %sms', $isPartial ? 'Partial' : 'Full', round((microtime(true) - $startTime) * 1000)));

            return $tempPath;
        } catch (\Exception $e) {
            \Log::error('getFileTempPath failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Check whether the exiftool binary is available on this system (cached per request).
     */
    public function exiftoolExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        exec('exiftool -ver 2>/dev/null', $out, $code);

        return $exists = ($code === 0 && ! empty($out));
    }

    /**
     * Extract the embedded cover-art image from a local audio file.
     */
    public function extractCoverArtData(string $localPath): ?string
    {
        if (! $this->exiftoolExists() || ! file_exists($localPath)) {
            return null;
        }

        $tmpFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dam_cover_'.uniqid('', true).'.tmp';

        try {
            exec(sprintf(
                'exiftool -b -Picture %s > %s 2>/dev/null',
                escapeshellarg($localPath),
                escapeshellarg($tmpFile)
            ));

            if (! file_exists($tmpFile) || filesize($tmpFile) === 0) {
                return null;
            }

            return file_get_contents($tmpFile) ?: null;
        } finally {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Persist cover-art binary data to the asset disk and return the storage-relative path.
     */
    public function storeCoverArt(string $imageData, int $assetId, string $disk): ?string
    {
        if (empty($imageData)) {
            return null;
        }

        $sniffFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dam_cover_sniff_'.uniqid('', true).'.tmp';
        file_put_contents($sniffFile, $imageData);
        $mime = @mime_content_type($sniffFile) ?: 'image/jpeg';
        @unlink($sniffFile);

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $storagePath = 'covers/'.$assetId.'.'.$ext;

        try {
            Storage::disk($disk)->put($storagePath, $imageData);

            return $storagePath;
        } catch (\Exception $e) {
            \Log::error('DAM: cover art storage failed for asset '.$assetId.': '.$e->getMessage());

            return null;
        }
    }

    /**
     * Cleanup temporary files.
     */
    protected function cleanupTempFile(?string $tempPath, string $disk): void
    {
        if ($disk === 's3' && $tempPath && file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    /**
     * Standard error response format.
     */
    protected function errorResponse(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
