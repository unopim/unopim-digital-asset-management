<?php

declare(strict_types=1);

namespace Webkul\DAM\Http\Controllers\DataTransfer;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;

class AssetExportFilterController extends Controller
{
    const PER_PAGE = 20;

    const DEFAULT_PAGE = 1;

    public function directories(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = Directory::query()
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Directory $directory) => [
                'id'    => (string) $directory->id,
                'label' => $directory->name,
            ]);

        return $this->respondWithOptions($options, 'id');
    }

    public function fileTypes(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = collect(['image', 'video', 'audio', 'document'])
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => trans('dam::app.data-transfer.exporters.assets.filters.file-types.'.$type),
            ]);

        return $this->respondWithOptions($options, 'value');
    }

    public function tags(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = Tag::query()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Tag $tag) => [
                'name'  => $tag->name,
                'label' => $tag->name,
            ]);

        return $this->respondWithOptions($options, 'name');
    }

    protected function ensureExportPermission(): void
    {
        if (! bouncer()->hasPermission('data_transfer.export')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }
    }

    protected function respondWithOptions(Collection $options, string $trackBy): JsonResponse
    {
        $identifiers = request('identifiers');

        if (! empty($identifiers['values'])) {
            $values = is_array($identifiers['values'])
                ? $identifiers['values']
                : explode(',', (string) $identifiers['values']);

            return new JsonResponse([
                'options'  => $options->whereIn($trackBy, $values)->values(),
                'page'     => self::DEFAULT_PAGE,
                'lastPage' => self::DEFAULT_PAGE,
            ]);
        }

        $search = trim((string) request('query', ''));

        if ($search !== '') {
            $options = $options->filter(
                fn (array $option) => stripos((string) $option['label'], $search) !== false
            )->values();
        }

        $page = max(self::DEFAULT_PAGE, (int) request('page', self::DEFAULT_PAGE));
        $lastPage = max(self::DEFAULT_PAGE, (int) ceil($options->count() / self::PER_PAGE));

        return new JsonResponse([
            'options'  => $options->forPage($page, self::PER_PAGE)->values(),
            'page'     => $page,
            'lastPage' => $lastPage,
        ]);
    }
}
