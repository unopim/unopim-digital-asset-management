<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image as AiImageFacade;
use Laravel\Ai\Prompts\ImagePrompt;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\MagicAI\Models\MagicAIPlatform;

beforeEach(function () {
    $this->loginAsAdmin();
    $this->disk = Directory::getAssetDisk();
    Storage::fake($this->disk);
});

function imageAsset(string $disk, string $ext = 'png'): Asset
{
    $file = UploadedFile::fake()->image("test.{$ext}", 200, 200);
    $path = 'assets/Root/test-'.uniqid().'.'.$ext;
    Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

    return Asset::factory()->create([
        'file_name' => basename($path),
        'path'      => $path,
        'extension' => $ext,
        'file_type' => 'image',
    ]);
}

it('should resize an image by width', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), ['width' => 100])
        ->assertOk()
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.success-updated')]);
});

it('should resize an image by height', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), ['height' => 80])
        ->assertOk();
});

it('should crop an image', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), [
        'crop_x' => 0,
        'crop_y' => 0,
        'crop_w' => 50,
        'crop_h' => 50,
    ])->assertOk();
});

it('should crop and then scale an image', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), [
        'crop_x' => 10,
        'crop_y' => 10,
        'crop_w' => 80,
        'crop_h' => 80,
        'width'  => 40,
    ])->assertOk();
});

it('should scale crop coordinates when img_nat_w and img_nat_h are provided', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), [
        'crop_x'    => 0,
        'crop_y'    => 0,
        'crop_w'    => 100,
        'crop_h'    => 100,
        'img_nat_w' => 400,
        'img_nat_h' => 400,
    ])->assertOk();
});

it('should return 422 when resize receives no dimensions', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), [])
        ->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-provide-dims')]);
});

it('should return 404 when resize targets a non-existent asset', function () {
    $this->postJson(route('admin.dam.assets.image_edit.resize', 99999), ['width' => 100])
        ->assertNotFound();
});

it('should reject negative crop coordinates', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), [
        'crop_x' => -5,
        'crop_y' => 0,
        'crop_w' => 50,
        'crop_h' => 50,
    ])->assertStatus(422);
});

it('should apply brightness adjustment', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), ['brightness' => 20])
        ->assertOk()
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.success-adjusted')]);
});

it('should apply contrast adjustment', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), ['contrast' => -10])
        ->assertOk();
});

it('should apply sharpen adjustment', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), ['sharpen' => 30])
        ->assertOk();
});

it('should apply blur adjustment', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), ['blur' => 5])
        ->assertOk();
});

it('should apply multiple adjustments together', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), [
        'brightness' => 10,
        'contrast'   => -10,
        'sharpen'    => 20,
        'blur'       => 0,
    ])->assertOk();
});

it('should succeed with all-zero adjustments (no-op)', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), [
        'brightness' => 0,
        'contrast'   => 0,
        'sharpen'    => 0,
        'blur'       => 0,
    ])->assertOk();
});

it('should reject out-of-range brightness', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), ['brightness' => 150])
        ->assertStatus(422);
});

it('should return 404 when adjust targets a non-existent asset', function () {
    $this->postJson(route('admin.dam.assets.image_edit.adjust', 99999), ['brightness' => 10])
        ->assertNotFound();
});

it('should rotate image 90 degrees', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['rotation' => 90])
        ->assertOk()
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.success-transformed')]);
});

it('should rotate image 180 degrees', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['rotation' => 180])
        ->assertOk();
});

it('should rotate image 270 degrees', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['rotation' => 270])
        ->assertOk();
});

it('should flip image horizontally', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['flip_h' => true])
        ->assertOk();
});

it('should flip image vertically', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['flip_v' => true])
        ->assertOk();
});

it('should apply rotation and flip together', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), [
        'rotation' => 90,
        'flip_h'   => true,
    ])->assertOk();
});

it('should accept zero rotation (no-op transform)', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['rotation' => 0])
        ->assertOk();
});

it('should reject invalid rotation angle', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['rotation' => 45])
        ->assertStatus(422);
});

it('should return 404 when transform targets a non-existent asset', function () {
    $this->postJson(route('admin.dam.assets.image_edit.transform', 99999), ['rotation' => 90])
        ->assertNotFound();
});

it('should apply greyscale filter', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.filters', $asset->id), ['greyscale' => true])
        ->assertOk()
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.success-updated')]);
});

it('should apply invert filter', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.filters', $asset->id), ['invert' => true])
        ->assertOk();
});

it('should apply both greyscale and invert filters', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.filters', $asset->id), [
        'greyscale' => true,
        'invert'    => true,
    ])->assertOk();
});

