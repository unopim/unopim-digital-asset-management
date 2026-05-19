<?php

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Locale;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetProperty;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('fetches a property by id via api', function () {
    $asset = Asset::factory()->create();
    $property = AssetProperty::factory()->create(['dam_asset_id' => $asset->id]);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.property.get', $property->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $property->id);
});

it('returns 404 when property is not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.property.get', 999999))
        ->assertStatus(404);
});

it('adds a new property via api', function () {
    $asset = Asset::factory()->create();
    $locale = Locale::where('status', 1)->first();

    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.property.add', $asset->id), [
            'name'     => 'Author Name',
            'type'     => 'text',
            'language' => $locale->code,
            'value'    => 'John Doe',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('dam_asset_properties', [
        'dam_asset_id' => $asset->id,
        'name'         => 'Author Name',
        'value'        => 'John Doe',
    ]);
});

it('validates property creation fields via api', function () {
    $asset = Asset::factory()->create();

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.property.add', $asset->id), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'type', 'language', 'value']);
});

it('rejects property creation for unknown locale via api', function () {
    $asset = Asset::factory()->create();

    $response = $this->withHeaders($this->headers)
        ->postJson(route('admin.api.dam.property.add', $asset->id), [
            'name'     => 'Field',
            'type'     => 'text',
            'language' => 'zz_ZZ',
            'value'    => 'v',
        ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

it('updates a property via api', function () {
    $asset = Asset::factory()->create();
    $property = AssetProperty::factory()->create(['dam_asset_id' => $asset->id]);

    $response = $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.dam.property.update', $property->id), [
            'name'  => 'Updated Name',
            'value' => 'new value',
        ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('dam_asset_properties', [
        'id'    => $property->id,
        'name'  => 'Updated Name',
        'value' => 'new value',
    ]);
});

it('returns 404 when updating a non-existent property via api', function () {
    $this->withHeaders($this->headers)
        ->patchJson(route('admin.api.dam.property.update', 999999), [
            'name'  => 'X',
            'value' => 'v',
        ])
        ->assertStatus(404);
});

it('deletes a property via api', function () {
    $asset = Asset::factory()->create();
    $property = AssetProperty::factory()->create(['dam_asset_id' => $asset->id]);

    $response = $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.property.delete', $property->id));

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseMissing('dam_asset_properties', ['id' => $property->id]);
});

it('returns 404 when deleting a non-existent property via api', function () {
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.dam.property.delete', 999999))
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// Helper — custom-role user granted only to a specific directory
// ---------------------------------------------------------------------------

function makePropCustomHeaders(Directory $grantedDir): array
{
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => []]);
    DB::table('dam_directory_role')->insert([
        'directory_id' => $grantedDir->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
    $headers = test()->getAuthenticationHeaders();
    Admin::latest('id')->first()->update(['role_id' => $role->id]);
    app(DirectoryPermissionService::class)->flush();

    return $headers;
}

// ---------------------------------------------------------------------------
// Directory-permission gate tests (Task 7)
// ---------------------------------------------------------------------------

it('returns 403 when adding a property to an asset in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $granted = Directory::factory()->create();
    $headers = makePropCustomHeaders($granted);

    $this->withHeaders($headers)
        ->postJson(route('admin.api.dam.property.add', $asset->id), [
            'name'     => 'color',
            'type'     => 'text',
            'language' => 'en',
            'value'    => 'red',
        ])
        ->assertStatus(403);
});

it('returns 403 when fetching a property whose asset is in a denied directory', function () {
    $denied = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $asset->directories()->attach($denied->id);
    $property = AssetProperty::factory()->create(['dam_asset_id' => $asset->id]);
    $granted = Directory::factory()->create();
    $headers = makePropCustomHeaders($granted);

    $this->withHeaders($headers)
        ->getJson(route('admin.api.dam.property.get', $property->id))
        ->assertStatus(403);
});
