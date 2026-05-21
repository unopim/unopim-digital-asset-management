<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\DataGrids\Share\ShareDataGrid;
use Webkul\DAM\Exceptions\ShareAlreadyActiveException;
use Webkul\DAM\Exceptions\ShareNeedsEnableException;
use Webkul\DAM\Exceptions\ShareNotEnableableException;
use Webkul\DAM\Http\Requests\StoreShareRequest;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Share;
use Webkul\DAM\Repositories\ShareRepository;
use Webkul\DAM\Services\DirectoryPermissionService;

class ShareController extends Controller
{
    public function __construct(
        protected ShareRepository $shareRepository,
        protected DirectoryPermissionService $permissionService,
    ) {}

    /**
     * Listing of all shares for the management page.
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ShareDataGrid::class)->toJson();
        }

        return view('dam::share.admin.index');
    }

    /**
     * Create a new share for an asset or directory.
     */
    public function store(StoreShareRequest $request): JsonResponse
    {
        $type = $request->input('share_type');
        $targetId = (int) $request->input('target_id');

        if ($type === Share::TYPE_ASSET) {
            $asset = Asset::find($targetId);
            if (! $asset) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.share.target-not-found'),
                ], 404);
            }

            if (! bouncer()->hasPermission('dam.asset.share') || ! $this->canActOnAsset($asset)) {
                return $this->unauthorized();
            }
        } else {
            $directory = Directory::find($targetId);
            if (! $directory) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.share.target-not-found'),
                ], 404);
            }

            if (! bouncer()->hasPermission('dam.directory.share') || ! $this->canAccessDirectory($targetId)) {
                return $this->unauthorized();
            }
        }

        $expiresAt = null;
        if (! $request->boolean('no_expiry')) {
            $days = (int) ($request->input('expiry_days') ?? 7);
            $days = max(1, min(365, $days));
            $expiresAt = now()->addDays($days);
        }

        $userId = auth()->guard('admin')->id() ?? auth()->id();

        try {
            $share = $type === Share::TYPE_ASSET
                ? $this->shareRepository->createForAsset($targetId, $expiresAt, $userId)
                : $this->shareRepository->createForDirectory($targetId, $expiresAt, $userId);
        } catch (ShareAlreadyActiveException) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.share.already-active'),
            ], 409);
        } catch (ShareNeedsEnableException) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.share.needs-enable'),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'share'   => $this->presentShare($share),
        ]);
    }

    /**
     * Re-enable a previously revoked share. Keeps the same token & URL.
     * Accepts an updated expiry from the modal so the user can refresh it
     * at the moment of re-enabling.
     */
    public function enable(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.share.not-found'),
            ], 404);
        }

        if (! $this->canRevoke($share)) {
            return $this->unauthorized();
        }

        $expiresAt = null;
        if (! request()->boolean('no_expiry')) {
            $days = (int) (request()->input('expiry_days') ?? 7);
            $days = max(1, min(365, $days));
            $expiresAt = now()->addDays($days);
        }

        try {
            $share = $this->shareRepository->enable($id, $expiresAt);
        } catch (ShareNotEnableableException $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.share.not-found'),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.share.enabled'),
            'share'   => $this->presentShare($share),
        ]);
    }

    /**
     * Revoke a share.
     */
    public function destroy(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.share.not-found'),
            ], 404);
        }

        if (! $this->canRevoke($share)) {
            return $this->unauthorized();
        }

        $revoked = $this->shareRepository->revoke($id);

        return response()->json([
            'success' => $revoked,
            'message' => $revoked
                ? trans('dam::app.admin.dam.share.revoked')
                : trans('dam::app.admin.dam.share.already-revoked'),
        ]);
    }

    /**
     * Return all active shares for a given target. Used by the share modal
     * to render the "Active links" list inline on asset / directory edit pages.
     */
    public function activeForTarget(string $type, int $targetId): JsonResponse
    {
        if (! in_array($type, [Share::TYPE_ASSET, Share::TYPE_DIRECTORY], true)) {
            return response()->json(['success' => false], 422);
        }

        if ($type === Share::TYPE_ASSET) {
            $asset = Asset::find($targetId);
            if (! $asset || ! $this->canActOnAsset($asset)) {
                return $this->unauthorized();
            }
        } else {
            if (! Directory::find($targetId) || ! $this->canAccessDirectory($targetId)) {
                return $this->unauthorized();
            }
        }

        $share = Share::query()
            ->where('share_type', $type)
            ->where('target_id', $targetId)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'share'   => $share ? $this->presentShare($share) : null,
        ]);
    }

    protected function presentShare(Share $share): array
    {
        return [
            'id'             => $share->id,
            'token'          => $share->token,
            'share_type'     => $share->share_type,
            'target_id'      => $share->target_id,
            'public_url'     => route('dam.share.show', ['token' => $share->token]),
            'expires_at'     => $share->expires_at?->toIso8601String(),
            'revoked_at'     => $share->revoked_at?->toIso8601String(),
            'is_active'      => (bool) $share->is_active,
            'can_be_enabled' => $share->canBeEnabled(),
            'view_count'     => $share->view_count,
            'download_count' => $share->download_count,
            'status'         => $share->statusLabel(),
            'created_at'     => $share->created_at?->toIso8601String(),
        ];
    }

    protected function canActOnAsset(Asset $asset): bool
    {
        if ($this->permissionService->bypass()) {
            return true;
        }

        $dirId = (int) ($asset->directories()->value('dam_directories.id') ?? 0);

        return $dirId !== 0 && $this->permissionService->canAccess($dirId);
    }

    protected function canAccessDirectory(int $directoryId): bool
    {
        if ($this->permissionService->bypass()) {
            return true;
        }

        return $this->permissionService->canAccess($directoryId);
    }

    protected function canRevoke(Share $share): bool
    {
        if (! bouncer()->hasPermission('dam.shares.revoke')) {
            return false;
        }

        if ($this->permissionService->bypass()) {
            return true;
        }

        if ($share->share_type === Share::TYPE_ASSET) {
            $asset = $share->asset;

            return $asset !== null && $this->canActOnAsset($asset);
        }

        return $this->canAccessDirectory($share->target_id);
    }

    protected function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => trans('dam::app.admin.permissions.unauthorized'),
        ], 403);
    }
}
