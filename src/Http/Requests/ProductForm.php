<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Support\Arr;
use Webkul\Admin\Http\Requests\ProductForm as BaseProductForm;
use Webkul\DAM\Models\Asset;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;

/**
 * Drops asset values a product does not own at its own structure level.
 *
 * An asset attribute placed at the common level belongs to the ancestor sitting at
 * that level. A descendant reads it back through the resolved value chain and renders
 * the control locked, so a submission carrying such a value can only come from a stale
 * or hand-crafted form. Storing it would put a copy on the descendant, and a
 * descendant's own value wins the merge — the ancestor would quietly stop being the
 * source of truth for every child that ever saved.
 *
 * Ownership is decided by {@see VariantStructurePlanner::ownsAtOwnLevel()}, the rule
 * the bulk product update job already applies, so no second notion of inheritance is
 * introduced. Only asset attributes are filtered, and only on this request, which the
 * admin product update is the sole consumer of: no other attribute type and no other
 * write path changes behaviour.
 */
class ProductForm extends BaseProductForm
{
    /**
     * Value buckets an attribute code can appear in, and how many keys sit above it.
     */
    protected const VALUE_SCOPES = [
        AbstractType::COMMON_VALUES_KEY         => 0,
        AbstractType::LOCALE_VALUES_KEY         => 1,
        AbstractType::CHANNEL_VALUES_KEY        => 1,
        AbstractType::CHANNEL_LOCALE_VALUES_KEY => 2,
    ];

    public function __construct(
        ProductRepository $productRepository,
        protected VariantStructurePlanner $variantStructurePlanner
    ) {
        parent::__construct($productRepository);
    }

    public function prepareForValidation()
    {
        parent::prepareForValidation();

        $values = $this->input(AbstractType::PRODUCT_VALUES_KEY);

        if (! is_array($values)) {
            return;
        }

        $product = $this->productRepository->find($this->route('id'));

        if (! $product) {
            return;
        }

        $inheritedCodes = $this->inheritedAssetCodes($product);

        if ($inheritedCodes === []) {
            return;
        }

        $this->merge([
            AbstractType::PRODUCT_VALUES_KEY => $this->withoutCodes($values, $inheritedCodes),
        ]);
    }

    /**
     * Asset attributes on the product's family that an ancestor owns rather than this
     * product. Products outside a variant structure own everything, so this is empty.
     *
     * @return list<string>
     */
    protected function inheritedAssetCodes(Product $product): array
    {
        $family = $product->attribute_family;

        if (! $family) {
            return [];
        }

        return $family->customAttributes()
            ->where('attributes.type', Asset::ASSET_ATTRIBUTE_TYPE)
            ->get()
            ->pluck('code')
            ->reject(fn (string $code): bool => $this->variantStructurePlanner->ownsAtOwnLevel($product, $code))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $codes
     */
    protected function withoutCodes(array $values, array $codes): array
    {
        foreach (self::VALUE_SCOPES as $scope => $depth) {
            if (! is_array($values[$scope] ?? null)) {
                continue;
            }

            $values[$scope] = $this->pruneScope($values[$scope], $codes, $depth);
        }

        return $values;
    }

    /**
     * @param  list<string>  $codes
     */
    protected function pruneScope(array $bucket, array $codes, int $depth): array
    {
        if ($depth === 0) {
            return Arr::except($bucket, $codes);
        }

        foreach ($bucket as $key => $nested) {
            if (is_array($nested)) {
                $bucket[$key] = $this->pruneScope($nested, $codes, $depth - 1);
            }
        }

        return $bucket;
    }
}
