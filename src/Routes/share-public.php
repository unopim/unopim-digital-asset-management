<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\PublicShare\SharedViewerController;

Route::middleware(['web', 'throttle:60,1'])
    ->prefix('share')
    ->group(function () {
        Route::get('{token}', [SharedViewerController::class, 'show'])
            ->name('dam.share.show')
            ->where('token', '[A-Za-z0-9]{20,64}');

        Route::get('{token}/download', [SharedViewerController::class, 'download'])
            ->name('dam.share.download')
            ->where('token', '[A-Za-z0-9]{20,64}');

        Route::get('{token}/asset/{assetId}', [SharedViewerController::class, 'assetView'])
            ->name('dam.share.asset_view')
            ->where('token', '[A-Za-z0-9]{20,64}')
            ->where('assetId', '[0-9]+');

        Route::get('{token}/asset/{assetId}/download', [SharedViewerController::class, 'assetDownload'])
            ->name('dam.share.asset_download')
            ->where('token', '[A-Za-z0-9]{20,64}')
            ->where('assetId', '[0-9]+');

        Route::get('{token}/thumb/{assetId}', [SharedViewerController::class, 'thumbnail'])
            ->name('dam.share.thumbnail')
            ->where('token', '[A-Za-z0-9]{20,64}')
            ->where('assetId', '[0-9]+');
    });
