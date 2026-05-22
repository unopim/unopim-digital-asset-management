<?php

use Illuminate\Support\Facades\Schema;

it('creates the dam_asset_versions table with required columns and unique key', function () {
    expect(Schema::hasTable('dam_asset_versions'))->toBeTrue();

    foreach ([
        'id',
        'asset_id',
        'version_path',
        'original_path',
        'original_file_name',
        'original_extension',
        'original_mime_type',
        'original_file_size',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('dam_asset_versions', $column))->toBeTrue("missing column $column");
    }
});
