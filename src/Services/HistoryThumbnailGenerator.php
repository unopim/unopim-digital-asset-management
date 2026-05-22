<?php

namespace Webkul\DAM\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Process;
use Webkul\DAM\Support\ThumbnailBinaries;

/**
 * Generate a thumbnail from a backed-up binary on a Laravel disk.
 *
 * Pure helper — never reads from or writes to Eloquent models, never references
 * the live asset's meta_data or path. Caller passes explicit source / dest
 * paths; this class owns disk I/O and the external process invocation.
 *
 * Every method returns bool (true on a written destination, false on any
 * failure) and logs failures at warning level. Failures never throw.
 */
class HistoryThumbnailGenerator
{
    public function fromImage(string $sourcePath, string $destPath, int $width, string $disk): bool
    {
        if (Storage::disk($disk)->missing($sourcePath)) {
            return false;
        }

        try {
            $manager = new ImageManager(new Driver);
            $jpeg = (string) $manager->read(Storage::disk($disk)->get($sourcePath))
                ->scale(width: $width)
                ->toJpeg(quality: 80);

            Storage::disk($disk)->put($destPath, $jpeg);

            return true;
        } catch (\Throwable $e) {
            Log::warning('DAM history thumbnail: image resize failed.', [
                'source'  => $sourcePath,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function fromSvg(string $sourcePath, string $destPath, string $disk): bool
    {
        if (Storage::disk($disk)->missing($sourcePath)) {
            return false;
        }

        try {
            Storage::disk($disk)->copy($sourcePath, $destPath);

            return true;
        } catch (\Throwable $e) {
            Log::warning('DAM history thumbnail: svg copy failed.', [
                'source'  => $sourcePath,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function fromVideo(string $sourcePath, string $destPath, int $width, string $disk): bool
    {
        return $this->runProcessThumbnail(
            sourcePath: $sourcePath,
            destPath: $destPath,
            width: $width,
            disk: $disk,
            tmpExt: 'mp4',
            buildCmd: fn (string $tmpSource, string $tmpJpg) => new Process([
                ThumbnailBinaries::ffmpeg(), '-y',
                '-ss', '00:00:01',
                '-i', $tmpSource,
                '-vframes', '1',
                '-vf', 'scale='.$width.':-2',
                '-q:v', '4',
                $tmpJpg,
            ]),
            retryCmd: fn (string $tmpSource, string $tmpJpg) => new Process([
                ThumbnailBinaries::ffmpeg(), '-y',
                '-i', $tmpSource,
                '-vframes', '1',
                '-vf', 'scale='.$width.':-2',
                '-q:v', '4',
                $tmpJpg,
            ]),
            kind: 'video',
        );
    }

    public function fromPdf(string $sourcePath, string $destPath, int $width, string $disk): bool
    {
        if (Storage::disk($disk)->missing($sourcePath)) {
            return false;
        }

        @set_time_limit(0);

        $tmpPdf = tempnam(sys_get_temp_dir(), 'damhistpdf_').'.pdf';
        $tmpPrefix = tempnam(sys_get_temp_dir(), 'damhistpdf_thumb_');
        $tmpPng = $tmpPrefix.'.png';

        try {
            // Stream-copy to avoid loading multi-MB PDFs entirely into memory.
            $sourceStream = Storage::disk($disk)->readStream($sourcePath);
            if ($sourceStream === false || $sourceStream === null) {
                return false;
            }
            $destStream = fopen($tmpPdf, 'wb');
            stream_copy_to_stream($sourceStream, $destStream);
            fclose($sourceStream);
            fclose($destStream);

            $process = new Process([
                ThumbnailBinaries::pdftoppm(), '-png',
                '-f', '1', '-l', '1',
                '-r', '80',
                '-singlefile',
                $tmpPdf,
                $tmpPrefix,
            ]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! file_exists($tmpPng) || filesize($tmpPng) === 0) {
                Log::warning('DAM history thumbnail: pdftoppm failed.', [
                    'source' => $sourcePath,
                    'stderr' => $process->getErrorOutput(),
                ]);

                return false;
            }

            $manager = new ImageManager(new Driver);
            $jpeg = (string) $manager->read(file_get_contents($tmpPng))
                ->scale(width: $width)
                ->toJpeg(quality: 80);

            Storage::disk($disk)->put($destPath, $jpeg);

            return true;
        } catch (\Throwable $e) {
            Log::warning('DAM history thumbnail: pdf exception.', [
                'source'  => $sourcePath,
                'message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            @unlink($tmpPdf);
            @unlink($tmpPng);
            @unlink($tmpPrefix);
        }
    }

    /**
     * Shared scaffold for video thumbnailing: temp-copy the source binary,
     * run a primary process, retry with an alternate command if the primary
     * failed, then persist the resulting JPG.
     */
    private function runProcessThumbnail(
        string $sourcePath,
        string $destPath,
        int $width,
        string $disk,
        string $tmpExt,
        \Closure $buildCmd,
        \Closure $retryCmd,
        string $kind,
    ): bool {
        if (Storage::disk($disk)->missing($sourcePath)) {
            return false;
        }

        // Large videos (>30 MB) would otherwise hit PHP's default web-request
        // time limit on `artisan serve` before ffmpeg returns. The Symfony
        // Process has its own timeout; clearing PHP's leaves ffmpeg to finish.
        @set_time_limit(0);

        $tmpSource = tempnam(sys_get_temp_dir(), 'damhist'.$kind.'_').'.'.$tmpExt;
        $tmpJpg = tempnam(sys_get_temp_dir(), 'damhist'.$kind.'_thumb_').'.jpg';

        try {
            // Stream-copy from the disk to the tmp file so we don't load the
            // entire binary into PHP memory (48 MB+ videos would otherwise
            // pressure memory_limit on shared hosts).
            $sourceStream = Storage::disk($disk)->readStream($sourcePath);
            if ($sourceStream === false || $sourceStream === null) {
                return false;
            }
            $destStream = fopen($tmpSource, 'wb');
            stream_copy_to_stream($sourceStream, $destStream);
            fclose($sourceStream);
            fclose($destStream);

            $process = $buildCmd($tmpSource, $tmpJpg);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! file_exists($tmpJpg) || filesize($tmpJpg) === 0) {
                $retry = $retryCmd($tmpSource, $tmpJpg);
                $retry->setTimeout(120);
                $retry->run();

                if (! $retry->isSuccessful() || ! file_exists($tmpJpg) || filesize($tmpJpg) === 0) {
                    Log::warning('DAM history thumbnail: '.$kind.' process failed.', [
                        'source' => $sourcePath,
                        'stderr' => $process->getErrorOutput().$retry->getErrorOutput(),
                    ]);

                    return false;
                }
            }

            Storage::disk($disk)->put($destPath, file_get_contents($tmpJpg));

            return true;
        } catch (\Throwable $e) {
            Log::warning('DAM history thumbnail: '.$kind.' exception.', [
                'source'  => $sourcePath,
                'message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            @unlink($tmpSource);
            @unlink($tmpJpg);
        }
    }
}
