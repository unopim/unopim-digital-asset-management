<?php

namespace Webkul\DAM\Helpers\Exporters\Asset;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Webkul\DAM\Helpers\Exporters\Concerns\CopiesDamMedia;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    use CopiesDamMedia;

    /**
     * @var array<int, string>
     */
    protected array $directoryPathCache = [];

    protected array $validColumnNames = [
        'path',
        'directory',
        'file_name',
        'tags',
        'properties',
        'file_type',
        'file_size',
        'mime_type',
        'extension',
    ];

    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FlatItemBuffer $exportFileBuffer,
        protected AssetRepository $assetRepository,
        protected DirectoryRepository $directoryRepository,
        protected DirectoryPermissionService $directoryPermissionService,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    public function initilize(): void
    {
        $this->initializeFileBuffer();
    }

    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $assets = $this->prepareAssets($batch, $filePath);

        $this->exportBuffer->write($assets);

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    protected function prepareAssets(JobTrackBatchContract $batch, $filePath): array
    {
        $filters = $this->getFilters();

        $withMedia = (bool) ($filters['with_media'] ?? false);

        $assets = [];

        foreach ($batch->data as $rowData) {
            $assets[] = [
                'path'       => $rowData['path'],
                'directory'  => $this->directoryFor($rowData),
                'file_name'  => $rowData['file_name'],
                'tags'       => implode(', ', array_column($rowData['tags'] ?? [], 'name')),
                'properties' => $this->propertiesFor($rowData),
                'file_type'  => $rowData['file_type'],
                'file_size'  => $rowData['file_size'],
                'mime_type'  => $rowData['mime_type'],
                'extension'  => $rowData['extension'],
            ];

            if ($withMedia) {
                $this->copyMedia($rowData['path'], $filePath->getTemporaryPath().'/'.$rowData['path'], true);
            }

            $this->createdItemsCount++;
        }

        return $assets;
    }

    protected function directoryFor(array $rowData): string
    {
        $paths = [];

        foreach ($rowData['directories'] ?? [] as $directory) {
            $id = (int) ($directory['id'] ?? 0);

            if ($id === 0) {
                continue;
            }

            $paths[] = $this->directoryPath($id, (string) ($directory['name'] ?? ''));
        }

        return implode(', ', array_filter($paths, 'strlen'));
    }

    protected function directoryPath(int $directoryId, string $fallback): string
    {
        if (array_key_exists($directoryId, $this->directoryPathCache)) {
            return $this->directoryPathCache[$directoryId];
        }

        $directory = $this->directoryRepository->find($directoryId);

        return $this->directoryPathCache[$directoryId] = $directory
            ? $directory->generatePath()
            : $fallback;
    }

    protected function propertiesFor(array $rowData): string
    {
        $properties = array_map(fn (array $property) => [
            'name'          => $property['name'] ?? null,
            'type'          => $property['type'] ?? null,
            'language'      => $property['language'] ?? null,
            'value'         => $property['value'] ?? null,
            'is_filterable' => (bool) ($property['is_filterable'] ?? false),
            'sort_order'    => (int) ($property['sort_order'] ?? 0),
        ], $rowData['properties'] ?? []);

        return $properties === [] ? '' : json_encode($properties);
    }

    protected function getResults(): \Traversable
    {
        $filters = $this->getFilters();

        $query = Asset::query()->with(['tags', 'directories', 'properties']);

        if (! empty($filters['file_type'])) {
            $query->whereIn('file_type', (array) $filters['file_type']);
        }

        if (! empty($filters['tags'])) {
            $tags = (array) $filters['tags'];

            $query->whereHas('tags', fn (Builder $q): Builder => $q->whereIn('dam_tags.name', $tags));
        }

        if (! empty($filters['directories'])) {
            $directoryIds = $this->expandDirectoryIds((array) $filters['directories']);

            $query->whereHas('directories', fn (Builder $q): Builder => $q->whereIn('dam_directories.id', $directoryIds));
        }

        if (! $this->directoryPermissionService->bypass()) {
            $allowedIds = $this->directoryPermissionService->viewableIds();

            $query->whereHas('directories', fn (Builder $q): Builder => $q->whereIn('dam_directories.id', $allowedIds));
        }

        return $query->lazyById(500)->getIterator();
    }

    /**
     * @param  array<int, int|string>  $directoryIds
     * @return array<int, int>
     */
    protected function expandDirectoryIds(array $directoryIds): array
    {
        $ids = [];

        foreach ($directoryIds as $directoryId) {
            $directoryId = (int) $directoryId;

            $ids[] = $directoryId;

            $ids = array_merge($ids, $this->directoryRepository->getDescendantIds($directoryId));
        }

        return array_values(array_unique($ids));
    }

    protected function getHeaderLabels(): array
    {
        return $this->validColumnNames;
    }
}
