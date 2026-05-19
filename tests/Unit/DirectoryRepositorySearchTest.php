<?php

use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Services\DirectoryPermissionService;

beforeEach(function () {
    $this->repository = app(DirectoryRepository::class);
});

it('returns an empty collection when the query is empty or below two characters', function () {
    $this->mock(DirectoryPermissionService::class, function ($mock) {
        $mock->shouldReceive('bypass')->andReturn(true);
    });

    Directory::factory()->create(['name' => 'banners']);

    expect($this->repository->search(''))->toHaveCount(0)
        ->and($this->repository->search('a'))->toHaveCount(0);
});

it('matches directory names case-insensitively as substring', function () {
    $this->mock(DirectoryPermissionService::class, function ($mock) {
        $mock->shouldReceive('bypass')->andReturn(true);
    });

    Directory::factory()->create(['name' => 'BANNERS']);
    Directory::factory()->create(['name' => 'urban']);
    Directory::factory()->create(['name' => 'brands']);

    $names = $this->repository->search('ban')->pluck('name')->all();

    expect($names)->toContain('BANNERS')
        ->and($names)->toContain('urban')
        ->and($names)->not->toContain('brands');
});

it('restricts results to viewable directory ids when ACL is not bypassed', function () {
    $banned = Directory::factory()->create(['name' => 'banned-marketing']);
    $accessible = Directory::factory()->create(['name' => 'banned-public']);

    $this->mock(DirectoryPermissionService::class, function ($mock) use ($accessible) {
        $mock->shouldReceive('bypass')->andReturn(false);
        $mock->shouldReceive('viewableIds')->andReturn([$accessible->id]);
    });

    $names = $this->repository->search('banned')->pluck('name')->all();

    expect($names)->toBe(['banned-public']);
});

it('caps results at the supplied limit', function () {
    $this->mock(DirectoryPermissionService::class, function ($mock) {
        $mock->shouldReceive('bypass')->andReturn(true);
    });

    for ($i = 0; $i < 25; $i++) {
        Directory::factory()->create(['name' => 'banner-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
    }

    expect($this->repository->search('banner', 20))->toHaveCount(20)
        ->and($this->repository->search('banner', 5))->toHaveCount(5);
});

it('decorates each result with the top-down ancestor name chain', function () {
    $this->mock(DirectoryPermissionService::class, function ($mock) {
        $mock->shouldReceive('bypass')->andReturn(true);
    });

    $root = Directory::factory()->create(['name' => 'Root', 'parent_id' => null]);
    $a = Directory::factory()->create(['name' => 'A', 'parent_id' => $root->id]);
    $b = Directory::factory()->create(['name' => 'B', 'parent_id' => $a->id]);
    $c = Directory::factory()->create(['name' => 'C-target', 'parent_id' => $b->id]);

    // Refresh nested-set columns after factory inserts.
    $root->refresh();
    $a->refresh();
    $b->refresh();
    $c->refresh();

    $result = $this->repository->search('C-target')->first();

    expect($result)->not->toBeNull();
    expect($result->path_names)->toBe(['Root', 'A', 'B', 'C-target']);
});
