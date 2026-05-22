<?php

namespace Webkul\DAM\Presenters;

use Webkul\DAM\Models\Asset as AssetModel;
use Webkul\DAM\Models\AssetVersion;
use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class Asset implements HistoryPresenterInterface
{
    /** Fields whose audit row should render as a thumbnail comparison. */
    protected const FIELDS = ['path'];

    private const EYE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';

    private const RESTORE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>';

    private const FILE_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';

    /** Per-request memoisation: asset_id => ['version' => ?AssetVersion, 'asset' => ?AssetModel] */
    protected static array $cache = [];

    /** @var (callable():?int)|null */
    protected static $assetIdResolver = null;

    public static function clearRequestCache(): void
    {
        static::$cache = [];
        static::$assetIdResolver = null;
    }

    public static function setAssetIdResolver(callable $resolver): void
    {
        static::$assetIdResolver = $resolver;
    }

    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        if (! in_array($fieldName, self::FIELDS, true)) {
            return [];
        }

        $assetId = static::resolveAssetId();
        if (! $assetId) {
            return [];
        }

        $context = static::loadContext($assetId);
        if (! $context['asset']) {
            return [];
        }

        return [
            $fieldName => [
                'name' => trans('dam::app.history.field-preview'),
                'old'  => static::htmlForSide('old', $assetId, $context),
                'new'  => static::htmlForSide('new', $assetId, $context),
            ],
        ];
    }

    protected static function resolveAssetId(): ?int
    {
        if (static::$assetIdResolver) {
            $value = (static::$assetIdResolver)();

            return $value ? (int) $value : null;
        }

        $route = request()->route();
        $id = $route?->parameter('id');

        if ($id) {
            return (int) $id;
        }

        if (preg_match('#/dam/assets/edit/(\d+)#', request()->path(), $m)) {
            return (int) $m[1];
        }

        $referer = request()->headers->get('referer');
        if ($referer && preg_match('#/dam/assets/edit/(\d+)#', $referer, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /** @return array{version: ?AssetVersion, asset: ?AssetModel} */
    protected static function loadContext(int $assetId): array
    {
        if (! isset(static::$cache[$assetId])) {
            static::$cache[$assetId] = [
                'version' => AssetVersion::where('asset_id', $assetId)->first(),
                'asset'   => AssetModel::find($assetId),
            ];
        }

        return static::$cache[$assetId];
    }

    /** @param array{version: ?AssetVersion, asset: ?AssetModel} $context */
    protected static function htmlForSide(string $side, int $assetId, array $context): string
    {
        $version = $context['version'];
        $asset = $context['asset'];

        if ($side === 'old') {
            $path = $version?->original_path ?? $asset?->path;
            $mime = $version?->original_mime_type ?? $asset?->mime_type;
            $name = $version?->original_file_name ?? $asset?->file_name;
        } else {
            $path = $asset?->path;
            $mime = $asset?->mime_type;
            $name = $asset?->file_name;
        }

        if (! $path) {
            return '';
        }

        $hasBackup = $side === 'old' && (bool) $version;
        $thumbUrl = $side === 'old'
            ? route('admin.dam.history.thumbnail', ['asset' => $assetId])
            : route('admin.dam.file.thumbnail', ['path' => $path]);

        $media = sprintf(
            '<img src="%s" class="dam-hist-img" loading="lazy" alt="%s" onerror="this.replaceWith(Object.assign(document.createElement(\'span\'),{className:\'dam-hist-fileicon\',innerHTML:this.dataset.fallback,title:this.alt}));" data-fallback="%s">',
            e($thumbUrl),
            e($name ?? ''),
            e(self::FILE_SVG)
        );

        $eye = sprintf(
            '<button type="button" class="dam-hist-eye" title="%s">%s</button>',
            e(trans('dam::app.history.preview')),
            self::EYE_SVG
        );

        return sprintf(
            '<div class="dam-hist-thumb" data-path="%s" data-asset-id="%d" data-side="%s" data-mime="%s">%s<span class="dam-hist-overlay">%s</span></div>',
            e($path),
            $assetId,
            e($side),
            e($mime ?? ''),
            $media,
            $eye
        );
    }
}
