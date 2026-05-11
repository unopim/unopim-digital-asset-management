<?php

use Webkul\DAM\Services\DirectoryPermissionService;

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
