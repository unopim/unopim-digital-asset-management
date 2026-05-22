<?php

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Process;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Support\ThumbnailBinaries;

class GeneratePdfThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(protected int $assetId) {}

    public function handle(): void
    {
        $asset = Asset::find($this->assetId);

        if (! $asset || $asset->extension !== 'pdf') {
            return;
        }

        $disk = Directory::getAssetDisk();

        if (! Storage::disk($disk)->exists($asset->path)) {
            Log::warning('DAM PDF thumbnail: source file missing.', ['asset' => $asset->id, 'path' => $asset->path]);

            return;
        }

        $cachedRelative = 'thumbnails/'.$asset->path.'.jpg';

        $tmpPdf = tempnam(sys_get_temp_dir(), 'dampdf_').'.pdf';
        $tmpPrefix = tempnam(sys_get_temp_dir(), 'dampdf_thumb_');
        $tmpPng = $tmpPrefix.'.png';

        try {
            file_put_contents($tmpPdf, Storage::disk($disk)->get($asset->path));

            $process = new Process([
                ThumbnailBinaries::pdftoppm(),
                '-png',
                '-f', '1',
                '-l', '1',
                '-r', '80',
                '-singlefile',
                $tmpPdf,
                $tmpPrefix,
            ]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! file_exists($tmpPng)) {
                Log::warning('DAM PDF thumbnail: pdftoppm failed.', [
                    'asset'  => $asset->id,
                    'stderr' => $process->getErrorOutput(),
                ]);

                return;
            }

            $manager = new ImageManager(new Driver);
            $jpegData = (string) $manager->read(file_get_contents($tmpPng))
                ->scale(width: 300)
                ->toJpeg(quality: 80);

            Storage::disk($disk)->put($cachedRelative, $jpegData);

            $meta = is_array($asset->meta_data) ? $asset->meta_data : [];
            $meta['thumbnail_path'] = $cachedRelative;
            $asset->meta_data = $meta;
            $asset->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('DAM PDF thumbnail: exception while generating.', [
                'asset'   => $asset->id,
                'message' => $e->getMessage(),
            ]);
        } finally {
            @unlink($tmpPdf);
            @unlink($tmpPng);
            @unlink($tmpPrefix);
        }
    }
}
