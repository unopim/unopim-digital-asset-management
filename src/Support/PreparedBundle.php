<?php

namespace Webkul\DAM\Support;

class PreparedBundle
{
    public function __construct(
        public readonly string $dataFile,
        public readonly ?string $mediaDirectory = null,
    ) {}
}
