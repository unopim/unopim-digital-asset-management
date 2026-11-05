<?php

use Webkul\DAM\Helpers\Importers\Product\Concerns\ImportsAssetAttributes;

it('resolves the product importer to one that imports asset attributes', function () {
    $importer = app(config('importers.products.importer'));

    expect(class_uses_recursive($importer))->toContain(ImportsAssetAttributes::class);
});
