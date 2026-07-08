<?php

namespace Webkul\DAM\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\Theme\ViewRenderEventManager;

class EventServiceProvider extends ServiceProvider
{
    const ASSET_ATTRIBUTE_TYPE = 'asset';

    const ASSET_CATEGORY_FIELD_TYPE = 'asset';

    protected $listen = [
        'catalog.product.create.after' => [
            'Webkul\DAM\Listeners\Product@afterCreateOrupdate',
        ],

        'catalog.product.update.after' => [
            'Webkul\DAM\Listeners\Product@afterCreateOrupdate',
        ],

        'catalog.category.create.after' => [
            'Webkul\DAM\Listeners\Category@afterUpdateOrCreate',
        ],

        'catalog.category.update.after' => [
            'Webkul\DAM\Listeners\Category@afterUpdateOrCreate',
        ],
    ];

    /** Load events. */
    public function boot()
    {
        Event::listen('unopim.admin.categories.dynamic-fields.control.'.self::ASSET_CATEGORY_FIELD_TYPE.'.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::asset.catalog.categories.dynamic-fields.asset-control');
        });

        Event::listen('unopim.admin.products.dynamic-attribute-fields.control.'.self::ASSET_ATTRIBUTE_TYPE.'.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::asset.catalog.products.dynamic-attribute-fields.asset-control');
        });

        /**
         * Inject the DAM asset cell + picker into the product bulk-edit spreadsheet.
         *
         * The core bulk-edit editor exposes no dedicated hook, so we ride the global
         * layout event that fires inside `#app` (Vue's mount root) and before the
         * `scripts` stack — guarding on the route so nothing loads on other pages.
         * This keeps the whole feature inside the DAM package: no core file changes.
         */
        Event::listen('unopim.admin.layout.content.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            if (! request()->routeIs('admin.catalog.products.bulkedit')) {
                return;
            }

            $viewRenderEventManager->addTemplate('dam::catalog.products.bulk-edit.asset');
        });

        Event::listen('unopim.admin.layout.head.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::style');
        });

        Event::listen('unopim.admin.settings.roles.edit.card.access-control.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::admin.roles.dam-permissions-tab');
        });

        Event::listen('unopim.admin.settings.roles.create.card.access_control.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::admin.roles.dam-permissions-tab');
        });

        $syncDirectoryGrants = static function ($role) {
            if (! $role) {
                return;
            }

            if (! request()->boolean('dam_directory_grants_managed')) {
                return;
            }

            $directoryIds = array_values(array_filter(
                array_map('intval', (array) request('directories', [])),
                fn ($id) => $id > 0
            ));

            if (! empty($directoryIds)) {
                $directoryIds = Directory::whereIn('id', $directoryIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            if (empty($directoryIds)) {
                $rootId = Directory::whereNull('parent_id')
                    ->orderBy('id')
                    ->value('id');

                if ($rootId) {
                    $directoryIds = [(int) $rootId];
                }
            }

            $allDirectories = request()->boolean('dam_all_directories');
            $inheritChildren = request()->boolean('dam_inherit_children');

            if ($inheritChildren && count($directoryIds) > 1) {
                $existingGrants = app(DirectoryRolePermissionRepository::class)
                    ->getDirectoryIdsForRole((int) $role->id);

                $directoryIds = DB::table('dam_directories as d')
                    ->whereIn('d.id', $directoryIds)
                    ->where(function ($q) use ($directoryIds, $existingGrants) {
                        if (! empty($existingGrants)) {
                            $q->whereIn('d.id', $existingGrants);
                        }
                        $q->orWhereNotExists(function ($sub) use ($directoryIds) {
                            $sub->from('dam_directories as ancestor')
                                ->whereIn('ancestor.id', $directoryIds)
                                ->whereColumn('ancestor._lft', '<', 'd._lft')
                                ->whereColumn('ancestor._rgt', '>', 'd._rgt');
                        });
                    })
                    ->pluck('d.id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
            }

            DB::table('dam_role_settings')->updateOrInsert(
                ['role_id' => (int) $role->id],
                [
                    'all_directories'  => $allDirectories,
                    'inherit_children' => $inheritChildren,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );

            app(DirectoryRolePermissionRepository::class)
                ->syncForRole((int) $role->id, $directoryIds);

            app(DirectoryPermissionService::class)->flush();
        };

        Event::listen('user.role.update.after', $syncDirectoryGrants);
        Event::listen('user.role.create.after', $syncDirectoryGrants);
    }
}
