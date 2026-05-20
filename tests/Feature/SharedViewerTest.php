<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Share;

uses(DatabaseTransactions::class);

beforeEach(function () {
    DB::table('dam_shares')->delete();
    Storage::fake(Directory::getAssetDisk());
});

/**
 * Place a 1x1 PNG at the asset's stored path so the file-streaming public
 * endpoints have something to serve.
 */
function placeAssetFile(Asset $asset): void
{
    $disk = Directory::getAssetDisk();
    $file = UploadedFile::fake()->image('test.png', 4, 4);
    Storage::disk($disk)->put($asset->path, file_get_contents($file->getRealPath()));
}

it('renders the public asset viewer for a valid active token', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->create();

    $response = $this->get(route('dam.share.show', $share->token));

    $response->assertOk()
        ->assertSeeText($asset->file_name);
});

it('increments view_count when the asset viewer page is opened', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->create();

    $this->get(route('dam.share.show', $share->token));

    expect($share->fresh()->view_count)->toBe(1);
});

it('shows the expired landing for an expired token', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->expired()->create();

    $this->get(route('dam.share.show', $share->token))
        ->assertStatus(410)
        ->assertSeeText(trans('dam::app.share.public.expired-title'));
});

it('shows the revoked landing for a revoked token', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->revoked()->create();

    $this->get(route('dam.share.show', $share->token))
        ->assertStatus(410)
        ->assertSeeText(trans('dam::app.share.public.revoked-title'));
});

it('returns the not-found page for an unknown token', function () {
    // Well-formed (matches the [A-Za-z0-9]{20,64} route regex) but
    // never persisted, so the controller renders the not-found view.
    $this->get(route('dam.share.show', str_repeat('Z', 40)))
        ->assertNotFound()
        ->assertSeeText(trans('dam::app.share.public.not-found-title'));
});

it('streams the asset file via the download route', function () {
    $asset = Asset::factory()->create();
    placeAssetFile($asset);
    $share = Share::factory()->forAsset($asset->id)->create();

    $this->get(route('dam.share.download', $share->token))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="'.$asset->file_name.'"');

    expect($share->fresh()->download_count)->toBe(1);
});

it('rejects download on an expired asset share', function () {
    $asset = Asset::factory()->create();
    placeAssetFile($asset);
    $share = Share::factory()->forAsset($asset->id)->expired()->create();

    $this->get(route('dam.share.download', $share->token))
        ->assertStatus(410);
});

it('renders the directory gallery and lists only direct files', function () {
    $directory = Directory::factory()->create();
    $assetA = Asset::factory()->create();
    $assetB = Asset::factory()->create();
    $directory->assets()->attach([$assetA->id, $assetB->id]);

    // An asset in a different directory should NOT appear in this share.
    $otherDir = Directory::factory()->create();
    $assetC = Asset::factory()->create();
    $otherDir->assets()->attach($assetC->id);

    $share = Share::factory()->forDirectory($directory->id)->create();

    $response = $this->get(route('dam.share.show', $share->token));

    $response->assertOk()
        ->assertSeeText($assetA->file_name)
        ->assertSeeText($assetB->file_name)
        ->assertDontSeeText($assetC->file_name);
});

it('serves an asset that belongs to the shared directory', function () {
    $directory = Directory::factory()->create();
    $asset = Asset::factory()->create();
    $directory->assets()->attach($asset->id);

    $share = Share::factory()->forDirectory($directory->id)->create();

    $this->get(route('dam.share.asset_view', ['token' => $share->token, 'assetId' => $asset->id]))
        ->assertOk()
        ->assertSeeText($asset->file_name);
});

it('rejects access to an asset that is NOT in the shared directory', function () {
    $directory = Directory::factory()->create();
    $otherDir = Directory::factory()->create();
    $foreignAsset = Asset::factory()->create();
    $otherDir->assets()->attach($foreignAsset->id);

    $share = Share::factory()->forDirectory($directory->id)->create();

    $this->get(route('dam.share.asset_view', ['token' => $share->token, 'assetId' => $foreignAsset->id]))
        ->assertNotFound();
});

it('rejects asset download outside the shared directory', function () {
    $directory = Directory::factory()->create();
    $otherDir = Directory::factory()->create();
    $foreignAsset = Asset::factory()->create();
    placeAssetFile($foreignAsset);
    $otherDir->assets()->attach($foreignAsset->id);

    $share = Share::factory()->forDirectory($directory->id)->create();

    $this->get(route('dam.share.asset_download', ['token' => $share->token, 'assetId' => $foreignAsset->id]))
        ->assertNotFound();
});

it('does not require authentication for any public share route', function () {
    $asset = Asset::factory()->create();
    $share = Share::factory()->forAsset($asset->id)->create();

    // No login at all
    auth()->logout();

    $this->get(route('dam.share.show', $share->token))->assertOk();
});
