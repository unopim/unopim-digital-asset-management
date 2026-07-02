<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use ZipStream\CompressionMethod;
use ZipStream\OperationMode;
use ZipStream\ZipStream;

trait StreamsZipDownload
{
    /**
     * Stream a directory's files as a ZIP archive directly to the browser.
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
     * Stream assets from a DB cursor as a ZIP archive.
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

            foreach ($query->lazy(500) as $asset) {
                $path = $asset->path;

                try {
                    if ($disk === Directory::ASSETS_DISK_AWS) {
                        $stream = Storage::disk($disk)->readStream($path);

                        if (! $stream) {
                            $path = $this->derivePath($asset) ?? $path;
                            $stream = Storage::disk($disk)->readStream($path);
                        }

                        if ($stream) {
                            $entryName = str_starts_with($path, $parentBase)
                                ? substr($path, strlen($parentBase))
                                : ($asset->file_name ?: basename($path));
                            $zip->addFileFromStream(fileName: $entryName, stream: $stream);
                        }
                    } else {
                        $fullPath = Storage::disk($disk)->path($path);

                        if (! file_exists($fullPath)) {
                            $derived = $this->derivePath($asset);
                            if ($derived) {
                                $path = $derived;
                                $fullPath = Storage::disk($disk)->path($path);
                            }
                        }

                        if (file_exists($fullPath)) {
                            $entryName = str_starts_with($path, $parentBase)
                                ? substr($path, strlen($parentBase))
                                : ($asset->file_name ?: basename($path));
                            $zip->addFileFromPath(fileName: $entryName, path: $fullPath);
                        }
                    }
                } catch (\Throwable) {
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
     * Derive the current storage path from the asset's immediate parent directory.
     */
    private function derivePath(Asset $asset): ?string
    {
        $directory = $asset->relationLoaded('directories')
            ? $asset->directories->first()
            : null;

        if (! $directory) {
            return null;
        }

        return sprintf('%s/%s/%s',
            Directory::ASSETS_DIRECTORY,
            $directory->generatePath(),
            $asset->file_name,
        );
    }

    /**
     * Predict the final ZIP byte-count without reading file content.
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
