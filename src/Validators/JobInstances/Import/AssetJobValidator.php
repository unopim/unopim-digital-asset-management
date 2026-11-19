<?php

namespace Webkul\DAM\Validators\JobInstances\Import;

use Webkul\DAM\Validators\JobInstances\Import\Concerns\AcceptsAssetBundle;
use Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator;

class AssetJobValidator extends ProductJobValidator
{
    use AcceptsAssetBundle;

    protected array $rules = [
        'action'              => 'required|in:append,delete',
        'validation_strategy' => 'required|in:stop-on-errors,skip-errors',
        'allowed_errors'      => 'required|integer|min:0',
    ];
}
