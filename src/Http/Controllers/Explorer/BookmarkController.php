<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\Explorer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Webkul\DAM\Models\ExplorerBookmark;
use Webkul\DAM\Services\DirectoryPermissionService;

class BookmarkController extends Controller
{
    public function __construct(
        protected DirectoryPermissionService $permissionService
    ) {}

    protected function authorizeBookmarks(): void
    {
        abort_unless(
            bouncer()->hasPermission('dam.asset.view'),
            403,
            trans('dam::app.admin.permissions.unauthorized')
        );
    }

    public function index(): JsonResponse
    {
        $this->authorizeBookmarks();

        $bookmarks = ExplorerBookmark::where('user_id', Auth::id())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'directory_id', 'name']);

        return response()->json($bookmarks);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeBookmarks();

        $request->validate([
            'directory_id' => 'required|integer|exists:dam_directories,id',
            'name'         => 'required|string|max:255',
        ]);

        $userId = Auth::id();
        $directoryId = $request->integer('directory_id');

        if (! $this->permissionService->bypass() && ! $this->permissionService->canView($directoryId)) {
            return response()->json(
                ['message' => trans('dam::app.admin.explorer.access-denied')],
                403
            );
        }

        $existing = ExplorerBookmark::where('user_id', $userId)
            ->where('directory_id', $directoryId)
            ->first();

        if ($existing) {
            return response()->json([
                'id'           => $existing->id,
                'directory_id' => $existing->directory_id,
                'name'         => $existing->name,
            ], 200);
        }

        if (ExplorerBookmark::where('user_id', $userId)->count() >= 20) {
            return response()->json(
                ['message' => trans('dam::app.admin.explorer.bookmarks.max')],
                422
            );
        }

        $bookmark = ExplorerBookmark::create([
            'user_id'      => $userId,
            'directory_id' => $directoryId,
            'name'         => $request->string('name')->toString(),
            'sort_order'   => 0,
        ]);

        return response()->json([
            'id'           => $bookmark->id,
            'directory_id' => $bookmark->directory_id,
            'name'         => $bookmark->name,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorizeBookmarks();

        ExplorerBookmark::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(null, 204);
    }
}
