<?php

declare(strict_types=1);

namespace Webkul\DAM\Contracts\Adapters;

interface PimIntegration
{
    public function isAvailable(): bool;
}
