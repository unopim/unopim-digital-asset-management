<?php

namespace Webkul\DAM\Services;

use Webkul\DAM\Models\Asset;

class IngestResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?Asset $asset = null,
    ) {}
}
