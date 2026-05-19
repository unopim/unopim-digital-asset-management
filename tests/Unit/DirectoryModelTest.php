<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;

it('can create a directory using factory', function () {
    $directory = Directory::factory()->create();

    expect($directory)->toBeInstanceOf(Directory::class);
    expect($directory->id)->toBeInt();
    expect($directory->name)->toBeString();
});

it('has correct table name', function () {
    $directory = new Directory;
    expect($directory->getTable())->toBe('dam_directories');
});

it('has correct fillable attributes', function () {
    $directory = new Directory;
    expect($directory->getFillable())->toBe(['name', 'parent_id']);
});

it('can have assets relationship (many-to-many)', function () {
    $directory = Directory::factory()->create();
    $assets = Asset::factory()->count(3)->create();

    $directory->assets()->attach($assets->pluck('id'));

    expect($directory->assets)->toHaveCount(3);
});

it('can have parent-child relationship', function () {
    $parent = Directory::factory()->create(['name' => 'Parent']);
    $child = Directory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

    expect($child->parent->id)->toBe($parent->id);
    expect($parent->children)->toHaveCount(1);
    expect($parent->children->first()->id)->toBe($child->id);
});

it('can have nested children', function () {
    $grandparent = Directory::factory()->create(['name' => 'Grandparent']);
    $parent = Directory::factory()->create(['name' => 'Parent', 'parent_id' => $grandparent->id]);
    $child = Directory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

    expect($grandparent->children)->toHaveCount(1);
    expect($parent->children)->toHaveCount(1);
    expect($child->children)->toHaveCount(0);
});

it('should mark root directory as non-deletable', function () {
    $rootDirectory = Directory::find(1);

    if (! $rootDirectory) {
        $rootDirectory = Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    }

    expect($rootDirectory->isDeletable())->toBeFalse();
});

it('should mark non-root directories as deletable', function () {
    $directory = Directory::factory()->create();

    if ($directory->id === 1) {
        $directory = Directory::factory()->create();
    }

    expect($directory->isDeletable())->toBeTrue();
});

it('should mark root directory as non-copyable', function () {
    $rootDirectory = Directory::find(1);

    if (! $rootDirectory) {
        $rootDirectory = Directory::factory()->create(['id' => 1, 'name' => 'Root']);
    }

    expect($rootDirectory->isCopyable())->toBeFalse();
});

it('should mark non-root directories as copyable', function () {
    $directory = Directory::factory()->create();

    if ($directory->id === 1) {
        $directory = Directory::factory()->create();
    }

    expect($directory->isCopyable())->toBeTrue();
});

it('should return private as default asset disk', function () {
    config(['filesystems.default' => 'local']);

    expect(Directory::getAssetDisk())->toBe('private');
});

it('should return s3 when configured as default disk', function () {
    config(['filesystems.default' => 's3']);

    expect(Directory::getAssetDisk())->toBe('s3');
});

it('has the correct ASSETS_DIRECTORY constant', function () {
    expect(Directory::ASSETS_DIRECTORY)->toBe('assets');
});

it('has the correct NON_DELETABLE_DRECTORIES constant', function () {
    expect(Directory::NON_DELETABLE_DRECTORIES)->toBe([1]);
});

// ── generatePath ──────────────────────────────────────────────────────────

it('returns directory name as path for a root-level directory', function () {
    $dir = Directory::create(['name' => 'Media']);

    expect($dir->generatePath())->toBe('Media');
});

it('returns slash-joined ancestor names for a nested directory', function () {
    $root = Directory::create(['name' => 'Assets']);
    $child = Directory::create(['name' => 'Images', 'parent_id' => $root->id]);

    // Rebuild tree so NodeTrait _lft/_rgt are accurate
    Directory::fixTree();

    $child->refresh();

    expect($child->generatePath())->toBe('Assets/Images');
});

it('returns full path for a three-level directory hierarchy', function () {
    $root = Directory::create(['name' => 'Root']);
    $mid = Directory::create(['name' => 'Sub', 'parent_id' => $root->id]);
    $leaf = Directory::create(['name' => 'Deep', 'parent_id' => $mid->id]);

    Directory::fixTree();

    $leaf->refresh();

    expect($leaf->generatePath())->toBe('Root/Sub/Deep');
});

// ── privateSupport ────────────────────────────────────────────────────────

it('returns true for a writable local disk path', function () {
    Storage::fake('private');

    $dir = new Directory;
    // Fake disk base dir is writable; empty string resolves to its root
    expect($dir->privateSupport('', 'private'))->toBeTrue();
});

it('returns false when the disk throws an exception', function () {
    $dir = new Directory;
    // Non-configured disk name → Storage::disk() will throw
    expect($dir->privateSupport('any/path', 'disk_that_does_not_exist_xyz'))->toBeFalse();
});

// ── DirectoryRepository: tree asset-load behavior ────────────────────────

it('getDirectoryTreeOnly returns directories without assets relation loaded', function () {
    $dir = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $dir->assets()->attach($asset->id);

    $tree = app(DirectoryRepository::class)->getDirectoryTreeOnly();

    expect($tree)->not->toBeEmpty();
    foreach ($tree as $node) {
        expect($node->relationLoaded('assets'))->toBeFalse();
    }
});

it('getDirectoryTree eager-loads assets relation', function () {
    $dir = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $dir->assets()->attach($asset->id);

    $tree = app(DirectoryRepository::class)->getDirectoryTree();

    $node = collect($tree)->firstWhere('id', $dir->id);
    expect($node)->not->toBeNull();
    expect($node->relationLoaded('assets'))->toBeTrue();
});
