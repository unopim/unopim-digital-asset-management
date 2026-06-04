<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Concerns;

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
            'Content-Disposition' => 'attachment; filename="'.str_replace(['"', "\r", "\n", "\0"], '', $zipName).'"',
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
