<?php

namespace Webkul\DAM\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Http\Controllers\Settings\DataTransfer\ImportController;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeTranslation;
use Webkul\DAM\Console\Commands\BackfillThumbnails;
use Webkul\DAM\Console\Commands\CleanupOrphanedAssetResourceMappings;
use Webkul\DAM\Console\Commands\DamInstaller;
use Webkul\DAM\Console\Commands\DamUpdate;
use Webkul\DAM\Console\Commands\DamUpdateRestore;
use Webkul\DAM\Console\Commands\DamVersion;
use Webkul\DAM\Console\Commands\GenerateScaleData;
use Webkul\DAM\Console\Commands\MoveDamAssetsToS3;
use Webkul\DAM\Console\Commands\SeedDamDemoData;
use Webkul\DAM\Contracts\Adapters\CategoryResolver;
use Webkul\DAM\Contracts\Adapters\ProductResolver;
use Webkul\DAM\Helpers\Exporters\Product\Exporter as DamProductExporter;
use Webkul\DAM\Helpers\Exporters\Product\MeasurementAwareExporter;
use Webkul\DAM\Helpers\Importers\Product\Importer as DamProductImporter;
use Webkul\DAM\Helpers\Importers\Product\MeasurementAwareImporter;
use Webkul\DAM\Helpers\Normalizers\ProductValuesNormalizer;
use Webkul\DAM\Http\Middleware\DAM;
use Webkul\DAM\Integrations\Pim\PimCategoryResolver;
use Webkul\DAM\Integrations\Pim\PimProductResolver;
use Webkul\DAM\Integrations\Standalone\NullCategoryResolver;
use Webkul\DAM\Integrations\Standalone\NullProductResolver;
use Webkul\DAM\Models\DamConfiguration;
use Webkul\DAM\Repositories\DirectoryRolePermissionRepository;
use Webkul\DAM\Services\AssetWebhookDispatcher;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\DAM\Support\PimIntegration;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Validators\JobInstances\Import\CategoryJobValidator;
use Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator;
use Webkul\Measurement\Helpers\Exporters\ProductExporter as MeasurementExporter;
use Webkul\Measurement\Helpers\Importers\Product\Importer as MeasurementImporter;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;
use Webkul\User\Models\Role;
use Webkul\Webhook\Registry\EventRegistry;

class DAMServiceProvider extends ServiceProvider
{
    protected function pimEnabled(): bool
    {
        return PimIntegration::enabled();
    }

    /**
     * @param  class-string  $coreClass
     * @param  callable():class-string  $damClass
     */
    protected function bindPimAwareHandler(string $coreClass, callable $damClass): void
    {
        $this->app->bind($coreClass, fn (Application $app): object => $this->pimEnabled()
            ? $app->make($damClass())
            : $app->build($coreClass));
    }

    protected function registerAssetWebhookEvents(): void
    {
        if (
            ! class_exists(EventRegistry::class)
            || ! $this->app->bound(EventRegistry::class)
        ) {
            return;
        }

        $this->app->make(EventRegistry::class)->register('asset', [
            AssetWebhookDispatcher::EVENT_ASSET_CREATED => 'dam::app.webhook-events.asset.created',
            AssetWebhookDispatcher::EVENT_ASSET_UPDATED => 'dam::app.webhook-events.asset.updated',
        ]);
    }

    public function boot(Router $router)
    {
        $this->app->booted(function (): void {
            $this->bindPimAwareHandler(Exporter::class, fn (): string => class_exists(MeasurementExporter::class)
                ? MeasurementAwareExporter::class
                : DamProductExporter::class);

            $this->bindPimAwareHandler(Importer::class, fn (): string => class_exists(MeasurementImporter::class)
                ? MeasurementAwareImporter::class
                : DamProductImporter::class);

            $this->registerAssetWebhookEvents();
        });

        $router->aliasMiddleware('dam', DAM::class);

        RateLimiter::for('dam-share-thumb', function ($request) {
            return Limit::perMinute(1200)->by('thumb|'.$request->ip());
        });

        RateLimiter::for('dam-share-view', function ($request) {
            return Limit::perMinute(120)->by('view|'.$request->ip());
        });

        RateLimiter::for('dam-share-download', function ($request) {
            return Limit::perMinute(20)->by('dl|'.$request->ip());
        });

        RateLimiter::for('dam-ai-tagging', function () {
            $value = DamConfiguration::find('DAM_AI_TAGGING_RATE_LIMIT')?->value;

            $perMinute = ($value !== null && $value !== '')
                ? (int) $value
                : (int) config('dam.ai_tagging.rate_limit_per_minute', 60);

            return Limit::perMinute(max(1, min(120, $perMinute)));
        });

        Route::middleware(['web', 'admin', 'dam'])
            ->prefix(config('app.admin_url').'/dam')
            ->group(__DIR__.'/../Routes/explorer-routes.php');

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        Route::group([], __DIR__.'/../Routes/share-public.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'dam');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'dam');

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $commands = [
                DamInstaller::class,
                BackfillThumbnails::class,
                SeedDamDemoData::class,
                GenerateScaleData::class,
                DamVersion::class,
                DamUpdate::class,
                DamUpdateRestore::class,
            ];

            if ($this->pimEnabled()) {
                $commands[] = CleanupOrphanedAssetResourceMappings::class;
            }

            $this->commands($commands);
        }

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'dam');

