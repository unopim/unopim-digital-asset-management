<?php

namespace Webkul\DAM\ApiDataSource;

use Illuminate\Support\Carbon;
use Webkul\AdminApi\ApiDataSource;
use Webkul\DAM\Models\Share;
use Webkul\DAM\Repositories\ShareRepository;
use Webkul\DAM\Services\DirectoryPermissionService;

class ShareDataSource extends ApiDataSource
{
    protected $sortColumn = 'dam_shares.id';

    public function __construct(
        protected ShareRepository $shareRepository,
    ) {}

    public function prepareApiQueryBuilder()
    {
        $this->addFilter('share_type', ['='], 'dam_shares');
        $this->addFilter('target_id', ['='], 'dam_shares');
        $this->addFilter('name', ['=', 'LIKE'], 'dam_shares');
        $this->addFilter('created_at', ['=', '>=', '<='], 'dam_shares');
        $this->addFilter('updated_at', ['=', '>=', '<='], 'dam_shares');

        return $this->shareRepository->queryBuilder();
    }

    public function setDefaultFilters($queryBuilder)
    {
        $service = app(DirectoryPermissionService::class);

        if ($service->bypass()) {
            return $queryBuilder;
        }

        $allowedIds = $service->directlyGrantedIds();

        if (empty($allowedIds)) {
            $queryBuilder->whereRaw('1 = 0');

            return $queryBuilder;
        }

        $queryBuilder->where(function ($query) use ($allowedIds) {
            $query->where(function ($directoryShare) use ($allowedIds) {
                $directoryShare->where('share_type', Share::TYPE_DIRECTORY)
                    ->whereIn('target_id', $allowedIds);
            })->orWhere(function ($assetShare) use ($allowedIds) {
                $assetShare->where('share_type', Share::TYPE_ASSET)
                    ->whereIn('target_id', function ($sub) use ($allowedIds) {
                        $sub->select('asset_id')
                            ->from('dam_asset_directory')
                            ->whereIn('directory_id', $allowedIds);
                    });
            });
        });

        return $queryBuilder;
    }

    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map([$this, 'normalizeShare'], $paginator['data'] ?? []);
    }

    protected function normalizeShare(array $share): array
    {
        return [
            'id'             => $share['id'],
            'token'          => $share['token'] ?? null,
            'name'           => $share['name'] ?? null,
            'share_type'     => $share['share_type'] ?? null,
            'target_id'      => isset($share['target_id']) ? (int) $share['target_id'] : null,
            'public_url'     => isset($share['token'])
                ? route('dam.share.show', ['token' => $share['token']])
                : null,
            'expires_at'     => $share['expires_at'] ?? null,
            'revoked_at'     => $share['revoked_at'] ?? null,
            'view_count'     => (int) ($share['view_count'] ?? 0),
            'download_count' => (int) ($share['download_count'] ?? 0),
            'status'         => $this->resolveStatus($share),
            'created_at'     => $share['created_at'] ?? null,
            'updated_at'     => $share['updated_at'] ?? null,
        ];
    }

    protected function resolveStatus(array $share): string
    {
        if (! empty($share['revoked_at'])) {
            return 'revoked';
        }

        if (! empty($share['expires_at']) && Carbon::parse($share['expires_at'])->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}
