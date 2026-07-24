<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Services\DirectoryPermissionService;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->repository = app(DirectoryRepository::class);
});

function seedRollupFixture(): array
{
    $root = Directory::create(['name' => 'RollupRoot', 'parent_id' => null]);
    $parent = Directory::create(['name' => 'RollupParent', 'parent_id' => $root->id]);
    $leafA = Directory::create(['name' => 'RollupLeafA', 'parent_id' => $parent->id]);
    $leafB = Directory::create(['name' => 'RollupLeafB', 'parent_id' => $parent->id]);

    $parentAsset = Asset::factory()->create();
    $leafAssetOne = Asset::factory()->create();
    $leafAssetTwo = Asset::factory()->create();

    $parent->assets()->attach($parentAsset->id);
    $leafA->assets()->attach([$leafAssetOne->id, $leafAssetTwo->id]);

    return [$root, $parent, $leafA, $leafB];
}

it('rolls up direct + descendant asset counts onto every directory', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    $rollup = $this->repository->getAssetCountsRollup();

    expect($rollup[$root->id])->toBe(3);

    expect($rollup[$parent->id])->toBe(3);

    expect($rollup[$leafA->id])->toBe(2);

    expect($rollup[$leafB->id])->toBe(0);
});

it('getDirectoryTreeOnly returns structure without inline counts (counts load lazily)', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    $treeNodes = $this->repository->getDirectoryTreeOnly();
    $byId = [];
    $collect = function ($nodes) use (&$collect, &$byId) {
        foreach ($nodes as $node) {
            $byId[$node->id] = $node;
            if (! empty($node->children)) {
                $collect($node->children);
            }
        }
    };
    $collect($treeNodes);

    expect($byId)->toHaveKey($root->id);
    expect($byId)->toHaveKey($parent->id);
    expect((bool) $byId[$root->id]->has_children)->toBeTrue();

    expect($byId[$root->id]->assets_total_count ?? null)->toBeNull();
    expect($byId[$parent->id]->assets_total_count ?? null)->toBeNull();

    $counts = $this->repository->getSubtreeAssetCounts([$root->id, $parent->id]);
    expect($counts[$root->id])->toBe(3);
    expect($counts[$parent->id])->toBe(3);
});

it('keeps the existing direct assets_count untouched alongside the rollup', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    $parentRow = Directory::query()->withCount('assets')->find($parent->id);

    expect((int) $parentRow->assets_count)->toBe(1);
});

it('returns 0 for directories with no assets in their subtree', function () {
    $solo = Directory::create(['name' => 'SoloDir', 'parent_id' => null]);

    $rollup = $this->repository->getAssetCountsRollup();

    expect($rollup[$solo->id] ?? null)->toBe(0);
});

it('restricts rollup to only allowed directory ids when passed', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    $rollup = $this->repository->getAssetCountsRollup([$leafA->id]);

    expect($rollup[$root->id])->toBe(2);
    expect($rollup[$parent->id])->toBe(2);
    expect($rollup[$leafA->id])->toBe(2);
    expect($rollup[$leafB->id])->toBe(0);
});

it('returns zero counts for all directories when allowed list is empty', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    $rollup = $this->repository->getAssetCountsRollup([]);

    expect($rollup[$root->id] ?? 0)->toBe(0);
    expect($rollup[$parent->id] ?? 0)->toBe(0);
    expect($rollup[$leafA->id] ?? 0)->toBe(0);
});

it('the lazy roll-up uses role-granted ids for asset counts when ACL is active', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    $admin = $this->loginWithPermissions('custom', []);
    DB::table('dam_directory_role')->insert([
        'directory_id' => $leafA->id,
        'role_id'      => $admin->role_id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
    app(DirectoryPermissionService::class)->flush();

    $tree = $this->repository->getDirectoryTreeOnly();
    $byId = [];
    $collect = function ($nodes) use (&$collect, &$byId) {
        foreach ($nodes as $node) {
            $byId[$node->id] = $node;
            if (! empty($node->children)) {
                $collect($node->children);
            }
        }
    };
    $collect($tree);

    expect($byId)->toHaveKey($root->id);
    expect($byId)->toHaveKey($parent->id);

    expect(isset($byId[$leafB->id]))->toBeFalse();

    $service = app(DirectoryPermissionService::class);
    $counts = $this->repository->getSubtreeAssetCounts(
        [$root->id, $parent->id],
        $service->directlyGrantedIds()
    );
    expect($counts[$root->id])->toBe(2);
    expect($counts[$parent->id])->toBe(2);
});
