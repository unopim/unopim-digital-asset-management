<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\ActionRequestController;
use Webkul\DAM\Http\Controllers\Asset\AssetController;
use Webkul\DAM\Http\Controllers\Asset\CommentController;
use Webkul\DAM\Http\Controllers\Asset\LinkedResourcesController;
use Webkul\DAM\Http\Controllers\Asset\PropertyController;
use Webkul\DAM\Http\Controllers\Asset\ShareController;
use Webkul\DAM\Http\Controllers\Asset\TagController;
use Webkul\DAM\Http\Controllers\AssetPickerController;
use Webkul\DAM\Http\Controllers\DAMController;
use Webkul\DAM\Http\Controllers\DirectoryController;
use Webkul\DAM\Http\Controllers\FileController;
use Webkul\DAM\Http\Controllers\ImageEditController;

Route::group([
    'middleware' => ['admin', 'dam'],
    'prefix'     => config('app.admin_url').'/dam',
], function () {
    Route::controller(DAMController::class)->prefix('')->group(function () {
        Route::get('', 'index')->name('admin.dam.index');
    });

    Route::group(['prefix' => 'assets'], function () {
        Route::controller(AssetController::class)->group(function () {
            Route::get('', 'index')->name('admin.dam.assets.index');
            Route::get('/edit/{id}', 'edit')->name('admin.dam.assets.edit')->where('id', '[0-9]+');
            Route::get('show/{id}', 'show')->name('admin.dam.assets.show');
            Route::put('update/{id}', 'update')->name('admin.dam.assets.update');

            Route::post('/upload', 'upload')->name('admin.dam.assets.upload');
            Route::post('/re-upload', 'reUpload')->name('admin.dam.assets.re_upload');
            Route::delete('/destroy/{id}', 'destroy')->name('admin.dam.assets.destroy');
            Route::post('/mass-update', 'massUpdate')->name('admin.dam.assets.mass_update');
            Route::post('/mass-delete', 'massDestroy')->name('admin.dam.assets.mass_delete');

            Route::get('download/{id}', 'download')->name('admin.dam.assets.download');
            Route::get('download-compressed/{id}', 'downloadCompressed')->name('admin.dam.assets.download_compressed');
            Route::get('custom-download/{id}', 'customDownload')->name('admin.dam.assets.custom_download');

            Route::post('rename', 'rename')->name('admin.dam.assets.rename');
            Route::post('/moved', 'moved')->name('admin.dam.assets.moved');

            Route::get('metadata/{id}', 'getMetadataById')->name('admin.dam.assets.metadata')->where('id', '[0-9]+');

        });

        Route::controller(TagController::class)->prefix('')->group(function () {
            Route::post('/tag', 'addOrUpdateTag')->name('admin.dam.assets.tag');
            Route::post('/remove-tag', 'removeTag')->name('admin.dam.assets.remove-tag');
        });

        Route::controller(PropertyController::class)->prefix('')->group(function () {
            Route::get('/edit/{id}/properties', 'properties')->name('admin.dam.asset.properties.index')->where('id', '[0-9]+');
            Route::post('/edit/{id}/properties/create', 'propertiesCreate')->name('admin.dam.asset.property.store');
            Route::put('/edit/{id}/properties/update', 'propertiesUpdate')->name('admin.dam.asset.properties.update');
            Route::get('edit/properties/edit/{id}', 'propertiesEdit')->name('admin.dam.asset.property.edit');
            Route::delete('edit/{asset_id}/properties/destroy/{id}', 'propertiesDestroy')->name('admin.dam.asset.properties.delete');
            Route::post('/edit/{asset_id}/properties/mass-delete', 'massDestroy')->name('admin.dam.asset.properties.mass_delete');
        });

        Route::controller(CommentController::class)->prefix('')->group(function () {
            Route::get('/get-user-info/{id}', 'getUserInfo')->name('admin.dam.asset.comments.get_user_info')->where('id', '[0-9]+');
            Route::get('/edit/{id}/comments', 'comments')->name('admin.dam.asset.comments.index')->where('id', '[0-9]+');
            Route::post('/edit/{id}/comment/create', 'commentCreate')->name('admin.dam.asset.comment.store');
            Route::put('/edit/{id}/comment/update', 'commentUpdate')->name('admin.dam.asset.comment.update');
            Route::delete('edit/{id}/comment/delete', 'commentDelete')->name('admin.dam.asset.comment.delete');
        });

        Route::controller(ImageEditController::class)->prefix('image-edit')->group(function () {
            Route::post('/resize/{id}', 'resize')->name('admin.dam.assets.image_edit.resize')->where('id', '[0-9]+');
            Route::post('/adjust/{id}', 'adjust')->name('admin.dam.assets.image_edit.adjust')->where('id', '[0-9]+');
            Route::post('/transform/{id}', 'transform')->name('admin.dam.assets.image_edit.transform')->where('id', '[0-9]+');
            Route::post('/bg-color/{id}', 'bgColor')->name('admin.dam.assets.image_edit.bg_color')->where('id', '[0-9]+');
            Route::post('/bg-upload/{id}', 'bgUpload')->name('admin.dam.assets.image_edit.bg_upload')->where('id', '[0-9]+');
            Route::post('/bg-ai/{id}', 'bgAi')->name('admin.dam.assets.image_edit.bg_ai')->where('id', '[0-9]+');
            Route::post('/bg-preview/{id}', 'bgPreview')->name('admin.dam.assets.image_edit.bg_preview')->where('id', '[0-9]+');
            Route::post('/bg-color-normal/{id}', 'bgColorNormal')->name('admin.dam.assets.image_edit.bg_color_normal')->where('id', '[0-9]+');
            Route::post('/filters/{id}', 'filters')->name('admin.dam.assets.image_edit.filters')->where('id', '[0-9]+');
        });

        Route::controller(LinkedResourcesController::class)->prefix('linked-resources')->group(function () {
            Route::get('', 'index')->name('admin.dam.asset.linked_resources.index');
        });
    });

    Route::controller(FileController::class)->prefix('file')->group(function () {
        Route::post('/create', 'createFile')->name('admin.dam.file.create');
        Route::delete('/delete', 'deleteFile')->name('admin.dam.file.delete');
        Route::put('/update', 'updateFile')->name('admin.dam.file.update');
        Route::get('/fetch/{path}', 'fetchFile')->where('path', '^assets/.*')->name('admin.dam.file.fetch')->withoutMiddleware(['admin', 'dam']);
        Route::get('/thumbnail', 'thumbnail')->name('admin.dam.file.thumbnail');
        Route::get('/preview/', 'preview')->name('admin.dam.file.preview');
        Route::get('/cover-art/{assetId}', 'coverArt')->name('admin.dam.file.cover-art');
    });

    Route::controller(ShareController::class)->prefix('shares')->group(function () {
        Route::get('', 'index')->name('admin.dam.shares.index');
        Route::post('', 'store')->name('admin.dam.shares.store');
        Route::patch('{id}', 'update')->name('admin.dam.shares.update')->where('id', '[0-9]+');
        Route::patch('{id}/revoke', 'revoke')->name('admin.dam.shares.revoke')->where('id', '[0-9]+');
        Route::delete('{id}', 'destroy')->name('admin.dam.shares.destroy')->where('id', '[0-9]+');
        Route::patch('{id}/reauthorize', 'reauthorize')->name('admin.dam.shares.reauthorize')->where('id', '[0-9]+');
        Route::get('active/{type}/{targetId}', 'activeForTarget')
            ->name('admin.dam.shares.active_for_target')
            ->where('type', 'asset|directory')
            ->where('targetId', '[0-9]+');
    });

    Route::controller(DirectoryController::class)->prefix('directory')->group(function () {
        Route::get('', 'index')->name('admin.dam.directory.index');
        Route::get('/search', 'search')->name('admin.dam.directory.search');
        Route::get('/children-directory/{id}', 'childrenDirectory')->name('admin.dam.directory.children');
        Route::get('/directory-assets/{id}', 'directoryAssets')->name('admin.dam.directory.assets');
        Route::post('/store', 'store')->name('admin.dam.directory.store');
        Route::post('/update', 'update')->name('admin.dam.directory.update');
        Route::delete('/destroy/{id}', 'destroy')->name('admin.dam.directory.destroy');
        Route::post('/copy', 'copy')->name('admin.dam.directory.copy');
        Route::get('zip-download/{id}', 'downloadArchive')->name('admin.dam.directory.zip_download');
        Route::post('/copy-structure', 'copyStructure')->name('admin.dam.directory.copy_structure');
        Route::post('/moved', 'moved')->name('admin.dam.directory.moved');
    });

    Route::controller(ActionRequestController::class)->prefix('action-request')->group(function () {
        Route::get('/status/{eventType}', 'fetchStatus')->name('admin.dam.action_request.status');
    });

    Route::controller(AssetPickerController::class)->prefix('picker')->group(function () {
        Route::get('', 'index')->name('admin.dam.asset_picker.index');

        Route::get('/get', 'fetchAssets')->name('admin.dam.asset_picker.get_assets');
    });

});
