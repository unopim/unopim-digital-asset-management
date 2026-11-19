<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Services\AiTaggingDispatcher;
use Webkul\DAM\Services\AssetAutoTaggingService;

/**
 * Shared by the admin mass action and the REST API; requires the AssetAccessControl trait.
 */
trait QueuesAiTagging
{
    /**
     * Queues AI tagging for the accessible image assets among $assetIds.
     *
     * @param  array<int, int|string>  $assetIds
     */
    protected function queueAiTagging(array $assetIds, ?int $userId): JsonResponse
    {
        if (! resolve(AssetAutoTaggingService::class)->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.tag.mass-action.ai-tag-unavailable'),
            ], 422);
        }

        $assetIds = array_values(array_unique(array_map(intval(...), $assetIds)));

        $assets = Asset::whereIn('id', $this->damAccessibleAssetIds($assetIds))
            ->get(['id', 'file_type']);

        $queued = resolve(AiTaggingDispatcher::class)->dispatch($assets, $userId);

        if ($queued === 0) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.tag.mass-action.ai-tag-none'),
            ], 422);
        }

        $skipped = count($assetIds) - $queued;

        return response()->json([
            'success' => true,
            'queued'  => $queued,
            'skipped' => $skipped,
            'message' => $skipped > 0
                ? trans('dam::app.admin.dam.tag.mass-action.ai-tag-queued-skipped', ['count' => $queued, 'skipped' => $skipped])
                : trans('dam::app.admin.dam.tag.mass-action.ai-tag-queued', ['count' => $queued]),
        ]);
    }
}
