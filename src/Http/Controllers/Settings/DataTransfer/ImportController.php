<?php

namespace Webkul\DAM\Http\Controllers\Settings\DataTransfer;

use Webkul\Admin\Http\Controllers\Settings\DataTransfer\ImportController as BaseController;
use Webkul\DAM\Support\ServableMedia;

class ImportController extends BaseController
{
    protected function isGenuineImage(string $extension, string $stagedPath): bool
    {
        return ServableMedia::permits($extension, $stagedPath);
    }
}
