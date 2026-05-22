<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\DAM\DataGrids\Asset\AssetHistoryDataGrid;
use Webkul\DAM\Exceptions\AssetVersionMissingException;
use Webkul\DAM\Models\AssetVersion;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetVersionService;
use Webkul\DAM\Services\HistoryThumbnailGenerator;

class HistoryRestoreController
{
    public function __construct(
        protected AssetVersionService $service,
        protected HistoryThumbnailGenerator $generator,
    ) {}

    /**
     * DAM-specific history datagrid endpoint (adds Restore action).
     */
    public function datagrid(string $entityName, int $id)
    {
        if (request()->ajax()) {
            return app(AssetHistoryDataGrid::class)
                ->setEntityName($entityName)
                ->setEntityId((string) $id)
                ->toJson();
        }
    }

    public function restore(Request $request, int $asset): JsonResponse
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('dam.asset.update')) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => trans('dam::app.history.unauthorized'),
            ], 403);
        }

        $version = AssetVersion::where('asset_id', $asset)->first();

        if (! $version) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => trans('dam::app.history.no-backup'),
            ], 410);
        }

        $requestedVersionId = $request->input('version_id') ?? $request->query('version_id');
        if ($requestedVersionId !== null && $requestedVersionId !== '') {
            if (! $this->versionMatchesBackup((int) $requestedVersionId, $asset, $version)) {
                return new JsonResponse([
                    'status'  => 'error',
                    'message' => trans('dam::app.history.no-backup'),
                ], 409);
            }
        }

        $requestedPath = (string) $request->input('path', '');
        if ($requestedPath !== '' && $requestedPath !== $version->original_path) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => trans('dam::app.history.no-backup'),
            ], 409);
        }

        try {
            $this->service->restore($asset);
        } catch (AssetVersionMissingException $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => trans('dam::app.history.no-backup'),
            ], 410);
        }

        return new JsonResponse([
            'status'  => 'success',
            'message' => trans('dam::app.history.restore-success'),
        ]);
    }

    /**
     * The current AssetVersion row was created right before the change recorded
     * by the highest-numbered audit version for this asset. Restore is therefore
     * only valid when the user clicks that latest row.
     */
    protected function versionMatchesBackup(int $versionId, int $assetId, AssetVersion $version): bool
    {
        $latest = DB::table('audits')
            ->where('tags', 'asset')
            ->where('history_id', $assetId)
            ->max('version_id');

        return $latest !== null && (int) $latest === $versionId;
    }

    /**
     * Render a thumbnail of the OLD-value content of the asset's most recent
     * AssetVersion backup. Caches at versions/thumbnails/{asset}/last.jpg so
     * subsequent requests are a single disk read.
     *
     * The presenter renders the OLD side of the History modal's "Preview" row
     * with an <img src> pointing here. Returning 404 is fine — the presenter's
     * onerror handler swaps in the inline file-icon SVG.
     */
    public function thumbnail(int $asset, Request $request): Response
    {
        if (! Auth::guard('admin')->check()) {
            abort(403);
        }

        $version = AssetVersion::where('asset_id', $asset)->first();
        if (! $version) {
            abort(404);
        }

        $disk = Directory::getAssetDisk();

        if (Storage::disk($disk)->missing($version->version_path)) {
            abort(404);
        }

        $size = max(1, min(1024, (int) $request->query('size', 300)));
        $isSvg = ($version->original_mime_type === 'image/svg+xml')
            || strtolower((string) $version->original_extension) === 'svg';

        $cached = $isSvg
            ? 'versions/thumbnails/'.$asset.'/last.svg'
            : 'versions/thumbnails/'.$asset.'/last.jpg';

        if (Storage::disk($disk)->exists($cached)) {
            return $this->serveCachedThumbnail($cached, $disk, $isSvg);
        }

        $ok = $this->generateForVersion($version, $cached, $size, $disk, $isSvg);

        if (! $ok || Storage::disk($disk)->missing($cached)) {
            abort(404);
        }

        return $this->serveCachedThumbnail($cached, $disk, $isSvg);
    }

    /**
     * Pick the matching generator method for the version's original type.
     */
    protected function generateForVersion(AssetVersion $v, string $cached, int $size, string $disk, bool $isSvg): bool
    {
        $mime = (string) $v->original_mime_type;
        $ext = strtolower((string) $v->original_extension);

        if ($isSvg) {
            return $this->generator->fromSvg($v->version_path, $cached, $disk);
        }

        if (Str::startsWith($mime, 'image/')) {
            return $this->generator->fromImage($v->version_path, $cached, $size, $disk);
        }

        if ($ext === 'pdf' || $mime === 'application/pdf') {
            return $this->generator->fromPdf($v->version_path, $cached, $size, $disk);
        }

        if (Str::startsWith($mime, 'video/') || in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'flv', 'webm', 'ogv'], true)) {
            return $this->generator->fromVideo($v->version_path, $cached, $size, $disk);
        }

        return false;
    }

    protected function serveCachedThumbnail(string $path, string $disk, bool $isSvg): Response
    {
        $body = Storage::disk($disk)->get($path);
        $contentType = $isSvg ? 'image/svg+xml' : 'image/jpeg';

        return response($body, 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'private, max-age=300');
    }
}
