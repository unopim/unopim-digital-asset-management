<?php

namespace Webkul\DAM\Validators\JobInstances\Import;

use Webkul\DAM\Validators\JobInstances\Import\Concerns\AcceptsAssetBundle;
use Webkul\DataTransfer\Validators\JobInstances\Import\CategoryJobValidator as BaseValidator;

class CategoryJobValidator extends BaseValidator
{
    use AcceptsAssetBundle;
}
