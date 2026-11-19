<?php

declare(strict_types=1);

namespace Webkul\DAM\Contracts\Adapters;

interface AttributeResolver extends PimIntegration
{
    public function modelClass(): ?string;

    public function translationModelClass(): ?string;
}
