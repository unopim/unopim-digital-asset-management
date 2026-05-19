<?php

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\API\Asset\AssetController;

Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'auth:api',
        'api.scope',
        'accept.json',
        'request.locale',
    ],
], function () {
    /**
     * Assets API
     */
    require 'V1/asset-routes.php';
});

Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => ['signed'],
], function () {
    Route::get('assets/signUrlDownload/{id}', [AssetController::class, 'signedUrl'])
        ->name('admin.api.dam.assets.private.download');
});
