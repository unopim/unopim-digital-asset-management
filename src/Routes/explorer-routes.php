<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Webkul\DAM\Http\Controllers\Explorer\BookmarkController;
use Webkul\DAM\Http\Controllers\Explorer\CopyController;
use Webkul\DAM\Http\Controllers\Explorer\ExplorerDataController;
use Webkul\DAM\Http\Controllers\Explorer\MassMoveController;

Route::controller(ExplorerDataController::class)->prefix('explorer')->group(function () {
    Route::get('/data', 'index')->name('admin.dam.explorer.index');
    Route::get('/filter-options', 'filterOptions')->name('admin.dam.explorer.filter-options');
    Route::post('/count-items', 'countItems')->name('admin.dam.explorer.count_items');
});

Route::prefix('explorer/bookmarks')->controller(BookmarkController::class)->group(function () {
    Route::get('', 'index')->name('admin.dam.explorer.bookmarks.index');
    Route::post('', 'store')->name('admin.dam.explorer.bookmarks.store');
    Route::delete('{id}', 'destroy')->name('admin.dam.explorer.bookmarks.destroy');
});

Route::prefix('explorer')->controller(CopyController::class)->group(function () {
    Route::post('/copy/asset', 'copyAsset')->name('admin.dam.explorer.copy.asset');
    Route::post('/copy/directory', 'copyDirectory')->name('admin.dam.explorer.copy.directory');
    Route::post('/directory/copy-structure-to', 'copyStructureTo')->name('admin.dam.explorer.directory.copy_structure_to');
    Route::post('/mass-copy', 'massCopy')->name('admin.dam.explorer.mass_copy');
});

Route::prefix('explorer')->controller(MassMoveController::class)->group(function () {
    Route::post('/mass-move', 'move')->name('admin.dam.explorer.mass_move');
});
