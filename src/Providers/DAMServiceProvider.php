<?php

namespace Webkul\DAM\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeTranslation;
use Webkul\DAM\Console\Commands\DamInstaller;
use Webkul\DAM\Console\Commands\MoveDamAssetsToS3;
use Webkul\DAM\Helpers\Normalizers\ProductValuesNormalizer;
use Webkul\DAM\Http\Middleware\DAM;
use Webkul\DAM\Repositories\DirectoryRepository;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\User\Models\Role;

class DAMServiceProvider extends ServiceProvider
{
    /**
     * The container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        Exporter::class                                                 => \Webkul\DAM\Helpers\Exporters\Product\Exporter::class,
        ProductAttributeValuesNormalizer::class                         => ProductValuesNormalizer::class,
        \Webkul\DataTransfer\Helpers\Exporters\Category\Exporter::class => \Webkul\DAM\Helpers\Exporters\Category\Exporter::class,
        Importer::class                                                 => \Webkul\DAM\Helpers\Importers\Product\Importer::class,
        \Webkul\DataTransfer\Helpers\Importers\Category\Importer::class => \Webkul\DAM\Helpers\Importers\Category\Importer::class,
        Attribute::class                                                => \Webkul\DAM\Models\Attribute::class,
        AttributeTranslation::class                                     => \Webkul\DAM\Models\AttributeTranslation::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function boot(Router $router)
    {
        $router->aliasMiddleware('dam', DAM::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'dam');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'dam');

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                DamInstaller::class,
            ]);
        }

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'dam');

        view()->composer('dam::admin.roles.dam-permissions-tab', function ($view) {
            $roleId = request()->route('id');
            $role = $roleId ? Role::find($roleId) : null;

            $view->with([
                'role'          => $role,
                'directoryTree' => app(DirectoryRepository::class)
                    ->getFullDirectoryTreeOnly(),
                'grantedIds'    => $role
                    ? app(DirectoryRolePermissionRepository::class)
                        ->getDirectoryIdsForRole($role->id)
                    : [],
            ]);
        });

        $this->publishes([
            __DIR__.'/../Resources/assets/images' => storage_path('app/public/dam'),
        ], 'dam-defaults');

        $this->publishes([
            __DIR__.'/../../publishable' => public_path('themes'),
        ], 'dam-config');
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        // Load DAM-only global helper functions (dam_can_view_dir, etc.).
        // Loaded here rather than via composer.json `autoload.files` so DAM
        // stays self-contained without touching the root composer.json.
        $helpers = __DIR__.'/../Http/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/api-acl.php', 'api-acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/dam.php', 'dam');

        $this->mergeConfigFrom(
            __DIR__.'/../Config/unopim-vite.php',
            'unopim-vite.viters'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php',
            'attribute_types'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/category_field_types.php',
            'category_field_types'
        );

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->commands([
            MoveDamAssetsToS3::class,
        ]);
    }
}
