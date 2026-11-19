<?php

use function Pest\Laravel\get;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('renders the asset import conflict-strategy and auto-tag fields from the dam package', function () {
    $response = get(route('admin.settings.data_transfer.imports.create'));

    $response->assertOk();
    $response->assertSee('filters[conflict_strategy]', false);
    $response->assertSee('filters[auto_tag]', false);
    $response->assertSee(trans('dam::app.data-transfer.importers.assets.options.title'), false);
});

it('keeps the conflict-strategy field out of the core admin import blade', function () {
    $blade = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/settings/data-transfer/imports/create.blade.php')
    );

    expect($blade)->not->toContain('conflict_strategy');
});
