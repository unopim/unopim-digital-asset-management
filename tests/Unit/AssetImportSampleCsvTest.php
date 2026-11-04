<?php

use Webkul\DAM\Helpers\Importers\Asset\Importer;

it('ships a sample csv whose header is a subset of the importer columns and carries directory', function () {
    $sample = base_path('packages/Webkul/DAM/src/Resources/samples/import/assets.csv');

    expect(is_file($sample))->toBeTrue();

    $header = str_getcsv(trim(explode("\n", file_get_contents($sample))[0]));

    $importer = app(Importer::class);
    $columns = new ReflectionProperty($importer, 'validColumnNames');
    $columns->setAccessible(true);
    $valid = $columns->getValue($importer);

    expect($header)->toContain('directory')
        ->not->toContain('url')
        ->and(array_diff($header, $valid))->toBe([]);
});
