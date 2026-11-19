<?php

use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Support\PimIntegration;

if (! function_exists('dam_can_view_dir')) {
    function dam_can_view_dir(int $directoryId): bool
    {
        return app(DirectoryPermissionService::class)->canView($directoryId);
    }
}

if (! function_exists('dam_viewable_directory_ids')) {
    function dam_viewable_directory_ids(): array
    {
        return app(DirectoryPermissionService::class)->viewableIds();
    }
}

if (! function_exists('dam_acl_bypass')) {
    function dam_acl_bypass(): bool
    {
        return app(DirectoryPermissionService::class)->bypass();
    }
}

if (! function_exists('dam_accessible_dir_ids')) {
    function dam_accessible_dir_ids(): array
    {
        return app(DirectoryPermissionService::class)->directlyGrantedIds();
    }
}

if (! function_exists('dam_can_access_dir')) {
    function dam_can_access_dir(int $directoryId): bool
    {
        return app(DirectoryPermissionService::class)->canAccess($directoryId);
    }
}

if (! function_exists('dam_pim_enabled')) {
    function dam_pim_enabled(): bool
    {
        return PimIntegration::enabled();
    }
}

if (! function_exists('dam_pim_disabled')) {
    function dam_pim_disabled(): bool
    {
        return ! PimIntegration::enabled();
    }
}
