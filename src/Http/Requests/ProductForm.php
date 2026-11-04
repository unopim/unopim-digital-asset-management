<?php

namespace Webkul\DAM\Http\Requests;

use Illuminate\Support\Arr;
use Webkul\Admin\Http\Requests\ProductForm as BaseProductForm;
use Webkul\DAM\Models\Asset;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;

class ProductForm extends BaseProductForm
{
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
