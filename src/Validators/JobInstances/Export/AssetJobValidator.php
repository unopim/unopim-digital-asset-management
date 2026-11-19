<?php

namespace Webkul\DAM\Validators\JobInstances\Export;

use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class AssetJobValidator extends JobValidator
{
    protected array $rules = [
        'filters.file_format' => 'required',
    ];

    protected array $attributeNames = [
        'filters.file_format' => 'File Format',
    ];
}
