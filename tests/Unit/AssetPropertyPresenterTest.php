<?php

use Webkul\Core\Models\Locale;
use Webkul\DAM\Presenters\AssetProperty as Presenter;

it('returns label and raw values for non-language fields', function () {
    $result = Presenter::representValueForHistory('Old Name', 'New Name', 'name');

    expect($result)->toBe([
        'name' => [
            'name' => 'Property Name',
            'old'  => 'Old Name',
            'new'  => 'New Name',
        ],
    ]);
});

it('falls back to empty string when values are null', function () {
    $result = Presenter::representValueForHistory(null, null, 'value');

    expect($result['value']['old'])->toBe('');
    expect($result['value']['new'])->toBe('');
    expect($result['value']['name'])->toBe('Property Value');
});

it('translates locale ids to locale names for the language field', function () {
    $oldLocale = Locale::first();
    $newLocale = Locale::skip(1)->first() ?? $oldLocale;

    $result = Presenter::representValueForHistory($oldLocale->id, $newLocale->id, 'language');

    expect($result['language']['name'])->toBe('Property Language');
    expect($result['language']['old'])->toBe($oldLocale->name);
    expect($result['language']['new'])->toBe($newLocale->name);
});
