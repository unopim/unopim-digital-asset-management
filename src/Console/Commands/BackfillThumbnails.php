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

        $videos = Asset::where('file_type', 'video')->get();
        $pdfs = Asset::whereRaw('LOWER(extension) = ?', ['pdf'])->get();

        $this->info("Found {$videos->count()} videos and {$pdfs->count()} PDFs.");

        $skipped = 0;
        $queued = 0;

        foreach ($videos as $asset) {
            if (! empty(($asset->meta_data['thumbnail_path'] ?? null))) {
                $skipped++;

                continue;
            }
            $sync
                ? (new GenerateVideoThumbnail($asset->id))->handle()
                : GenerateVideoThumbnail::dispatch($asset->id);
            $queued++;
        }

        foreach ($pdfs as $asset) {
            if (! empty(($asset->meta_data['thumbnail_path'] ?? null))) {
                $skipped++;

                continue;
            }
            $sync
                ? (new GeneratePdfThumbnail($asset->id))->handle()
                : GeneratePdfThumbnail::dispatch($asset->id);
            $queued++;
        }

        $action = $sync ? 'processed' : 'queued';
        $this->info("Done. {$queued} {$action}, {$skipped} skipped (already had thumbnails).");

        if (! $sync && $queued > 0) {
            $this->line('Make sure a queue worker is running (e.g. `php artisan queue:work`).');
        }

        return self::SUCCESS;
    }
}
