<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\API\Asset\AssetController;
use Webkul\DAM\Http\Controllers\API\Asset\CommentController;
use Webkul\DAM\Http\Controllers\API\Asset\DirectoryController;
use Webkul\DAM\Http\Controllers\API\Asset\LinkedResourcesController;
use Webkul\DAM\Http\Controllers\API\Asset\PropertyController;
use Webkul\DAM\Http\Controllers\API\Asset\ShareController;
use Webkul\DAM\Http\Controllers\API\Asset\TagController;

Route::group([
    'middleware' => [
        'auth:api',
    ],
], function () {
    Route::controller(AssetController::class)->prefix('assets')->group(function () {
        Route::get('', 'index')->name('admin.api.dam.assets.index');
        Route::put('/edit/{id}', 'edit')->name('admin.api.dam.assets.edit');
        Route::get('/{id}', 'show')->name('admin.api.dam.assets.show');
        Route::post('/reupload', 'reUpload')->name('admin.api.dam.assets.reUpload');
        Route::get('/{id}/metadata', 'metadata')->whereNumber('id')->name('admin.api.dam.assets.metadata');
        Route::put('/{id}', 'update')->name('admin.api.dam.assets.update');
        Route::post('', 'upload')->name('admin.api.dam.assets.upload');
        Route::delete('/{id}', 'destroy')->name('admin.api.dam.assets.destroy');
        Route::get('/download/{id}', 'download')->name('admin.api.dam.assets.download');
    });

    Route::controller(DirectoryController::class)->prefix('directories')->group(function () {
        Route::get('', 'index')->name('admin.api.dam.directory.index');
        Route::get('{id}', 'getDirectory')->name('admin.api.dam.directory.get');
        Route::post('', 'store')->name('admin.api.dam.directory.store');
        Route::put('/{id}', 'update')->name('admin.api.dam.directory.update');
        Route::delete('{id}', 'destroy')->name('admin.api.dam.directory.delete');
    });

    Route::controller(CommentController::class)->prefix('comments')->group(function () {
        Route::get('{id}', 'comments')->name('admin.api.dam.comment.get');
        Route::put('/{id}', 'update')->name('admin.api.dam.comment.update');
        Route::delete('/{id}', 'delete')->name('admin.api.dam.comment.delete');
        Route::post('', 'createComment')->name('admin.api.dam.comment.store');
    });

    Route::controller(TagController::class)->prefix('tags')->group(function () {
        Route::get('', 'allTags')->name('admin.api.dam.tags.all');
        Route::post('/bulk', 'bulkAssign')->name('admin.api.dam.tags.bulk_assign');
        Route::get('{id}', 'tags')->whereNumber('id')->name('admin.api.dam.tags.get');
        Route::post('', 'addTag')->name('admin.api.dam.tag.add');
        Route::delete('', 'removeTag')->name('admin.api.dam.tag.delete');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('admin.api.dam.tags.destroy');
    });

    Route::controller(PropertyController::class)->prefix('properties')->group(function () {
        Route::get('{id}', 'properties')->name('admin.api.dam.property.get');
        Route::post('/{id}', 'addProperty')->name('admin.api.dam.property.add');
        Route::patch('/{id}', 'update')->name('admin.api.dam.property.update');
        Route::delete('/{id}', 'delete')->name('admin.api.dam.property.delete');
    });

    Route::controller(LinkedResourcesController::class)->prefix('linked-resource')->group(function () {
        Route::get('{id}', 'getLinkedResource')->name('admin.api.dam.linked_resource.get');
    });

    Route::controller(ShareController::class)->prefix('shares')->group(function () {
        Route::get('', 'index')->name('admin.api.dam.shares.index');
        Route::post('', 'store')->name('admin.api.dam.shares.store');
        Route::put('/{id}', 'update')->whereNumber('id')->name('admin.api.dam.shares.update');
        Route::post('/{id}/revoke', 'revoke')->whereNumber('id')->name('admin.api.dam.shares.revoke');
        Route::post('/{id}/reauthorize', 'reauthorize')->whereNumber('id')->name('admin.api.dam.shares.reauthorize');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('admin.api.dam.shares.destroy');
    });
});
