<?php

use Illuminate\Support\Facades\DB;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Services\AssetAutoTaggingService;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('returns only the tags of the given asset via the api', function () {
    $asset = Asset::factory()->create(['file_type' => 'image']);
    $other = Asset::factory()->create(['file_type' => 'image']);

    $sunset = Tag::firstOrCreate(['name' => 'sunset']);
    $beach = Tag::firstOrCreate(['name' => 'beach']);
    $asset->tags()->attach([$sunset->id, $beach->id]);
    $other->tags()->attach(Tag::firstOrCreate(['name' => 'unrelated']));

    $this->withHeaders($this->getAuthenticationHeaders())
        ->getJson(route('admin.api.dam.assets.tags', $asset->id))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('ai_tagging_pending', false)
        ->assertExactJson([
            'success'            => true,
            'ai_tagging_pending' => false,
            'data'               => [
                ['id' => $beach->id, 'name' => 'beach'],
                ['id' => $sunset->id, 'name' => 'sunset'],
            ],
        ]);
});

it('returns an empty list for an asset without tags via the api', function () {
    $asset = Asset::factory()->create();

    $this->withHeaders($this->getAuthenticationHeaders())
        ->getJson(route('admin.api.dam.assets.tags', $asset->id))
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('reports AI tagging as pending while a tagging job is queued for the asset', function () {
    config(['dam.ai_tagging.enabled' => true]);

    $asset = Asset::factory()->create(['file_type' => 'image']);
    app(AssetAutoTaggingService::class)->markPending($asset->id);

    $this->withHeaders($this->getAuthenticationHeaders())
        ->getJson(route('admin.api.dam.assets.tags', $asset->id))
        ->assertOk()
        ->assertJsonPath('ai_tagging_pending', true);
});

it('returns 404 for the tags of a missing asset via the api', function () {
    $this->withHeaders($this->getAuthenticationHeaders())
        ->getJson(route('admin.api.dam.assets.tags', 999999))
        ->assertNotFound()
        ->assertJsonPath('success', false);
});

it('returns 403 for the tags of an asset in a denied directory via the api', function () {
    $denied = Directory::factory()->create();
    $granted = Directory::factory()->create();

    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    DB::table('dam_directory_role')->insert([
        'directory_id' => $granted->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $headers = $this->getAuthenticationHeaders();
    Admin::latest('id')->first()->update(['role_id' => $role->id]);
    app(DirectoryPermissionService::class)->flush();

    $this->withHeaders($headers)
        ->getJson(route('admin.api.dam.assets.tags', $asset->id))
        ->assertForbidden();
});

it('forbids a custom api key without the asset tags scope', function () {
    $asset = Asset::factory()->create();

    $this->withHeaders($this->getAuthenticationHeaders('custom', ['api.dam.assets.getById']))
        ->getJson(route('admin.api.dam.assets.tags', $asset->id))
        ->assertForbidden();
});

it('allows a custom api key granted only the asset tags scope', function () {
    $asset = Asset::factory()->create();

    $this->withHeaders($this->getAuthenticationHeaders('custom', ['api.dam.assets.tags']))
        ->getJson(route('admin.api.dam.assets.tags', $asset->id))
        ->assertOk();
});
