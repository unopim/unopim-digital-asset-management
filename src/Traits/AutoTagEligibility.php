<?php

namespace Webkul\DAM\Traits;

trait AutoTagEligibility
{
    protected function autoTagEligible(): bool
    {
        return bouncer()->hasPermission('dam.asset.update') && bouncer()->hasPermission('dam.tags.create');
    }
}
