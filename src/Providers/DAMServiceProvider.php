<?php

namespace Webkul\DAM\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\DAM\Console\Commands\DamInstaller;
use Webkul\DAM\Http\Middleware\DAM;

class DAMServiceProvider extends ServiceProvider
{
    /**
     * The container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        \Webkul\DataTransfer\Helpers\Exporters\Product\Exporter::class     => \Webkul\DAM\Helpers\Exporters\Product\Exporter::class,
        \Webkul\Product\Normalizer\ProductAttributeValuesNormalizer::class => \Webkul\DAM\Helpers\Normalizers\ProductValuesNormalizer::class,
        \Webkul\DataTransfer\Helpers\Exporters\Category\Exporter::class    => \Webkul\DAM\Helpers\Exporters\Category\Exporter::class,
        \Webkul\DataTransfer\Helpers\Importers\Product\Importer::class     => \Webkul\DAM\Helpers\Importers\Product\Importer::class,
        \Webkul\DataTransfer\Helpers\Importers\Category\Importer::class    => \Webkul\DAM\Helpers\Importers\Category\Importer::class,
        \Webkul\Attribute\Models\Attribute::class                          => \Webkul\DAM\Models\Attribute::class,
        \Webkul\Attribute\Models\AttributeTranslation::class               => \Webkul\DAM\Models\AttributeTranslation::class,
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

        $this->publishes([
            __DIR__.'/../Resources/assets/images' => storage_path('app/public/dam'),
        ]);

        $this->publishes([
            __DIR__.'/../../publishable' => public_path('themes'),
        ], 'dam-config');
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(
            __DIR__.'/../Config/unopim-vite.php', 'unopim-vite.viters'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/attribute_types.php', 'attribute_types'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/category_field_types.php', 'category_field_types'
        );

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');

    }
}
