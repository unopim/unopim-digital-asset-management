<?php

namespace Webkul\DAM\Traits;

trait AutoTagEligibility
{
    /**
     * Auto-tagging is functionally "edit the asset" plus "create a tag",
     * so both permissions must be held independently of upload rights.
     */
    protected function autoTagEligible(): bool
    {
        return bouncer()->hasPermission('dam.asset.update') && bouncer()->hasPermission('dam.tags.create');
    }
}
