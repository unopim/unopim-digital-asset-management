<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\API\Asset\AssetController;
use Webkul\DAM\Http\Controllers\API\Asset\CommentController;
use Webkul\DAM\Http\Controllers\API\Asset\DirectoryController;
use Webkul\DAM\Http\Controllers\API\Asset\LinkedResourcesController;
use Webkul\DAM\Http\Controllers\API\Asset\PropertyController;
use Webkul\DAM\Http\Controllers\API\Asset\TagController;

Route::group([
    'middleware' => [
        'auth:api',
    ],
], function () {
    /** Assets API Routes */
    Route::controller(AssetController::class)->prefix('assets')->group(function () {
        Route::get('', 'index');
        Route::put('/edit/{id}', 'edit')->name('admin.api.dam.assets.edit');
        Route::get('/{id}', 'show')->name('admin.api.dam.assets.show');
        Route::post('/reupload', 'reUpload')->name('admin.api.dam.assets.reUpload');
        Route::put('/{id}', 'update')->name('admin.api.dam.assets.update');
        Route::post('', 'upload')->name('admin.api.dam.assets.upload');
        Route::delete('/{id}', 'destroy')->name('admin.api.dam.assets.destroy');
        Route::get('/download/{id}', 'download')->name('admin.api.dam.assets.download');
    });

    /** Directory API Routes */
    Route::controller(DirectoryController::class)->prefix('directories')->group(function () {
        Route::get('', 'index');
        Route::get('{id}', 'getDirectory')->name('admin.api.dam.directory.get');
        Route::post('', 'store')->name('admin.api.dam.directory.store');
        Route::put('/{id}', 'update')->name('admin.api.dam.directory.update');
        Route::delete('{id}', 'destroy')->name('admin.api.dam.directory.delete');
    });

    /** Comment API Routes */
    Route::controller(CommentController::class)->prefix('comments')->group(function () {
        Route::get('{id}', 'comments')->name('admin.api.dam.comment.get');
        Route::put('/{id}', 'update')->name('admin.api.dam.comment.update');
        Route::delete('/{id}', 'delete')->name('admin.api.dam.comment.delete');
        Route::post('', 'createComment')->name('admin.api.dam.comment.store');
    });

    /** Tag API Routes */
    Route::controller(TagController::class)->prefix('tags')->group(function () {
        Route::get('{id}', 'tags')->name('admin.api.dam.tags.get');
        Route::post('', 'addTag')->name('admin.api.dam.tag.add');
        Route::delete('', 'removeTag')->name('admin.api.dam.tag.delete');
    });

    /** Property API Routes */
    Route::controller(PropertyController::class)->prefix('properties')->group(function () {
        Route::get('{id}', 'properties')->name('admin.api.dam.property.get');
        Route::post('/{id}', 'addProperty')->name('admin.api.dam.property.add');
        Route::put('/{id}', 'update')->name('admin.api.dam.property.update');
        Route::delete('/{id}', 'delete')->name('admin.api.dam.property.delete');
    });

    /** LinkedResource API Routes */
    Route::controller(LinkedResourcesController::class)->prefix('linked-resource')->group(function () {
        Route::get('{id}', 'getLinkedResource')->name('admin.api.dam.linked_resource.get');
    });
});
