<?php
namespace Yoochoose\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * Class YoochooseRouteServiceProvider
 * @package Yoochoose\Providers
 */
class YoochooseRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param Router $router
     */
    public function map(Router $router)
    {
        $router->get('hello', 'Yoochoose\Controllers\ContentController@sayHello');
        $router->get('yoochoose/export', 'Yoochoose\Controllers\ExportController@export');

        //settings
        $router->post('yoochoose/settings/', 'Yoochoose\Controllers\SettingsController@saveSettings');
        $router->get('yoochoose/settings/', 'Yoochoose\Controllers\SettingsController@loadSettings');
    }

}
