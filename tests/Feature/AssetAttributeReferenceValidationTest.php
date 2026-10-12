<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductValuesValidator;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->attribute = Attribute::factory()->create([
        'type'              => 'asset',
        'value_per_channel' => false,
        'value_per_locale'  => false,
        'is_required'       => false,
    ]);

    $this->granted = Directory::factory()->create(['name' => 'Granted'.uniqid(), 'parent_id' => null]);
    $this->restricted = Directory::factory()->create(['name' => 'Restricted'.uniqid(), 'parent_id' => null]);

    $this->grantedAsset = assetIn($this->granted);
    $this->restrictedAsset = assetIn($this->restricted);

    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog.products.edit']]);

    DB::table('dam_directory_role')->insert([
        'directory_id' => $this->granted->id,
        'role_id'      => $role->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $this->limitedAdmin = Admin::factory()->create(['role_id' => $role->id]);
});

function assetIn(Directory $directory): Asset
{
    $asset = Asset::factory()->create(['path' => 'assets/'.$directory->name.'/'.uniqid().'.jpg']);

    $asset->directories()->attach($directory->id);

    return $asset;
}

function validateAssetValue(Attribute $attribute, string $value, ?int $productId = null): void
{
    app(DirectoryPermissionService::class)->flush();

    app(ProductValuesValidator::class)->validate([
        AbstractType::COMMON_VALUES_KEY => [$attribute->code => $value],
    ], productId: $productId);
}

it('accepts an asset from a directory the user can access', function () {
    $this->actingAs($this->limitedAdmin, 'admin');

    validateAssetValue($this->attribute, (string) $this->grantedAsset->id);

    expect(true)->toBeTrue();
});

it('rejects an asset from a directory the user cannot access', function () {
    $this->actingAs($this->limitedAdmin, 'admin');

    validateAssetValue($this->attribute, $this->grantedAsset->id.','.$this->restrictedAsset->id);
})->throws(ValidationException::class, 'could not be found or are not available to you');

it('rejects an asset id that does not exist', function () {
    $this->actingAs($this->limitedAdmin, 'admin');

    validateAssetValue($this->attribute, (string) ($this->restrictedAsset->id + 100000));
})->throws(ValidationException::class, 'could not be found or are not available to you');

it('rejects a value that is not an asset id', function () {
    $this->actingAs($this->limitedAdmin, 'admin');

    validateAssetValue($this->attribute, 'assets/Root/photo.jpg');
})->throws(ValidationException::class, 'could not be found or are not available to you');

it('accepts an asset from any directory for a user with full directory access', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    validateAssetValue($this->attribute, (string) $this->restrictedAsset->id);

    expect(true)->toBeTrue();
});

it('accepts a restricted asset the product already stores when it is resubmitted unchanged', function () {
    $product = Product::factory()->create();
    $product->values = array_replace_recursive($product->values ?? [], [
        AbstractType::COMMON_VALUES_KEY => [$this->attribute->code => (string) $this->restrictedAsset->id],
    ]);
    $product->saveQuietly();

    $this->actingAs($this->limitedAdmin, 'admin');

    validateAssetValue($this->attribute, $this->restrictedAsset->id.','.$this->grantedAsset->id, $product->id);

    expect(true)->toBeTrue();
});

it('rejects a restricted asset that another product stores', function () {
    $other = Product::factory()->create();
    $other->values = array_replace_recursive($other->values ?? [], [
        AbstractType::COMMON_VALUES_KEY => [$this->attribute->code => (string) $this->restrictedAsset->id],
    ]);
    $other->saveQuietly();

    $product = Product::factory()->create();

    $this->actingAs($this->limitedAdmin, 'admin');

    validateAssetValue($this->attribute, (string) $this->restrictedAsset->id, $product->id);
})->throws(ValidationException::class, 'could not be found or are not available to you');
