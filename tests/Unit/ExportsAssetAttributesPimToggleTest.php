<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\DAM\Helpers\Exporters\Product\Concerns\ExportsAssetAttributes;
use Webkul\DAM\Support\PimIntegration;

uses(DatabaseTransactions::class);

it('dam_pim_enabled reflects the PIM state when enabled', function () {
    PimIntegration::fake(true);

    expect(dam_pim_enabled())->toBeTrue();
    expect(dam_pim_disabled())->toBeFalse();
});

it('dam_pim_enabled reflects the PIM state when disabled', function () {
    PimIntegration::fake(false);

    expect(dam_pim_enabled())->toBeFalse();
    expect(dam_pim_disabled())->toBeTrue();
});

it('product exporter uses ExportsAssetAttributes when pim is enabled', function () {
    PimIntegration::fake(true);

    // The binding is lazy, so flipping PIM state and resolving is enough — no
    // reboot and no manual rebinding.
    $exporter = app(config('exporters.products.exporter'));

    expect(class_uses_recursive($exporter))
        ->toContain(ExportsAssetAttributes::class);
});

it('product exporter does not use ExportsAssetAttributes when pim is disabled', function () {
    PimIntegration::fake(false);

    $exporter = app(config('exporters.products.exporter'));

    expect(class_uses_recursive($exporter))
        ->not->toContain(ExportsAssetAttributes::class);
});
