<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\ApiDataSource\ShareDataSource;
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

    public function index(): JsonResponse
    {
        return app(ShareDataSource::class)->toJson();
    }

    public function show(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return $this->notFound(trans('dam::app.admin.dam.share.not-found'));
        }

        if (! $this->hasTargetAccess($share)) {
            return $this->unauthorized();
        }

        return response()->json([
            'success' => true,
            'data'    => $this->presentShare($share),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'share_type'   => 'required|in:'.Share::TYPE_ASSET.','.Share::TYPE_DIRECTORY,
            'asset_id'     => 'required_if:share_type,'.Share::TYPE_ASSET.'|integer|min:1',
            'directory_id' => 'required_if:share_type,'.Share::TYPE_DIRECTORY.'|integer|min:1',
            'expiry_days'  => 'nullable|integer|min:1|max:365',
            'no_expiry'    => 'nullable|boolean',
            'name'         => 'nullable|string|max:255',
        ]);

        $type = $request->input('share_type');
        $targetId = (int) ($type === Share::TYPE_ASSET
            ? $request->input('asset_id')
            : $request->input('directory_id'));

        if ($type === Share::TYPE_ASSET) {
            $asset = Asset::find($targetId);

            if (! $asset) {
                return $this->notFound(trans('dam::app.admin.dam.share.target-not-found'));
            }

            if (! $this->canActOnAsset($asset)) {
                return $this->unauthorized();
            }
        } else {
            $directory = Directory::find($targetId);

            if (! $directory) {
                return $this->notFound(trans('dam::app.admin.dam.share.target-not-found'));
            }

            if (! $this->canAccessDirectory($targetId)) {
                return $this->unauthorized();
            }
        }

        $expiresAt = null;

        if (! $request->boolean('no_expiry')) {
            $days = (int) ($request->input('expiry_days') ?? 7);
            $days = max(1, min(365, $days));
            $expiresAt = now()->addDays($days);
        }

        $userId = auth()->guard('api')->id() ?? auth()->id();

        $name = $request->input('name');
        $name = is_string($name) ? trim($name) : null;
        $name = $name === '' ? null : $name;

        $share = $type === Share::TYPE_ASSET
            ? $this->shareRepository->createForAsset($targetId, $expiresAt, $userId, $name)
            : $this->shareRepository->createForDirectory($targetId, $expiresAt, $userId, $name);

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.share.modal.created'),
            'data'    => $this->presentShare($share),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return $this->notFound(trans('dam::app.admin.dam.share.not-found'));
        }

        if (! $this->hasTargetAccess($share)) {
            return $this->unauthorized();
        }

        $data = $request->validate([
            'name'        => 'sometimes|nullable|string|max:255',
            'no_expiry'   => 'sometimes|boolean',
            'expiry_days' => 'sometimes|nullable|integer|min:1|max:365',
        ]);

        $payload = [];

        if ($request->has('name')) {
            $name = is_string($data['name'] ?? null) ? trim($data['name']) : null;
            $payload['name'] = $name === '' ? null : $name;
        }

        if ($request->boolean('no_expiry')) {
            $payload['expires_at'] = null;
        } elseif (! empty($data['expiry_days'])) {
            $payload['expires_at'] = now()->addDays((int) $data['expiry_days']);
        }

        if (! empty($payload)) {
            $share->fill($payload)->save();
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.share.updated'),
            'data'    => $this->presentShare($share->fresh()),
        ]);
    }

    public function revoke(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return $this->notFound(trans('dam::app.admin.dam.share.not-found'));
        }

        if (! $this->hasTargetAccess($share)) {
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

    public function reauthorize(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return $this->notFound(trans('dam::app.admin.dam.share.not-found'));
        }

        if (! $this->hasTargetAccess($share)) {
            return $this->unauthorized();
        }

        $reauthorized = $this->shareRepository->reauthorize($id);

        return response()->json([
            'success' => $reauthorized,
            'message' => $reauthorized
                ? trans('dam::app.admin.dam.share.reauthorized')
                : trans('dam::app.admin.dam.share.not-revoked'),
            'data'    => $reauthorized ? $this->presentShare($share->fresh()) : null,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $share = $this->shareRepository->find($id);

        if (! $share) {
            return $this->notFound(trans('dam::app.admin.dam.share.not-found'));
        }

        if (! $this->hasTargetAccess($share)) {
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

    protected function presentShare(Share $share): array
    {
        return [
            'id'             => $share->id,
            'token'          => $share->token,
            'name'           => $share->name,
            'share_type'     => $share->share_type,
            'target_id'      => $share->target_id,
            'public_url'     => route('dam.share.show', ['token' => $share->token]),
            'expires_at'     => $share->expires_at?->toIso8601String(),
            'revoked_at'     => $share->revoked_at?->toIso8601String(),
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

    protected function notFound(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    protected function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => trans('dam::app.admin.permissions.unauthorized'),
        ], 403);
    }
}
