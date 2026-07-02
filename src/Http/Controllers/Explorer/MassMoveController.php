<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Explorer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\DAM\Enums\EventType;
use Webkul\DAM\Jobs\MassMove as MassMoveJob;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\ActionRequest as ActionRequestTrait;
use Webkul\DAM\Traits\AssetAccessControl;

class MassMoveController extends Controller
{
    use ActionRequestTrait, AssetAccessControl;

    /** Create a new instance. */
    public function __construct(
        protected DirectoryPermissionService $permissionService
    ) {}

    /**
     * Validate access and queue a job to move the selected assets and directories.
     */
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'asset_ids'           => 'nullable|array',
            'asset_ids.*'         => 'integer',
            'directory_ids'       => 'nullable|array',
            'directory_ids.*'     => 'integer',
            'target_directory_id' => 'required|integer|exists:dam_directories,id',
        ]);

        $targetId = $request->integer('target_directory_id');

        if (! $this->permissionService->bypass() && ! $this->permissionService->canAccess($targetId)) {
            return new JsonResponse(['message' => trans('dam::app.admin.permissions.unauthorized')], 403);
        }

        $assetIds = array_filter(
            array_map('intval', $request->input('asset_ids', [])),
            fn (int $id) => $this->damCanAccessAsset($id)
        );

        $targetDirectory = Directory::findOrFail($targetId);

        $dirIds = array_filter(
            array_map('intval', $request->input('directory_ids', [])),
            function (int $id) use ($targetId, $targetDirectory) {
                if (! $this->permissionService->bypass() && ! $this->permissionService->canAccess($id)) {
                    return false;
                }

                $dir = Directory::find($id);

                if (! $dir || ! $dir->isDeletable()) {
                    return false;
                }

                if ($id === $targetId || $dir->isAncestorOf($targetDirectory)) {
                    return false;
                }

                return true;
            }
        );

        $requestAction = $this->start(EventType::MASS_MOVE->value);

        MassMoveJob::dispatch(
            array_values($assetIds),
            array_values($dirIds),
            $targetId,
            $requestAction->getUser()->id
        );

        return new JsonResponse(['queued' => true]);
    }
}
