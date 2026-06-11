<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\DAM\Models\Directory;
use ZipStream\CompressionMethod;
use ZipStream\OperationMode;
use ZipStream\ZipStream;

trait StreamsZipDownload
{
    /**
     * Stream a directory's files as a ZIP archive directly to the browser.
     *
     * Uses SIMULATE_STRICT to predict Content-Length before streaming,
     * so the browser can show accurate download progress.
     *
     * @param  string[]  $files  Paths from Storage::allFiles()
     */
    protected function buildZipStreamResponse(
        array $files,
        string $folderPath,
        string $disk,
        string $zipName,
    ): StreamedResponse {
        $zipSize = $this->simulateZipSize($files, $folderPath, $disk);

        $headers = [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.addslashes($zipName).'"',
            'Cache-Control'       => 'no-store',
        ];

        if ($zipSize !== null) {
            $headers['Content-Length'] = $zipSize;
        }

        return response()->stream(function () use ($files, $folderPath, $disk): void {
            $outputStream = fopen('php://output', 'wb');

            $zip = new ZipStream(
                outputStream: $outputStream,
                sendHttpHeaders: false,
                defaultCompressionMethod: CompressionMethod::STORE,
                flushOutput: true,
            );

            foreach ($files as $file) {
                if (! Storage::disk($disk)->exists($file)) {
                    continue;
                }

                $relativePath = str_replace(dirname($folderPath).'/', '', $file);

                if ($disk === Directory::ASSETS_DISK_AWS) {
                    $stream = Storage::disk($disk)->readStream($file);
                    $zip->addFileFromStream(fileName: $relativePath, stream: $stream);
                } else {
                    $zip->addFileFromPath(fileName: $relativePath, path: Storage::disk($disk)->path($file));
                }
            }

            $zip->finish();
        }, Response::HTTP_OK, $headers);
    }

    /**
     * Stream assets from a DB cursor as a ZIP — no allFiles() scan, no pre-size simulation.
     * Starts streaming immediately; uses set_time_limit(0) inside the closure so large
     * directories are not cut off by PHP's max_execution_time.
     */
    protected function buildZipStreamFromAssets(
        Builder $query,
        string $folderBase,
        string $disk,
        string $zipName,
    ): StreamedResponse {
        $parentBase = dirname($folderBase).'/';

        return response()->stream(function () use ($query, $parentBase, $disk): void {
            set_time_limit(0);

            $outputStream = fopen('php://output', 'wb');

            $zip = new ZipStream(
                outputStream: $outputStream,
                sendHttpHeaders: false,
                defaultCompressionMethod: CompressionMethod::STORE,
                flushOutput: true,
            );

            foreach ($query->cursor() as $asset) {
                $path = $asset->path;

                $entryName = str_starts_with($path, $parentBase)
                    ? substr($path, strlen($parentBase))
                    : ($asset->file_name ?: basename($path));

                try {
                    if ($disk === Directory::ASSETS_DISK_AWS) {
                        $stream = Storage::disk($disk)->readStream($path);
                        if ($stream) {
                            $zip->addFileFromStream(fileName: $entryName, stream: $stream);
                        }
                    } else {
                        $fullPath = Storage::disk($disk)->path($path);
                        if (file_exists($fullPath)) {
                            $zip->addFileFromPath(fileName: $entryName, path: $fullPath);
                        }
                    }
                } catch (\Throwable) {
                    // Skip unreadable/missing files rather than aborting the archive
                }
            }

            $zip->finish();
        }, Response::HTTP_OK, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.addslashes($zipName).'"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    /**
     * Predict the final ZIP byte-count using SIMULATE_STRICT + STORE compression.
     * No file content is read — only file sizes are needed.
     * Returns null on any failure so the caller can stream without Content-Length.
     */
    private function simulateZipSize(array $files, string $folderPath, string $disk): ?int
    {
        try {
            $sink = fopen('php://temp', 'wb');

            $zip = new ZipStream(
                operationMode: OperationMode::SIMULATE_STRICT,
                outputStream: $sink,
                sendHttpHeaders: false,
                defaultCompressionMethod: CompressionMethod::STORE,
            );

            foreach ($files as $file) {
                if (! Storage::disk($disk)->exists($file)) {
                    continue;
                }

                $relativePath = str_replace(dirname($folderPath).'/', '', $file);

                $exactSize = $disk === Directory::ASSETS_DISK_AWS
                    ? Storage::disk($disk)->size($file)
                    : filesize(Storage::disk($disk)->path($file));

                $zip->addFileFromCallback(
                    fileName: $relativePath,
                    callback: static fn () => fopen('php://temp', 'rb'),
                    compressionMethod: CompressionMethod::STORE,
                    exactSize: max(0, (int) $exactSize),
                );
            }

            return $zip->finish();
        } catch (\Throwable) {
            return null;
        }
    }
}
