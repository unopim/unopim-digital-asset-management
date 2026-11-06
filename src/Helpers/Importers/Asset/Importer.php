<?php

namespace Webkul\DAM\Helpers\Importers\Asset;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Webkul\DAM\Exceptions\BlockedMediaHostException;
use Webkul\DAM\Exceptions\ForbiddenMediaException;
use Webkul\DAM\Exceptions\InvalidMediaUrlException;
use Webkul\DAM\Exceptions\InvalidPathException;
use Webkul\DAM\Exceptions\MediaTooLargeException;
use Webkul\DAM\Exceptions\UnreachableMediaException;
use Webkul\DAM\Helpers\AssetHelper;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetProperty;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\AssetPropertyRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Repositories\AssetTagRepository;
use Webkul\DAM\Services\AssetIngestionService;
use Webkul\DAM\Services\AssetPathResolver;
use Webkul\DAM\Services\RemoteMediaDownloader;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    public const ERROR_PATH_NOT_FOUND = 'asset_path_not_found';

    public const ERROR_ASSET_NOT_FOUND = 'asset_not_found';

    public const ERROR_ASSET_EXISTS = 'asset_already_exists';

    public const ERROR_DIRECTORY_REQUIRED = 'directory_required';

    public const ERROR_ASSET_HAS_RESOURCES = 'asset_has_resources';

    public const ERROR_MEDIA_SOURCE_NOT_FOUND = 'media_source_not_found';

    public const ERROR_URL_INVALID = 'url_invalid';

    public const ERROR_URL_UNREACHABLE = 'url_unreachable';

    public const ERROR_URL_BLOCKED = 'url_blocked';

    public const ERROR_FILE_TOO_LARGE = 'file_too_large';

    public const ERROR_FORBIDDEN_FILE = 'forbidden_file';

    public const ERROR_IMPORT_FAILED = 'import_failed';

    protected const THUMBNAIL_DIRECTORY = 'thumbnails';

    protected const PREVIEW_DIRECTORY = 'preview';

    protected ?string $defaultPropertyLocale = null;

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

    protected array $permanentAttributes = [
        'path',
    ];

    protected array $messages = [
        self::ERROR_PATH_NOT_FOUND          => 'dam::app.data-transfer.importers.assets.validation.errors.path-not-found',
        self::ERROR_ASSET_NOT_FOUND         => 'dam::app.data-transfer.importers.assets.validation.errors.asset-not-found',
        self::ERROR_ASSET_EXISTS            => 'dam::app.data-transfer.importers.assets.validation.errors.asset-already-exists',
        self::ERROR_DIRECTORY_REQUIRED      => 'dam::app.data-transfer.importers.assets.validation.errors.directory-required',
        self::ERROR_ASSET_HAS_RESOURCES     => 'dam::app.data-transfer.importers.assets.validation.errors.asset-has-resources',
        self::ERROR_MEDIA_SOURCE_NOT_FOUND  => 'dam::app.data-transfer.importers.assets.validation.errors.media-source-not-found',
        self::ERROR_URL_INVALID             => 'dam::app.data-transfer.importers.assets.validation.errors.url-invalid',
        self::ERROR_URL_UNREACHABLE         => 'dam::app.data-transfer.importers.assets.validation.errors.url-unreachable',
        self::ERROR_URL_BLOCKED             => 'dam::app.data-transfer.importers.assets.validation.errors.url-blocked',
        self::ERROR_FILE_TOO_LARGE          => 'dam::app.data-transfer.importers.assets.validation.errors.file-too-large',
        self::ERROR_FORBIDDEN_FILE          => 'dam::app.data-transfer.importers.assets.validation.errors.forbidden-file',
        self::ERROR_IMPORT_FAILED           => 'dam::app.data-transfer.importers.assets.validation.errors.import-failed',
    ];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AssetRepository $assetRepository,
        protected AssetTagRepository $assetTagRepository,
        protected AssetPropertyRepository $assetPropertyRepository,
        protected AssetPathResolver $pathResolver,
        protected AssetIngestionService $ingestionService,
        protected RemoteMediaDownloader $remoteMediaDownloader,
    ) {
        parent::__construct($importBatchRepository);
    }

    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        $validator = Validator::make($rowData, [
            'path' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            $this->skipRow($rowNumber, self::ERROR_PATH_NOT_FOUND, 'path', trans($this->messages[self::ERROR_PATH_NOT_FOUND]));

            return false;
        }

        if ($this->import->action !== Import::ACTION_DELETE && trim((string) ($rowData['directory'] ?? '')) === '') {
            $this->skipRow($rowNumber, self::ERROR_DIRECTORY_REQUIRED, 'directory', trans($this->messages[self::ERROR_DIRECTORY_REQUIRED]));

            return false;
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteAssets($batch);
        } else {
            $this->saveAssets($batch);
        }

        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    protected function saveAssets(JobTrackBatchContract $batch): void
    {
        $filters = $this->import->jobInstance->filters ?? [];
        $conflictStrategy = $filters['conflict_strategy'] ?? 'skip';
        $autoTag = (bool) ($filters['auto_tag'] ?? false);
        $imagesDir = (string) ($this->import->images_directory_path ?? '');

        $maxBytes = AssetHelper::getMaxUploadSizeKb() * 1024;

        $rowNumber = 0;
        $skippedCount = 0;

        foreach ($batch->data as $rowData) {
            $rowNumber++;

            $source = trim((string) $rowData['path']);
            $directories = $this->directoryNames($rowData);
            $fileName = trim((string) ($rowData['file_name'] ?? '')) ?: $this->leafName($source);
            $damPath = $this->buildDamPath($directories[0] ?? '', $fileName);
            $extraDirectories = array_map('basename', array_slice($directories, 1));
            $isUrl = $this->isHttpUrl($source);

            $localPath = null;
            $temporary = false;

            try {
                $destination = $this->pathResolver->destinationPath($damPath, $fileName);
                $existing = Asset::where('path', $destination)->first();

                if ($existing && $conflictStrategy === 'skip') {
                    $skippedCount++;
                    $this->logSkip($rowNumber, $damPath);

                    continue;
                }

                if ($existing && $conflictStrategy === 'update_metadata') {
                    $result = $this->ingestionService->ingest(
                        localPath: '',
                        damPath: $damPath,
                        fileName: $fileName,
                        conflictStrategy: 'update_metadata',
                        autoTag: $autoTag,
                        extraDirectoryNames: $extraDirectories,
                    );

                    if ($result->asset) {
                        $this->applyTags($result->asset, $rowData);
                        $this->applyProperties($result->asset, $rowData);
                        $this->updatedItemsCount++;
                    }

                    continue;
                }

                if ($isUrl) {
                    $localPath = $this->remoteMediaDownloader->download($source, $maxBytes);
                    $temporary = true;
                } else {
                    $localPath = $this->resolveServerFile($imagesDir, $source, $fileName);
                }

                if ($localPath === null) {
                    $this->skipRow($rowNumber, self::ERROR_MEDIA_SOURCE_NOT_FOUND, 'path', trans($this->messages[self::ERROR_MEDIA_SOURCE_NOT_FOUND]));

                    continue;
                }

                $result = $this->ingestionService->ingest(
                    localPath: $localPath,
                    damPath: $damPath,
                    fileName: $fileName,
                    conflictStrategy: $conflictStrategy,
                    autoTag: $autoTag,
                    extraDirectoryNames: $extraDirectories,
                );

                if ($result->status === 'skipped' || ! $result->asset) {
                    continue;
                }

                $this->applyTags($result->asset, $rowData);
                $this->applyProperties($result->asset, $rowData);

                if ($result->status === 'created') {
                    $this->createdItemsCount++;
                } else {
                    $this->updatedItemsCount++;
                }
            } catch (InvalidMediaUrlException) {
                $this->skipRow($rowNumber, self::ERROR_URL_INVALID, 'url', trans($this->messages[self::ERROR_URL_INVALID]));
            } catch (BlockedMediaHostException) {
                $this->skipRow($rowNumber, self::ERROR_URL_BLOCKED, 'url', trans($this->messages[self::ERROR_URL_BLOCKED]));
            } catch (UnreachableMediaException) {
                $this->skipRow($rowNumber, self::ERROR_URL_UNREACHABLE, 'url', trans($this->messages[self::ERROR_URL_UNREACHABLE]));
            } catch (MediaTooLargeException) {
                $this->skipRow($rowNumber, self::ERROR_FILE_TOO_LARGE, 'path', trans($this->messages[self::ERROR_FILE_TOO_LARGE]));
            } catch (ForbiddenMediaException) {
                $this->skipRow($rowNumber, self::ERROR_FORBIDDEN_FILE, 'path', trans($this->messages[self::ERROR_FORBIDDEN_FILE]));
            } catch (InvalidPathException) {
                $this->skipRow($rowNumber, self::ERROR_PATH_NOT_FOUND, 'path', trans($this->messages[self::ERROR_PATH_NOT_FOUND]));
            } catch (\Throwable $e) {
                report($e);

                $this->skipRow($rowNumber, self::ERROR_IMPORT_FAILED, 'path', trans($this->messages[self::ERROR_IMPORT_FAILED]));
            } finally {
                if ($temporary && $localPath !== null && is_file($localPath)) {
                    @unlink($localPath);
                }
            }
        }

        if ($skippedCount > 0) {
            $this->logSkipSummary($batch->id, $skippedCount);
        }
    }

    protected function logSkip(int $rowNumber, string $damPath): void
    {
        $this->jobLogger?->info(trans($this->messages[self::ERROR_ASSET_EXISTS], ['row' => $rowNumber, 'path' => $damPath]));
    }

    protected function logSkipSummary(int $batchId, int $skippedCount): void
    {
        $this->jobLogger?->info(trans('dam::app.data-transfer.importers.assets.validation.errors.asset-already-exists-summary', ['batch' => $batchId, 'count' => $skippedCount]));
    }

    protected function isHttpUrl(string $value): bool
    {
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) && parse_url($value, PHP_URL_HOST);
    }

    protected function resolveServerFile(string $imagesDir, string $damPath, ?string $fileName): ?string
    {
        $base = trim($imagesDir, '/');

        $candidates = array_values(array_filter([
            ltrim($damPath, '/'),
            $fileName !== null ? ltrim($fileName, '/') : null,
            basename($damPath),
        ]));

        foreach ($candidates as $candidate) {
            $relative = $base === '' ? $candidate : $base.'/'.$candidate;
            $storagePath = 'public/'.$relative;

            if (Storage::disk('local')->fileExists($storagePath)) {
                return Storage::disk('local')->path($storagePath);
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function directoryNames(array $rowData): array
    {
        return array_values(array_filter(array_map(trim(...), explode(',', (string) ($rowData['directory'] ?? ''))), 'strlen'));
    }

    protected function leafName(string $source): string
    {
        $path = (string) (parse_url($source, PHP_URL_PATH) ?: $source);

        return basename(str_replace('\\', '/', $path));
    }

    protected function buildDamPath(string $directoryTree, string $fileName): string
    {
        $prefix = Directory::ASSETS_DIRECTORY;

        $tree = trim(str_replace('\\', '/', $directoryTree), '/');

        if ($tree === $prefix) {
            $tree = '';
        } elseif (str_starts_with($tree, $prefix.'/')) {
            $tree = ltrim(substr($tree, strlen($prefix)), '/');
        }

        return $prefix.'/'.($tree === '' ? '' : $tree.'/').$fileName;
    }

    protected function applyTags(Asset $asset, array $rowData): void
    {
        $tags = array_filter(array_map(trim(...), explode(',', (string) ($rowData['tags'] ?? ''))), 'strlen');

        if ($tags === []) {
            return;
        }

        $this->assetTagRepository->attachTagsByName($asset, $tags);
    }

    protected function applyProperties(Asset $asset, array $rowData): void
    {
        $raw = trim((string) ($rowData['properties'] ?? ''));

        if ($raw === '') {
            return;
        }

        $properties = json_decode($raw, true);

        if (! is_array($properties)) {
            return;
        }

        foreach ($properties as $property) {
            $name = trim((string) ($property['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $language = trim((string) ($property['language'] ?? '')) ?: $this->defaultPropertyLocale();

            $existing = AssetProperty::query()
                ->where('dam_asset_id', $asset->id)
                ->where('name', $name)
                ->where('language', $language)
                ->first();

            $payload = [
                'dam_asset_id'  => $asset->id,
                'name'          => $name,
                'type'          => $property['type'] ?? null,
                'language'      => $language,
                'value'         => $property['value'] ?? null,
                'is_filterable' => (bool) ($property['is_filterable'] ?? false),
                'sort_order'    => (int) ($property['sort_order'] ?? 0),
            ];

            if ($existing) {
                $this->assetPropertyRepository->update($payload, $existing->id);
            } else {
                $this->assetPropertyRepository->create($payload);
            }
        }
    }

    protected function defaultPropertyLocale(): string
    {
        return $this->defaultPropertyLocale ??= core()->getDefaultLocaleCodeFromDefaultChannel();
    }

    protected function deleteAssets(JobTrackBatchContract $batch): void
    {
        $disk = Directory::getAssetDisk();

        foreach ($batch->data as $rowData) {
            $directories = $this->directoryNames($rowData);
            $fileName = trim((string) ($rowData['file_name'] ?? '')) ?: $this->leafName(trim((string) $rowData['path']));

            $target = $directories !== []
                ? $this->buildDamPath($directories[0], $fileName)
                : $this->normalizePath((string) $rowData['path']);

            $asset = $this->assetRepository->findOneByField('path', $target);

            if (! $asset || $asset->resources()->exists()) {
                continue;
            }

            Storage::disk($disk)->delete($asset->path);

            $this->clearDerivatives($asset->path, $disk);

            $this->assetRepository->delete($asset->id);

            $this->deletedItemsCount++;
        }
    }

    protected function clearDerivatives(string $path, string $disk): void
    {
        Storage::disk($disk)->delete(self::THUMBNAIL_DIRECTORY.'/'.$path);

        foreach (Storage::disk($disk)->directories(self::PREVIEW_DIRECTORY) as $sizeDirectory) {
            Storage::disk($disk)->delete($sizeDirectory.'/'.$path);
        }
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path);

        $prefix = Directory::ASSETS_DIRECTORY.'/';

        return str_starts_with($path, $prefix) ? $path : $prefix.$path;
    }
}
