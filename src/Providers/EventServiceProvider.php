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

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
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

    /**
     * Load events
     */
    public function boot()
    {
        Event::listen('unopim.admin.categories.dynamic-fields.control.'.self::ASSET_CATEGORY_FIELD_TYPE.'.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::asset.catalog.categories.dynamic-fields.asset-control');
        });

        Event::listen('unopim.admin.products.dynamic-attribute-fields.control.'.self::ASSET_ATTRIBUTE_TYPE.'.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::asset.catalog.products.dynamic-attribute-fields.asset-control');
        });

        Event::listen('unopim.admin.layout.head.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::style');
        });

        Event::listen('unopim.admin.settings.roles.edit.card.access-control.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::admin.roles.dam-permissions-tab');
        });

        // Create page uses an underscored event name (`access_control` vs
        // edit's `access-control`). Listen to both so the tab renders in
        // both create + edit flows.
        Event::listen('unopim.admin.settings.roles.create.card.access_control.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::admin.roles.dam-permissions-tab');
        });

        $syncDirectoryGrants = static function ($role) {
            if (! $role) {
                return;
            }

            // Marker is rendered inside the DAM tab's v-if wrapper. Absent
            // marker = tab not shown (e.g. permission_type=all) so we leave
            // existing grants alone. Present marker = the submitted
            // `directories[]` set is authoritative, including empty.
            if (! request()->boolean('dam_directory_grants_managed')) {
                return;
            }

            $directoryIds = array_values(array_filter(
                array_map('intval', (array) request('directories', [])),
                fn ($id) => $id > 0
            ));

            // Defensive filter against stale form data — a user can have
            // an old edit tab open while another admin deletes a directory,
            // so the submission may reference an id that no longer exists.
            if (! empty($directoryIds)) {
                $directoryIds = Directory::whereIn('id', $directoryIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            // No selection — fall back to the root grant so the role keeps
            // a baseline entry point (mirrors the backfill migration).
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

            // When inherit_children is on, the blade pre-checks all descendants
            // so submitted directories[] contains both explicit grants and
            // inherit-expanded descendants. Strip only the expanded ones:
            // keep items that were already an explicit grant in the DB (e.g.
            // auto-granted by another admin's directory creation), and keep
            // new root-level selections. This prevents accumulation of
            // descendants in the DB while preserving intentional child grants.
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
