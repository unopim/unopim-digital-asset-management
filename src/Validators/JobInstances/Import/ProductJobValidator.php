<?php

namespace Webkul\DAM\Validators\JobInstances\Import;

use Webkul\DAM\Validators\JobInstances\Import\Concerns\AcceptsAssetBundle;
use Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator as BaseValidator;

class ProductJobValidator extends BaseValidator
{
    use AcceptsAssetBundle;
}
