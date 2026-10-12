<?php

use Illuminate\Support\Facades\File;

it('keeps the sprintf slot for the missing asset path in every locale', function () {
    $locales = collect(File::directories(dirname(__DIR__, 2).'/src/Resources/lang'))->map(fn ($dir) => basename($dir));

    expect($locales)->not->toBeEmpty();

    foreach ($locales as $locale) {
        $message = sprintf(trans('dam::app.data-transfer.bundle.asset-not-found', [], $locale), 'assets/Root/missing.png');

        expect(str_contains($message, 'assets/Root/missing.png'))->toBeTrue("Locale {$locale} drops the asset path")
            ->and($message)->not->toContain(':path');
    }
});
