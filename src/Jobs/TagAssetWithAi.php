<?php

declare(strict_types=1);

namespace Webkul\DAM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\AssetAutoTaggingService;

/**
 * Runs the AI tagging call on its own queue lifecycle, separate from
 * ProcessAssetUpload, so a slow/failed AI call can't delay metadata
 * extraction, thumbnail generation, or upload-batch completion.
 */
class TagAssetWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(
        protected int $assetId,
        protected string $disk,
    ) {}

    /**
     * @return array<int, RateLimited>
     */
    public function middleware(): array
    {
        return [new RateLimited('dam-ai-tagging')];
    }

    public function handle(AssetAutoTaggingService $service): void
    {
        $asset = Asset::find($this->assetId);

        if (! $asset) {
            return;
        }

        $service->tagAsset($asset, $this->disk);
    }
}
