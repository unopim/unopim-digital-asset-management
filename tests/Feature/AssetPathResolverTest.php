<?php

use Webkul\DAM\Exceptions\InvalidPathException;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\AssetPathResolver;

beforeEach(function () {
    $this->damResetAssetTables();
    $this->resolver = app(AssetPathResolver::class);
});

it('creates the directory tree from a dam path and returns the deepest directory id', function () {
    $id = $this->resolver->resolveDirectoryForPath('assets/Root/Audio/Podcasts/x.mp3');

    $podcasts = Directory::find($id);

    expect($podcasts)->not->toBeNull()
        ->and($podcasts->name)->toBe('Podcasts')
        ->and($podcasts->generatePath())->toBe('Root/Audio/Podcasts');
});

it('reuses existing directories instead of duplicating them', function () {
    $first = $this->resolver->resolveDirectoryForPath('assets/Root/Audio/Podcasts/a.mp3');
    $countAfterFirst = Directory::count();

    $second = $this->resolver->resolveDirectoryForPath('assets/Root/Audio/Podcasts/b.mp3');

    expect($second)->toBe($first)
        ->and(Directory::count())->toBe($countAfterFirst);
});

it('computes the destination path from the dam path and optional file name', function () {
    expect($this->resolver->destinationPath('assets/Root/A/x.mp3', 'y.mp3'))
        ->toBe('assets/Root/A/y.mp3');

    expect($this->resolver->destinationPath('assets/Root/A/x.mp3', null))
        ->toBe('assets/Root/A/x.mp3');

    expect($this->resolver->destinationPath('assets/Root/A/x.mp3', ''))
        ->toBe('assets/Root/A/x.mp3');
});

it('anchors a non-matching first segment under the seeded root instead of creating a new root', function () {
    $root = Directory::create(['name' => 'Root', 'parent_id' => null]);

    $id = $this->resolver->resolveDirectoryForPath('assets/Other/x.png');

    $dir = Directory::find($id);

    expect($dir->name)->toBe('Other')
        ->and($dir->parent_id)->toBe($root->id)
        ->and(Directory::whereNull('parent_id')->count())->toBe(1);
});

it('treats a leading segment that names the seeded root as the root itself', function () {
    $root = Directory::create(['name' => 'Root', 'parent_id' => null]);

    $id = $this->resolver->resolveDirectoryForPath('assets/Root/Brand/x.png');

    $brand = Directory::find($id);

    expect($brand->name)->toBe('Brand')
        ->and($brand->parent_id)->toBe($root->id)
        ->and(Directory::whereNull('parent_id')->count())->toBe(1);
});

it('rejects a path with traversal or forbidden segments', function () {
    $this->resolver->resolveDirectoryForPath('assets/Root/../etc/x.mp3');
})->throws(InvalidPathException::class);

it('rejects a path missing the root anchor', function () {
    $this->resolver->resolveDirectoryForPath('assets/x.mp3');
})->throws(InvalidPathException::class);
