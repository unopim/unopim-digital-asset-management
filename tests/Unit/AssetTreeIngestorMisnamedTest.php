<?php

use Illuminate\Support\Facades\File;
use Webkul\DAM\Support\AssetTreeIngestor;

/*
 * macOS stores a "/" typed into a folder name as ":", so an archive meant to carry
 * "assets/" arrives as "assets:" and used to ingest nothing while reporting success.
 */

beforeEach(function (): void {
    $this->root = sys_get_temp_dir().'/dam-ingest-'.uniqid();
    File::ensureDirectoryExists($this->root);
});

afterEach(function (): void {
    File::deleteDirectory($this->root);
});

it('rejects an archive whose asset tree is named "assets:"', function () {
    File::ensureDirectoryExists($this->root.'/assets:/Root/Seed');

    expect(fn () => app(AssetTreeIngestor::class)->ingest($this->root))
        ->toThrow(RuntimeException::class, trans('dam::app.data-transfer.bundle.asset-tree-misnamed', ['name' => 'assets:']));
});

it('finds the misnamed tree beneath a wrapping folder', function () {
    File::ensureDirectoryExists($this->root.'/bundle/Assets:/Root');

    expect(fn () => app(AssetTreeIngestor::class)->ingest($this->root))
        ->toThrow(RuntimeException::class, trans('dam::app.data-transfer.bundle.asset-tree-misnamed', ['name' => 'Assets:']));
});

it('still ingests nothing, without failing, when no asset tree was meant', function () {
    File::ensureDirectoryExists($this->root.'/images');

    expect(app(AssetTreeIngestor::class)->ingest($this->root))->toBe([]);
});

it('does not mistake an empty "assets" tree for a misnamed one', function () {
    File::ensureDirectoryExists($this->root.'/assets');

    expect(app(AssetTreeIngestor::class)->ingest($this->root))->toBe([]);
});
