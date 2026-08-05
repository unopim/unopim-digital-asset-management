<?php

namespace Webkul\DAM\Support;

/**
 * Hardened ZIP extraction for the asset bundle an import job may receive.
 *
 * Archive-level limits (entry count, total size, compression ratio) guard against
 * zip bombs; per-entry handling guards against zip-slip and oversized entries. What
 * counts as an acceptable file is left to the caller, so bundle ingestion can apply
 * the same file-type policy a UI upload does.
 *
 * This lives in the DAM rather than leaning on a core equivalent so the package runs
 * against a released UnoPim, where no such class exists.
 */
class SafeZipExtractor
{
    protected int $maxEntrySize;

    protected int $maxTotalSize;

    protected int $maxEntries;

    protected float $maxCompressionRatio;

    public function __construct(
        ?int $maxEntrySize = null,
        ?int $maxTotalSize = null,
        ?int $maxEntries = null,
        ?float $maxCompressionRatio = null,
    ) {
        $this->maxEntrySize = $maxEntrySize ?? (int) config('dam.import_bundle.max_entry_size', 524288000);
        $this->maxTotalSize = $maxTotalSize ?? (int) config('dam.import_bundle.max_total_size', 5368709120);
        $this->maxEntries = $maxEntries ?? (int) config('dam.import_bundle.max_entries', 50000);
        $this->maxCompressionRatio = $maxCompressionRatio ?? (float) config('dam.import_bundle.max_compression_ratio', 200);
    }

    /**
     * Inspect the archive before extracting anything.
     *
     * @return array{key: string, replace: array<string, mixed>}|null null when the archive is acceptable
     */
    public function rejectionReason(\ZipArchive $zip): ?array
    {
        if ($this->maxEntries > 0 && $zip->numFiles > $this->maxEntries) {
            return [
                'key'     => 'zip-too-many-entries',
                'replace' => ['count' => $zip->numFiles, 'limit' => $this->maxEntries],
            ];
        }

        $totalSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if ($stat === false) {
                return ['key' => 'invalid-zip', 'replace' => []];
            }

            $size = (int) ($stat['size'] ?? 0);
            $compressedSize = (int) ($stat['comp_size'] ?? 0);
            $totalSize += $size;

            if ($this->maxTotalSize > 0 && $totalSize > $this->maxTotalSize) {
                return [
                    'key'     => 'zip-contents-too-large',
                    'replace' => ['limit' => (int) round($this->maxTotalSize / 1048576)],
                ];
            }

            if (
                $this->maxCompressionRatio > 0
                && $size > 0
                && ($compressedSize === 0 || ($size / $compressedSize) > $this->maxCompressionRatio)
            ) {
                return [
                    'key'     => 'zip-compression-suspicious',
                    'replace' => ['entry' => (string) ($stat['name'] ?? '')],
                ];
            }
        }

        return null;
    }

    /**
     * Extract every acceptable entry beneath $extractPath.
     *
     * Entries are streamed to a temporary file rather than buffered, so a large asset
     * costs bounded memory. $accepts receives the staged file and decides whether it
     * is kept; returning false discards it and extraction continues.
     *
     * @param  callable(string, string, string): bool|null  $accepts  ($relativePath, $extension, $stagedPath)
     * @return list<string> relative paths actually extracted
     */
    public function extract(\ZipArchive $zip, string $extractPath, ?callable $accepts = null): array
    {
        if (! is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $realBase = realpath($extractPath);

        if ($realBase === false) {
            return [];
        }

        $extracted = [];
        $totalExtractedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $relativePath = $this->normaliseEntryName($zip->getNameIndex($index));

            if ($relativePath === null) {
                continue;
            }

            $stat = $zip->statIndex($index);

            if ($stat === false || ($this->maxEntrySize > 0 && (int) ($stat['size'] ?? 0) > $this->maxEntrySize)) {
                continue;
            }

            $targetPath = $extractPath.DIRECTORY_SEPARATOR.$relativePath;

            if (! $this->prepareTargetDirectory($targetPath, $realBase)) {
                continue;
            }

            $stagedPath = $this->stageEntry($zip, $relativePath, $targetPath);

            if ($stagedPath === null) {
                continue;
            }

            $actualSize = (int) filesize($stagedPath);
            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

            $withinTotal = $this->maxTotalSize <= 0 || ($totalExtractedBytes + $actualSize) <= $this->maxTotalSize;
            $accepted = $withinTotal && ($accepts === null || $accepts($relativePath, $extension, $stagedPath));

            if (! $accepted) {
                @unlink($stagedPath);

                continue;
            }

            rename($stagedPath, $targetPath);

            $extracted[] = $relativePath;
            $totalExtractedBytes += $actualSize;
        }

        return $extracted;
    }

    /**
     * Reject directories and any name that tries to climb out of the extraction root.
     */
    protected function normaliseEntryName(string|false $entryName): ?string
    {
        if ($entryName === false || str_ends_with($entryName, '/')) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $entryName), '/');

        if ($relativePath === '' || str_contains($relativePath, '../')) {
            return null;
        }

        return $relativePath;
    }

    /**
     * Create the entry's directory and confirm it resolves inside the extraction root,
     * which catches traversal achieved through symlinks rather than through the name.
     */
    protected function prepareTargetDirectory(string $targetPath, string $realBase): bool
    {
        $targetDir = dirname($targetPath);

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $realDir = realpath($targetDir);

        return $realDir !== false
            && str_starts_with($realDir.DIRECTORY_SEPARATOR, $realBase.DIRECTORY_SEPARATOR);
    }

    /**
     * Copy one entry to a sibling temp file, enforcing the per-entry byte cap while
     * streaming so a lying header cannot exhaust memory.
     */
    protected function stageEntry(\ZipArchive $zip, string $relativePath, string $targetPath): ?string
    {
        $stream = $zip->getStream($relativePath);

        if ($stream === false) {
            return null;
        }

        $stagedPath = $targetPath.'.part';
        $handle = fopen($stagedPath, 'wb');

        if ($handle === false) {
            fclose($stream);

            return null;
        }

        $written = 0;
        $exceeded = false;

        while (! feof($stream)) {
            $chunk = fread($stream, 1048576);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $written += strlen($chunk);

            if ($this->maxEntrySize > 0 && $written > $this->maxEntrySize) {
                $exceeded = true;

                break;
            }

            fwrite($handle, $chunk);
        }

        fclose($stream);
        fclose($handle);

        if ($exceeded) {
            @unlink($stagedPath);

            return null;
        }

        return $stagedPath;
    }
}
