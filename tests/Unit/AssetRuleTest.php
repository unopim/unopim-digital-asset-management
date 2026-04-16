<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\DAM\Rules\AssetRule;

it('passes when value is non-empty for a required attribute', function () {
    $attribute = new Attribute(['is_required' => true]);
    $rule = new AssetRule($attribute);

    $failed = false;
    $rule->validate('field', ['1', '2'], function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails when value is empty for a required attribute', function () {
    $attribute = new Attribute(['is_required' => true]);
    $rule = new AssetRule($attribute);

    $failed = false;
    $message = null;
    $rule->validate('field', [null, '', false], function ($msg) use (&$failed, &$message) {
        $failed = true;
        $message = $msg;
    });

    expect($failed)->toBeTrue();
    expect($message)->toBe(trans('dam::app.admin.validation.asset.required'));
});

it('does not fail for an empty value when attribute is not required', function () {
    $attribute = new Attribute(['is_required' => false]);
    $rule = new AssetRule($attribute);

    $failed = false;
    $rule->validate('field', [], function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});
