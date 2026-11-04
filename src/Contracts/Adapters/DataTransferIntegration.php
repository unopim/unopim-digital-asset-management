<?php

namespace Webkul\DAM\Contracts\Adapters;

use Illuminate\Contracts\Foundation\Application;

interface DataTransferIntegration extends PimIntegration
{
    public function registerOverrides(Application $app): void;
}
