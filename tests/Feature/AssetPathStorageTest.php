<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Support\AssetPathStorage;

/*
 * Importers resolve asset paths per value; without bulk loading a batch referencing one
 * asset a thousand times issued a thousand queries.
 */

beforeEach(function (): void {
    $this->damResetAssetTables();

    $this->storage = new AssetPathStorage;

    foreach (['assets/Root/a.jpg', 'assets/Root/b.jpg', 'assets/Root/Sub/c.png'] as $path) {
        Asset::create([
            'file_name' => basename($path),
            'file_type' => 'image',
            'file_size' => 10,
            'mime_type' => 'image/jpeg',
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'path'      => $path,
        ]);
    }
});

it('resolves known paths to their asset ids', function () {
    $this->storage->load(['assets/Root/a.jpg', 'assets/Root/Sub/c.png']);

    expect($this->storage->get('assets/Root/a.jpg'))
        ->toBe(Asset::where('path', 'assets/Root/a.jpg')->value('id'))
        ->and($this->storage->get('assets/Root/Sub/c.png'))
        ->toBe(Asset::where('path', 'assets/Root/Sub/c.png')->value('id'));
});

it('reports an unknown path as unresolved', function () {
    $this->storage->load(['assets/Root/missing.jpg']);

    expect($this->storage->has('assets/Root/missing.jpg'))->toBeFalse()
        ->and($this->storage->get('assets/Root/missing.jpg'))->toBeNull();
});

it('resolves a whole batch in a single query', function () {
    DB::enableQueryLog();

    $this->storage->load(['assets/Root/a.jpg', 'assets/Root/b.jpg', 'assets/Root/Sub/c.png']);

    expect(DB::getQueryLog())->toHaveCount(1);

    DB::disableQueryLog();
});

it('does not re-query paths it already holds', function () {
    $this->storage->load(['assets/Root/a.jpg']);

    DB::enableQueryLog();

    $this->storage->load(['assets/Root/a.jpg']);

    expect(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});

it('ignores empty paths', function () {
    DB::enableQueryLog();

    $this->storage->load(['', '']);

    expect(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});
