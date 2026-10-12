<?php

namespace Webkul\DAM\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Traits\CollectsAssetIds;
use Webkul\Product\Repositories\ProductRepository;

class AssetRule implements ValidationRule
{
    use CollectsAssetIds;

    public function __construct(
        protected Attribute $productAttribute,
        protected ?int $productId = null,
    ) {}

    /**
     * Ids the product already stores for this attribute are accepted as they are, so a
     * save that resubmits them does not fail for an editor who cannot open their
     * directory. Every other id must exist and sit in a directory the user can access.
     * Unknown and inaccessible ids share one message so the rule cannot be used to
     * probe which restricted assets exist.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = array_filter(is_array($value)
            ? $value
            : array_map(trim(...), explode(',', (string) $value)));

        if ($this->productAttribute->is_required && $value === []) {
            $fail(trans('dam::app.admin.validation.asset.required'));
        }

        $newIds = array_diff(
            array_map(fn (mixed $id): string => trim((string) $id), $value),
            $this->storedAssetIds(),
        );

        if ($newIds === []) {
            return;
        }

        if (! $this->allReferenceAccessibleAssets($newIds)) {
            $fail(trans('dam::app.admin.validation.asset.invalid'));
        }
    }

    /**
     * @param  string[]  $ids
     */
    protected function allReferenceAccessibleAssets(array $ids): bool
    {
        if (array_any($ids, fn (string $id): bool => ! ctype_digit($id))) {
            return false;
        }

        $ids = array_values(array_unique(array_map(intval(...), $ids)));

        if (resolve(AssetRepository::class)->findWhereIn('id', $ids)->count() !== count($ids)) {
            return false;
        }

        $permissions = resolve(DirectoryPermissionService::class);

        if ($permissions->bypass()) {
            return true;
        }

        $granted = $permissions->directlyGrantedIds();

        if ($granted === []) {
            return false;
        }

        return DB::table('dam_asset_directory')
            ->whereIn('asset_id', $ids)
            ->whereIn('directory_id', $granted)
            ->distinct()
            ->count('asset_id') === count($ids);
    }

    /**
     * @return string[]
     */
    protected function storedAssetIds(): array
    {
        if (! $this->productId) {
            return [];
        }

        $values = (array) (resolve(ProductRepository::class)->find($this->productId)?->values ?? []);

        return array_map(strval(...), $this->collectAssetIdsAcrossScopes($values, $this->productAttribute->code));
    }
}
