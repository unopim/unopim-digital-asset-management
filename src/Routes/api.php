<?php

use Illuminate\Support\Facades\Route;

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
