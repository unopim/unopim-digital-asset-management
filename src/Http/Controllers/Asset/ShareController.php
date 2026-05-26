<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\DataGrids\Share\ShareDataGrid;
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
        $name = $request->input('name');
        $name = is_string($name) ? trim($name) : null;
        $name = $name === '' ? null : $name;

        $share = $type === Share::TYPE_ASSET
            ? $this->shareRepository->createForAsset($targetId, $expiresAt, $userId, $name)
            : $this->shareRepository->createForDirectory($targetId, $expiresAt, $userId, $name);

        return response()->json([
            'success' => true,
            'share'   => $this->presentShare($share),
        ]);
    }

    /**
     * Update an existing share — currently allows editing the custom name and
     * the expiry (or removing it entirely via no_expiry=true).
     */
    public function update(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.share.not-found'),
            ], 404);
        }

        // Rename / re-expiry is bundled with the existing dam.shares.revoke
        // permission — anyone who can already manage a share's revoke state
        // can also change its label. Plus the standard per-target access check.
        if (! $this->canRevoke($share)) {
            return $this->unauthorized();
        }

        $data = request()->validate([
            'name'        => 'nullable|string|max:255',
            'no_expiry'   => 'sometimes|boolean',
            'expiry_days' => 'sometimes|nullable|integer|min:1|max:365',
        ]);

        $payload = [
            'name' => $data['name'] ?? null,
        ];

        if (request()->boolean('no_expiry')) {
            $payload['expires_at'] = null;
        } elseif (isset($data['expiry_days'])) {
            $payload['expires_at'] = now()->addDays((int) $data['expiry_days']);
        }

        $share->fill($payload)->save();

        return response()->json([
            'success' => true,
            'share'   => $this->presentShare($share->fresh()),
            'message' => trans('dam::app.admin.dam.share.updated'),
        ]);
    }

    /**
     * Reauthorize a previously revoked share — clears revoked_at so the same
     * token/URL becomes active again without generating a new link.
     */
    public function reauthorize(int $id): JsonResponse
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

        $reauthorized = $this->shareRepository->reauthorize($id);

        return response()->json([
            'success' => $reauthorized,
            'share'   => $reauthorized ? $this->presentShare($share->fresh()) : null,
            'message' => $reauthorized
                ? trans('dam::app.admin.dam.share.reauthorized')
                : trans('dam::app.admin.dam.share.not-revoked'),
        ]);
    }

    /**
     * Revoke a share (soft — sets revoked_at).
     */
    public function revoke(int $id): JsonResponse
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
     * Permanently delete a share record.
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

        if (! $this->canDelete($share)) {
            return $this->unauthorized();
        }

        $deleted = $this->shareRepository->hardDelete($id);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted
                ? trans('dam::app.admin.dam.share.deleted')
                : trans('dam::app.admin.dam.share.delete-failed'),
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

        // Return the most recent share for the target (any status: active, revoked,
        // expired) so the modal can offer reauthorize on a revoked share rather
        // than forcing the user to create a brand-new link.
        $shares = Share::query()
            ->where('share_type', $type)
            ->where('target_id', $targetId)
            ->orderByDesc('created_at')
            ->limit(1)
            ->get()
            ->map(fn (Share $s) => $this->presentShare($s));

        return response()->json([
            'success' => true,
            'shares'  => $shares,
        ]);
    }

    protected function presentShare(Share $share): array
    {
        return [
            'id'              => $share->id,
            'token'           => $share->token,
            'name'            => $share->name,
            'share_type'      => $share->share_type,
            'target_id'       => $share->target_id,
            'public_url'      => route('dam.share.show', ['token' => $share->token]),
            'expires_at'      => $share->expires_at?->toIso8601String(),
            'revoked_at'      => $share->revoked_at?->toIso8601String(),
            'view_count'      => $share->view_count,
            'download_count'  => $share->download_count,
            'status'          => $share->statusLabel(),
            'created_at'      => $share->created_at?->toIso8601String(),
            'update_url'      => route('admin.dam.shares.update', $share->id),
            'reauthorize_url' => route('admin.dam.shares.reauthorize', $share->id),
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

        return $this->hasTargetAccess($share);
    }

    protected function canDelete(Share $share): bool
    {
        if (! bouncer()->hasPermission('dam.shares.delete')) {
            return false;
        }

        return $this->hasTargetAccess($share);
    }

    protected function hasTargetAccess(Share $share): bool
    {
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
