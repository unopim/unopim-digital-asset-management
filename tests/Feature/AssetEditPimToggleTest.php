<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
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

it('asset edit blade wraps view render events in dam_pim_enabled guards', function () {
    $editBlade = file_get_contents(base_path('packages/Webkul/DAM/src/Resources/views/asset/edit.blade.php'));

    expect($editBlade)->toContain('@if (dam_pim_enabled())');

    expect($editBlade)->toContain("view_render_event('unopim.dam.asset.edit.before')");
    expect($editBlade)->toContain("view_render_event('unopim.dam.asset.edit.after')");
    expect($editBlade)->toContain("view_render_event('unopim.dam.asset.edit.card.general.before', ['asset' => \$asset])");
    expect($editBlade)->toContain("view_render_event('unopim.dam.asset.edit.card.general.after', ['asset' => \$asset])");
    expect($editBlade)->toContain("view_render_event('unopim.dam.asset.edit.card.accordian.details.before', ['asset' => \$asset])");
    expect($editBlade)->toContain("view_render_event('unopim.dam.asset.edit.card.accordian.details.after', ['asset' => \$asset])");
});
