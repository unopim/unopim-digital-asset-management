<?php

/*
 * A common Asset attribute is owned by the parent product. The control renders before
 * core's lock wrapper opens, so it has to apply the lock itself — otherwise the field
 * stays fully editable on the child and the child stores its own copy, which then
 * stops tracking the parent.
 */

function renderAssetControl(array $data): string
{
    return view('dam::asset.catalog.products.dynamic-attribute-fields.asset-control', array_merge([
        'value'     => [3, 7],
        'fieldName' => 'product_images',
        'field'     => [],
    ], $data))->render();
}

it('locks the asset control when the attribute is inherited', function () {
    $html = renderAssetControl(['isLocked' => true, 'lockTitle' => 'Inherited from parent']);

    expect($html)->toContain('disabled')
        ->and($html)->toContain('pointer-events-none')
        ->and($html)->toContain('opacity-60')
        ->and($html)->toContain(':readonly="true"');
});

it('shows the parent assets on the locked child field', function () {
    $html = renderAssetControl(['isLocked' => true]);

    expect($html)->toContain('asset-values="3,7"');
});

it('surfaces the inheritance reason as a tooltip', function () {
    $html = renderAssetControl(['isLocked' => true, 'lockTitle' => 'Locked by Common']);

    expect($html)->toContain('title="Locked by Common"');
});

it('leaves an unlocked asset control fully editable', function () {
    $html = renderAssetControl(['isLocked' => false]);

    expect($html)->not->toContain('pointer-events-none')
        ->and($html)->not->toContain('opacity-60')
        ->and($html)->toContain(':readonly="false"');
});

it('stays editable when the host view sends no lock information', function () {
    $html = renderAssetControl([]);

    expect($html)->not->toContain('pointer-events-none')
        ->and($html)->toContain(':readonly="false"');
});

it('passes the lock state into the product attribute control event', function () {
    $blade = file_get_contents(base_path(
        'packages/Webkul/Admin/src/Resources/views/components/products/dynamic-attribute-fields.blade.php'
    ));

    expect($blade)->toContain("'isLocked' => \$isLocked")
        ->and(substr_count($blade, "'isLocked' => \$isLocked"))->toBe(2);
});
