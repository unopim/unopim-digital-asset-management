<?php

namespace Webkul\DAM\Console\Commands;

use Illuminate\Console\Command;
use Webkul\DAM\Jobs\GeneratePdfThumbnail;
use Webkul\DAM\Jobs\GenerateVideoThumbnail;
use Webkul\DAM\Models\Asset;

class BackfillThumbnails extends Command
{
    protected $signature = 'dam:backfill-thumbnails {--sync : Run jobs synchronously instead of dispatching to the queue}';

    protected $description = 'Generate real thumbnails (first PDF page / first video frame) for existing DAM assets that are still showing placeholder icons.';

    public function handle(): int
    {
        $sync = (bool) $this->option('sync');

        // COUNT queries only — avoids loading asset records into memory upfront.
        $videoCount = Asset::where('file_type', 'video')->count();
        $pdfCount = Asset::whereRaw('LOWER(extension) = ?', ['pdf'])->count();

        $this->info("Found {$videoCount} videos and {$pdfCount} PDFs.");

        $skipped = 0;
        $queued = 0;

        // lazy() chunks through the table (1000 rows at a time) and yields one
        // model at a time — constant memory regardless of asset count.
        // select() limits columns to what the loop actually needs.
        Asset::where('file_type', 'video')
            ->select(['id', 'meta_data'])
            ->lazy()
            ->each(function (Asset $asset) use ($sync, &$skipped, &$queued) {
                if (! empty($asset->meta_data['thumbnail_path'] ?? null)) {
                    $skipped++;

                    return;
                }
                $sync
                    ? (new GenerateVideoThumbnail($asset->id))->handle()
                    : GenerateVideoThumbnail::dispatch($asset->id);
                $queued++;
            });

        Asset::whereRaw('LOWER(extension) = ?', ['pdf'])
            ->select(['id', 'meta_data'])
            ->lazy()
            ->each(function (Asset $asset) use ($sync, &$skipped, &$queued) {
                if (! empty($asset->meta_data['thumbnail_path'] ?? null)) {
                    $skipped++;

                    return;
                }
                $sync
                    ? (new GeneratePdfThumbnail($asset->id))->handle()
                    : GeneratePdfThumbnail::dispatch($asset->id);
                $queued++;
            });

        $action = $sync ? 'processed' : 'queued';
        $this->info("Done. {$queued} {$action}, {$skipped} skipped (already had thumbnails).");

        if (! $sync && $queued > 0) {
            $this->line('Make sure a queue worker is running (e.g. `php artisan queue:work`).');
        }

        return self::SUCCESS;
    }
}
