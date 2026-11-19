<?php

use Webkul\DAM\Helpers\Exporters\Asset\Exporter;
use Webkul\DataTransfer\Models\JobTrackBatch;

it('lists the directory column among the asset exporter columns and no longer carries url', function () {
    $exporter = app(Exporter::class);

    $columns = new ReflectionProperty($exporter, 'validColumnNames');
    $columns->setAccessible(true);

    expect($columns->getValue($exporter))
        ->toContain('directory')
        ->not->toContain('url');
});

it('writes an empty directory cell for an asset with no directory membership', function () {
    $exporter = app(Exporter::class);

    $filters = new ReflectionProperty($exporter, 'filters');
    $filters->setAccessible(true);
    $filters->setValue($exporter, ['with_media' => false]);

    $batch = new JobTrackBatch;
    $batch->data = [[
        'path'        => 'assets/Root/A/x.jpg',
        'file_name'   => 'x.jpg',
        'file_type'   => 'image',
        'file_size'   => 10,
        'mime_type'   => 'image/jpeg',
        'extension'   => 'jpg',
        'tags'        => [],
        'properties'  => [],
        'directories' => [],
    ]];

    $prepare = new ReflectionMethod($exporter, 'prepareAssets');
    $prepare->setAccessible(true);

    $rows = $prepare->invoke($exporter, $batch, null);

    expect($rows[0])->toHaveKey('directory')
        ->and($rows[0]['directory'])->toBe('')
        ->and($rows[0])->not->toHaveKey('url');
});
