<?php

use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;

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