it('should return 422 when no filter is selected', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.filters', $asset->id), [])
        ->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-no-filter')]);
});

it('should return 422 when filters are explicitly false', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.filters', $asset->id), [
        'greyscale' => false,
        'invert'    => false,
    ])->assertStatus(422);
});

it('should return 404 when filters targets a non-existent asset', function () {
    $this->postJson(route('admin.dam.assets.image_edit.filters', 99999), ['greyscale' => true])
        ->assertNotFound();
});

it('should return 422 when bg-color has missing required fields', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_color', $asset->id), [])
        ->assertStatus(422);
});

it('should return 422 when bg-color has invalid hex format', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_color', $asset->id), [
        'color'       => 'not-a-color',
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertStatus(422);
});

it('should return 404 when bg-color references a non-existent platform', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_color', $asset->id), [
        'color'       => '#ff0000',
        'platform_id' => 999999,
        'model'       => 'gpt-image-1',
    ])->assertNotFound();
});

it('should return 422 when bg-color platform provider does not support images', function () {
    $asset = imageAsset($this->disk);
    $platform = MagicAIPlatform::create([
        'label'    => 'Anthropic Test',
        'provider' => 'anthropic',
        'api_key'  => 'test-key',
        'models'   => 'claude-3',
        'status'   => true,
    ]);

    $this->postJson(route('admin.dam.assets.image_edit.bg_color', $asset->id), [
        'color'       => '#ff0000',
        'platform_id' => $platform->id,
        'model'       => 'claude-3',
    ])->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-provider-no-images')]);
});

it('should return 404 when bg-color targets a non-existent asset', function () {
    $this->postJson(route('admin.dam.assets.image_edit.bg_color', 99999), [
        'color'       => '#ff0000',
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertNotFound();
});

it('should return 422 when bg-upload has no file', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_upload', $asset->id), [
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertStatus(422);
});

it('should return 422 when bg-upload has invalid mime type', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_upload', $asset->id), [
        'image'       => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertStatus(422);
});

it('should return 422 when bg-upload platform provider does not support images', function () {
    $asset = imageAsset($this->disk);
    $platform = MagicAIPlatform::create([
        'label'    => 'Groq Test',
        'provider' => 'groq',
        'api_key'  => 'test-key',
        'models'   => 'llama-3',
        'status'   => true,
    ]);

    $this->postJson(route('admin.dam.assets.image_edit.bg_upload', $asset->id), [
        'image'       => UploadedFile::fake()->image('bg.png', 200, 200),
        'platform_id' => $platform->id,
        'model'       => 'llama-3',
    ])->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-provider-no-images')]);
});

it('should return 422 when bg-ai has no prompt', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_ai', $asset->id), [
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertStatus(422);
});

it('should return 422 when bg-ai prompt exceeds max length', function () {
    $asset = imageAsset($this->disk);

    $this->postJson(route('admin.dam.assets.image_edit.bg_ai', $asset->id), [
        'prompt'      => str_repeat('a', 1001),
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertStatus(422);
});

it('should return 422 when bg-ai platform provider does not support images', function () {
    $asset = imageAsset($this->disk);
    $platform = MagicAIPlatform::create([
        'label'    => 'Mistral Test',
        'provider' => 'mistral',
        'api_key'  => 'test-key',
        'models'   => 'mistral-7b',
        'status'   => true,
    ]);

    $this->postJson(route('admin.dam.assets.image_edit.bg_ai', $asset->id), [
        'prompt'      => 'Blue sky background',
        'platform_id' => $platform->id,
        'model'       => 'mistral-7b',
    ])->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-provider-no-images')]);
});

it('should return 404 when bg-ai targets a non-existent asset', function () {
    $this->postJson(route('admin.dam.assets.image_edit.bg_ai', 99999), [
        'prompt'      => 'test',
        'platform_id' => 1,
        'model'       => 'gpt-image-1',
    ])->assertNotFound();
});

it('returns 422 (not 500) for every edit op when the asset file is missing on disk', function () {
    $asset = Asset::factory()->create([
        'file_name' => 'missing.png',
        'path'      => 'assets/Root/missing-'.uniqid().'.png',
        'extension' => 'png',
        'file_type' => 'image',
    ]);

    $expected = trans('dam::app.admin.dam.asset.edit.image-source-not-readable');

    $this->postJson(route('admin.dam.assets.image_edit.resize', $asset->id), ['width' => 100])
        ->assertStatus(422)->assertJson(['message' => $expected]);

    $this->postJson(route('admin.dam.assets.image_edit.adjust', $asset->id), ['brightness' => 10])
        ->assertStatus(422)->assertJson(['message' => $expected]);

    $this->postJson(route('admin.dam.assets.image_edit.filters', $asset->id), ['greyscale' => true])
        ->assertStatus(422)->assertJson(['message' => $expected]);

    $this->postJson(route('admin.dam.assets.image_edit.transform', $asset->id), ['rotation' => 90])
        ->assertStatus(422)->assertJson(['message' => $expected]);
});

function imageEditPlatform(string $provider, string $models, bool $status = true): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'    => ucfirst($provider).' Platform',
        'provider' => $provider,
        'api_key'  => 'test-key',
        'models'   => $models,
        'extras'   => [],
        'status'   => $status,
    ]);
}

