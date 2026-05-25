<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\PublicShare\SharedViewerController;

Route::middleware('web')
    ->prefix('share')
    ->group(function () {
        Route::get('{token}', [SharedViewerController::class, 'show'])
            ->name('dam.share.show')
            ->middleware('throttle:dam-share-view')
            ->where('token', '[A-Za-z0-9]{20,64}');

        Route::get('{token}/download', [SharedViewerController::class, 'download'])
            ->name('dam.share.download')
            ->middleware('throttle:dam-share-download')
            ->where('token', '[A-Za-z0-9]{20,64}');

        Route::get('{token}/asset/{assetId}', [SharedViewerController::class, 'assetView'])
            ->name('dam.share.asset_view')
            ->middleware('throttle:dam-share-view')
            ->where('token', '[A-Za-z0-9]{20,64}')
            ->where('assetId', '[0-9]+');

        Route::get('{token}/asset/{assetId}/download', [SharedViewerController::class, 'assetDownload'])
            ->name('dam.share.asset_download')
            ->middleware('throttle:dam-share-download')
            ->where('token', '[A-Za-z0-9]{20,64}')
            ->where('assetId', '[0-9]+');

        Route::get('{token}/thumb/{assetId}', [SharedViewerController::class, 'thumbnail'])
            ->name('dam.share.thumbnail')
            ->middleware('throttle:dam-share-thumb')
            ->where('token', '[A-Za-z0-9]{20,64}')
            ->where('assetId', '[0-9]+');

        Route::get('{token}/download-zip', [SharedViewerController::class, 'downloadZip'])
            ->name('dam.share.download_zip')
            ->middleware('throttle:dam-share-download')
            ->where('token', '[A-Za-z0-9]{20,64}');
    });
