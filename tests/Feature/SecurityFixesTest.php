<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Http\Controllers\PublicShare\SharedViewerController;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetProperty;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Share;

uses(DatabaseTransactions::class);

beforeEach(function () {
    DB::table('dam_shares')->delete();
    Storage::fake(Directory::getAssetDisk());
});

function grantDirectoryAccess(int $roleId, int $directoryId): void
{
    DB::table('dam_directory_role')->insert([
        'directory_id' => $directoryId,
        'role_id'      => $roleId,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
}

it('forces an HTML asset to download through a public share, even when inline is requested', function () {
    config(['filesystems.default' => Directory::ASSETS_DISK_PRIVATE]);
    Storage::fake(Directory::ASSETS_DISK_PRIVATE);
    $disk = Directory::getAssetDisk();

    $asset = Asset::factory()->create([
        'file_name' => 'evil.html',
        'extension' => 'html',
        'mime_type' => 'text/html',
        'file_type' => 'document',
        'path'      => 'assets/evil/evil.html',
    ]);
    Storage::disk($disk)->put($asset->path, '<script>alert(document.cookie)</script>');

    $share = Share::factory()->forAsset($asset->id)->create();

    $controller = app(SharedViewerController::class);
    $request = Request::create('/', 'GET', ['disposition' => 'inline']);

    $response = $controller->download($request, $share->token);

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Content-Security-Policy'))->not->toBeNull();
});

it('denies asset-property mass delete to an admin without the property-delete permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $asset = Asset::factory()->create();
    $properties = AssetProperty::factory()->count(2)->create(['dam_asset_id' => $asset->id]);
    $ids = $properties->pluck('id')->toArray();

    $this->postJson(
        route('admin.dam.asset.properties.mass_delete', ['asset_id' => $asset->id]),
        ['indices' => $ids]
    )->assertForbidden();

    foreach ($ids as $id) {
        $this->assertDatabaseHas('dam_asset_properties', ['id' => $id]);
    }
});

it('skips properties on assets outside the admin\'s granted directories during mass delete', function () {
    $admin = $this->loginWithPermissions('custom', ['dam.asset.property.delete']);

    $grantedDir = Directory::factory()->create();
    $deniedDir = Directory::factory()->create();
    grantDirectoryAccess($admin->role_id, $grantedDir->id);

    $allowedAsset = Asset::factory()->create();
    $grantedDir->assets()->attach($allowedAsset->id);
    $deniedAsset = Asset::factory()->create();
    $deniedDir->assets()->attach($deniedAsset->id);

    $allowedProp = AssetProperty::factory()->create(['dam_asset_id' => $allowedAsset->id]);
    $deniedProp = AssetProperty::factory()->create(['dam_asset_id' => $deniedAsset->id]);

    $this->postJson(
        route('admin.dam.asset.properties.mass_delete', ['asset_id' => $allowedAsset->id]),
        ['indices' => [$allowedProp->id, $deniedProp->id]]
    )->assertOk();

    $this->assertDatabaseMissing('dam_asset_properties', ['id' => $allowedProp->id]);
    $this->assertDatabaseHas('dam_asset_properties', ['id' => $deniedProp->id]);
});

it('skips assets outside the admin\'s granted directories during mass delete', function () {
    $disk = Directory::getAssetDisk();
    $admin = $this->loginWithPermissions('custom', ['dam.asset.mass_delete']);

    $grantedDir = Directory::factory()->create();
    $deniedDir = Directory::factory()->create();
    grantDirectoryAccess($admin->role_id, $grantedDir->id);

    $allowedAsset = Asset::factory()->create(['path' => 'assets/granted/allowed.png']);
    $grantedDir->assets()->attach($allowedAsset->id);
    Storage::disk($disk)->put($allowedAsset->path, 'x');

    $deniedAsset = Asset::factory()->create(['path' => 'assets/denied/denied.png']);
    $deniedDir->assets()->attach($deniedAsset->id);
    Storage::disk($disk)->put($deniedAsset->path, 'x');

    $this->post(route('admin.dam.assets.mass_delete'), [
        'indices' => [$allowedAsset->id, $deniedAsset->id],
    ])->assertOk();

    $this->assertDatabaseMissing('dam_assets', ['id' => $allowedAsset->id]);
    $this->assertDatabaseHas('dam_assets', ['id' => $deniedAsset->id]);
});

it('denies folder upload to an admin without the upload permission', function () {
    $admin = $this->loginWithPermissions('custom', ['dashboard']);

    $directory = Directory::factory()->create();
    grantDirectoryAccess($admin->role_id, $directory->id);

    $this->post(route('admin.dam.assets.upload_folder'), [
        'files'          => [UploadedFile::fake()->image('a.png', 8, 8)],
        'relative_paths' => ['a.png'],
        'directory_id'   => $directory->id,
    ])->assertForbidden();
});
