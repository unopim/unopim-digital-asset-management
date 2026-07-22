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

/**
 * Build a 3-level nested directory tree using create() so the nestedset trait
 * assigns `_lft`/`_rgt` correctly. Returns the [root, parent, leafA, leafB]
 * tuple. parent has 1 direct asset; leafA has 2; leafB has 0.
 */
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

    // root subtree: 1 (parent's direct) + 2 (leafA's) + 0 = 3
    expect($rollup[$root->id])->toBe(3);
    // parent subtree: own 1 + leafA 2 + leafB 0 = 3
    expect($rollup[$parent->id])->toBe(3);
    // leafA: 2 direct
    expect($rollup[$leafA->id])->toBe(2);
    // leafB: zero
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

    // Structure is present (root + its pre-loaded children) with has_children…
    expect($byId)->toHaveKey($root->id);
    expect($byId)->toHaveKey($parent->id);
    expect((bool) $byId[$root->id]->has_children)->toBeTrue();

    // …but the heavy subtree counts are no longer embedded — they are fetched
    // lazily via the asset-counts endpoint (getSubtreeAssetCounts).
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

// ---------------------------------------------------------------------------
// Permission-filtered rollup (role-based counts)
// ---------------------------------------------------------------------------

it('restricts rollup to only allowed directory ids when passed', function () {
    [$root, $parent, $leafA, $leafB] = seedRollupFixture();

    // Simulate: role is granted only leafA — sibling leafB and parent must not contribute.
    $rollup = $this->repository->getAssetCountsRollup([$leafA->id]);

    // ancestor dirs show sum of only the allowed subtree entries
    expect($rollup[$root->id])->toBe(2);   // only leafA's 2 assets
    expect($rollup[$parent->id])->toBe(2); // only leafA's 2 assets
    expect($rollup[$leafA->id])->toBe(2);  // leafA's own 2 assets
    expect($rollup[$leafB->id])->toBe(0);  // no assets — not in allowed list
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

    // Create a custom-role admin granted only to leafA.
    $admin = $this->loginWithPermissions('custom', []);
    DB::table('dam_directory_role')->insert([
        'directory_id' => $leafA->id,
        'role_id'      => $admin->role_id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
    app(DirectoryPermissionService::class)->flush();

    // root and parent are visible (ancestors of leafA); leafB is not.
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
    // leafB is not visible (not an ancestor or grant of leafA).
    expect(isset($byId[$leafB->id]))->toBeFalse();

    // The ACL-scoped roll-up (used by the asset-counts endpoint) only counts
    // leafA's 2 assets for the ancestors — not the parent's own direct asset.
    $service = app(DirectoryPermissionService::class);
    $counts = $this->repository->getSubtreeAssetCounts(
        [$root->id, $parent->id],
        $service->directlyGrantedIds()
    );
    expect($counts[$root->id])->toBe(2);
    expect($counts[$parent->id])->toBe(2);
});
