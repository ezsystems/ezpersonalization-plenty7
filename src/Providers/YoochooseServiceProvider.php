<?php

namespace Yoochoose\Providers;

use IO\Services\SessionStorageService;
use Plenty\Modules\Order\Events\OrderCreated;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;

/**
 * Class YoochooseServiceProvider
 * @package Yoochoose\Providers
 */
class YoochooseServiceProvider extends ServiceProvider
{

    const YC_LAST_ORDER_ID = 'yoochooseLastOrderId';

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->getApplication()->register(YoochooseRouteServiceProvider::class);

        // Hook onto Order Created event and save order id into plugin session storage
        $dispatcher = pluginApp(Dispatcher::class);
        $dispatcher->listen(OrderCreated::class,
            function (OrderCreated $event) use ($dispatcher) {
                $orderId = $event->getOrder()->id;

                /** @var SessionStorageService $sessionStorage */
                $sessionStorage = pluginApp(SessionStorageService::class);
                $sessionStorage->setSessionValue(static::YC_LAST_ORDER_ID, $orderId);
            });
    }

}