it('lists every active image platform with the models core can use to edit images', function () {
    $openai = imageEditPlatform('openai', 'gpt-4o,gpt-image-2.5-sunburst,dall-e-3');
    $gemini = imageEditPlatform('gemini', 'gemini-2.5-pro,imagen-4.0-generate-001,gemini-2.5-flash-image');
    $generationOnly = imageEditPlatform('openai', 'dall-e-3');
    $anthropic = imageEditPlatform('anthropic', 'claude-3');
    $inactive = imageEditPlatform('openai', 'gpt-image-1', status: false);

    $platforms = collect($this->getJson(route('admin.dam.assets.image_edit.platforms'))
        ->assertOk()
        ->json('platforms'))->keyBy('id');

    expect($platforms->get($openai->id)['models'])->toBe(['gpt-image-2.5-sunburst'])
        ->and($platforms->get($gemini->id)['models'])->toBe(['gemini-2.5-flash-image'])
        ->and($platforms->has($generationOnly->id))->toBeFalse()
        ->and($platforms->has($anthropic->id))->toBeFalse()
        ->and($platforms->has($inactive->id))->toBeFalse();
});

it('denies the image platform list to an admin without the asset edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->getJson(route('admin.dam.assets.image_edit.platforms'))->assertForbidden();
});

it('rejects a background edit with a model the platform cannot use for images', function () {
    AiImageFacade::fake();

    $asset = imageAsset($this->disk);
    $platform = imageEditPlatform('openai', 'gpt-4o,gpt-image-2.5-sunburst');

    $this->postJson(route('admin.dam.assets.image_edit.bg_ai', $asset->id), [
        'prompt'      => 'a beach',
        'platform_id' => $platform->id,
        'model'       => 'gpt-4o',
    ])->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-model-no-images')]);

    AiImageFacade::assertNothingGenerated();
});

it('edits the background with exactly the model that was selected', function () {
    $result = UploadedFile::fake()->image('result.png', 200, 200);
    $png = base64_encode(file_get_contents($result->getRealPath()));
    AiImageFacade::fake([$png]);

    $asset = imageAsset($this->disk);
    $platform = imageEditPlatform('openai', 'gpt-image-1,gpt-image-1-mini');

    $this->postJson(route('admin.dam.assets.image_edit.bg_ai', $asset->id), [
        'prompt'      => 'a beach',
        'platform_id' => $platform->id,
        'model'       => 'gpt-image-1-mini',
    ])->assertOk();

    AiImageFacade::assertGenerated(fn (ImagePrompt $prompt) => $prompt->model === 'gpt-image-1-mini');
});

it('loads the AI platforms whenever the upload or AI background tab is opened', function () {
    $script = file_get_contents(base_path('packages/Webkul/DAM/src/Resources/views/asset/preview-modal/image/image-viewer-script.blade.php'));

    expect($script)
        ->toContain("route('admin.dam.assets.image_edit.platforms')")
        ->toContain("if (this.editTool === 'edit-bg' && tab !== 'color') this.loadBgPlatforms();")
        ->not->toContain("['openai', 'gemini', 'xai'].includes(p.provider)")
        ->not->toContain('imagePatterns');
});

it('rejects a generation-only model for a background edit', function (string $provider, string $model) {
    AiImageFacade::fake();

    $asset = imageAsset($this->disk);
    $platform = imageEditPlatform($provider, "{$model},gpt-image-1,gemini-2.5-flash-image");

    $this->postJson(route('admin.dam.assets.image_edit.bg_color', $asset->id), [
        'color'       => '#ff0000',
        'platform_id' => $platform->id,
        'model'       => $model,
    ])->assertStatus(422)
        ->assertJson(['message' => trans('dam::app.admin.dam.asset.edit.image-editor.error-model-no-images')]);

    AiImageFacade::assertNothingGenerated();
})->with([
    ['openai', 'dall-e-3'],
    ['gemini', 'imagen-4.0-generate-001'],
]);