        view()->composer('dam::admin.roles.dam-permissions-tab', function ($view) {
            $roleId = request()->route('id');
            $role = $roleId ? Role::find($roleId) : null;

            $settings = $role
                ? DB::table('dam_role_settings')->where('role_id', $role->id)->first()
                : null;

            $grantedIds = $role
                ? app(DirectoryRolePermissionRepository::class)->getAllGrantedIds($role->id)
                : [];
            $inheritChildren = (bool) ($settings?->inherit_children ?? false);

            $damEnabled = true;

            $view->with([
                'role'                       => $role,
                'grantedIds'                 => $grantedIds,
                'allDirectories'             => (bool) ($settings?->all_directories ?? false),
                'inheritChildren'            => $inheritChildren,
                'damEnabled'                 => $damEnabled,
                'routeDirectory'             => route('admin.dam.directory.index'),
                'routeDirectoryPaths'        => route('admin.dam.directory.paths'),
                'routeDirectoryChildren'     => route('admin.dam.directory.children', ['id' => '__ID__']),
                'routeDirectoryDescendants'  => route('admin.dam.directory.descendants', ['id' => '__ID__']),
            ]);
        });

        $this->publishes([
            __DIR__.'/../Resources/assets/images' => storage_path('app/public/dam'),
        ], 'dam-defaults');

        $this->publishes([
            __DIR__.'/../Resources/samples' => storage_path('app/public/data-transfer/samples'),
        ], 'dam-defaults');

        $this->publishes([
            __DIR__.'/../../publishable' => public_path('themes'),
        ], 'dam-config');
    }

    public function register()
    {
        $helpers = __DIR__.'/../Http/helpers.php';
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        $this->app->scoped(DirectoryPermissionService::class);

        $this->app->bind(ProductResolver::class, fn (): ProductResolver => $this->pimEnabled()
            ? new PimProductResolver
            : new NullProductResolver);

        $this->app->bind(CategoryResolver::class, fn (): CategoryResolver => $this->pimEnabled()
            ? new PimCategoryResolver
            : new NullCategoryResolver);

        $this->app->bind(Import::class, \Webkul\DAM\Helpers\Import::class);

        $this->app->bind(ImportController::class, \Webkul\DAM\Http\Controllers\Settings\DataTransfer\ImportController::class);

        if ($this->pimEnabled()) {
            $this->app->bind(ProductAttributeValuesNormalizer::class, ProductValuesNormalizer::class);

            $this->app->bind(\Webkul\DataTransfer\Helpers\Exporters\Category\Exporter::class, \Webkul\DAM\Helpers\Exporters\Category\Exporter::class);

            $this->app->bind(\Webkul\DataTransfer\Helpers\Importers\Category\Importer::class, \Webkul\DAM\Helpers\Importers\Category\Importer::class);

            $this->app->bind(ProductJobValidator::class, \Webkul\DAM\Validators\JobInstances\Import\ProductJobValidator::class);

            $this->app->bind(CategoryJobValidator::class, \Webkul\DAM\Validators\JobInstances\Import\CategoryJobValidator::class);

            $this->app->bind(ProductForm::class, \Webkul\DAM\Http\Requests\ProductForm::class);

            $this->app->bind(Attribute::class, \Webkul\DAM\Models\Attribute::class);

            $this->app->bind(AttributeTranslation::class, \Webkul\DAM\Models\AttributeTranslation::class);

            $this->app->bind(ProductDataGrid::class, \Webkul\DAM\DataGrids\Catalog\ProductDataGrid::class);
        }

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/api-acl.php', 'api-acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/dam.php', 'dam');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/importers.php', 'importers');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/exporters.php', 'exporters');

        $this->mergeConfigFrom(
            __DIR__.'/../Config/unopim-vite.php',
            'unopim-vite.viters'
        );

        if ($this->pimEnabled()) {
            $this->mergeConfigFrom(
                __DIR__.'/../Config/attribute_types.php',
                'attribute_types'
            );

            $this->mergeConfigFrom(
                __DIR__.'/../Config/category_field_types.php',
                'category_field_types'
            );
        }

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

        $this->commands([
            MoveDamAssetsToS3::class,
        ]);
    }
}
