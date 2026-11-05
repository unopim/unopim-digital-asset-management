<?php

declare(strict_types=1);

namespace Webkul\DAM\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webkul\DAM\Jobs\TagAssetWithAi;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\UploadBatch;
use Webkul\DAM\Models\UploadTracker;

class AiTaggingDispatcher
{
    public function __construct(
        protected AiTaggingJobTrackerService $jobTracker,
    ) {}

    /**
     * Queues AI tagging for the image assets and returns how many were queued; a multi-asset run shares one tracker.
     *
     * @param  Collection<int, Asset>  $assets
     */
    public function dispatch(Collection $assets, ?int $userId): int
    {
        $images = $assets->filter(fn (Asset $asset) => $asset->file_type === 'image')->values();

        if ($images->isEmpty()) {
            return 0;
        }

        $disk = Directory::getAssetDisk();

        if ($images->count() === 1) {
            TagAssetWithAi::dispatch($images->first()->id, $disk, userId: $userId);

            return 1;
        }

        $tracker = UploadTracker::create([
            'uuid'        => (string) Str::uuid(),
            'user_id'     => $userId,
            'state'       => UploadTracker::STATE_PROCESSING,
            'total_files' => $images->count(),
            'started_at'  => now(),
        ]);

        $jobTrack = $this->jobTracker->ensureSessionJob($tracker);

        foreach ($images as $asset) {
            $batch = UploadBatch::create([
                'upload_tracker_id' => $tracker->id,
                'asset_id'          => $asset->id,
                'state'             => UploadBatch::STATE_PENDING,
            ]);

            TagAssetWithAi::dispatch(
                $asset->id,
                $disk,
                $batch->id,
                userId: $userId,
                trackBatchId: $jobTrack ? $this->jobTracker->startBatch($jobTrack)->id : null,
            );
        }

        return $images->count();
    }
}
