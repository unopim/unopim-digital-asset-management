<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Support\ThumbnailBinaries;

class GenerateVideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public function __construct(protected int $assetId) {}

    public function handle(): void
    {
        $asset = Asset::find($this->assetId);

        if (! $asset || $asset->file_type !== 'video') {
            return;
        }

        $disk = Directory::getAssetDisk();

        if (! Storage::disk($disk)->exists($asset->path)) {
            Log::warning('DAM video thumbnail: source file missing.', ['asset' => $asset->id, 'path' => $asset->path]);

            return;
        }

        $cachedRelative = 'thumbnails/'.$asset->path.'.jpg';

        $ext = $asset->extension ?: 'mp4';
        $tmpVideo = tempnam(sys_get_temp_dir(), 'damvid_').'.'.$ext;
        $tmpJpg = tempnam(sys_get_temp_dir(), 'damvid_thumb_').'.jpg';

        try {
            file_put_contents($tmpVideo, Storage::disk($disk)->get($asset->path));

            $process = new Process([
                ThumbnailBinaries::ffmpeg(),
                '-y',
                '-ss', '00:00:01',
                '-i', $tmpVideo,
                '-vframes', '1',
                '-vf', 'scale=300:-2',
                '-q:v', '4',
                $tmpJpg,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful() || ! file_exists($tmpJpg) || filesize($tmpJpg) === 0) {
                // Some videos are shorter than 1s — retry from the first frame.
                $retry = new Process([
                    ThumbnailBinaries::ffmpeg(),
                    '-y',
                    '-i', $tmpVideo,
                    '-vframes', '1',
                    '-vf', 'scale=300:-2',
                    '-q:v', '4',
                    $tmpJpg,
                ]);
                $retry->setTimeout(120);
                $retry->run();

                if (! $retry->isSuccessful() || ! file_exists($tmpJpg) || filesize($tmpJpg) === 0) {
                    Log::warning('DAM video thumbnail: ffmpeg failed.', [
                        'asset'  => $asset->id,
                        'stderr' => $process->getErrorOutput().$retry->getErrorOutput(),
                    ]);

                    return;
                }
            }

            Storage::disk($disk)->put($cachedRelative, file_get_contents($tmpJpg));

            $meta = is_array($asset->meta_data) ? $asset->meta_data : [];
            $meta['thumbnail_path'] = $cachedRelative;
            $asset->meta_data = $meta;
            $asset->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('DAM video thumbnail: exception while generating.', [
                'asset'   => $asset->id,
                'message' => $e->getMessage(),
            ]);
        } finally {
            @unlink($tmpVideo);
            @unlink($tmpJpg);
        }
    }
}
