<?php

namespace Webkul\DAM\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
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

        Event::listen('unopim.admin.layout.head', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('dam::style');
        });
    }
}
