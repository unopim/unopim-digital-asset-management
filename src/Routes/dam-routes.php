<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\ActionRequestController;
use Webkul\DAM\Http\Controllers\Asset\AssetController;
use Webkul\DAM\Http\Controllers\Asset\CommentController;
use Webkul\DAM\Http\Controllers\Asset\LinkedResourcesController;
use Webkul\DAM\Http\Controllers\Asset\PropertyController;
use Webkul\DAM\Http\Controllers\Asset\TagController;
use Webkul\DAM\Http\Controllers\AssetPickerController;
use Webkul\DAM\Http\Controllers\DAMController;
use Webkul\DAM\Http\Controllers\DirectoryController;
use Webkul\DAM\Http\Controllers\FileController;

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
            Route::get('custom-download/{id}', 'customDownload')->name('admin.dam.assets.custom_download');

            Route::post('rename', 'rename')->name('admin.dam.assets.rename');
            Route::post('/moved', 'moved')->name('admin.dam.assets.moved');
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
    });

    Route::controller(DirectoryController::class)->prefix('directory')->group(function () {
        Route::get('', 'index')->name('admin.dam.directory.index');
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
