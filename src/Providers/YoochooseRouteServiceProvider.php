<?php
namespace Yoochoose\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

class YoochooseRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param Router $router
     */
    public function map(Router $router)
    {
        $router->get('yoochoose/products', 'Yoochoose\Controllers\FrontController@export');
        $router->get('yoochoose/export', 'Yoochoose\Controllers\ExportController@init');
        $router->get('yoochoose/trigger', 'Yoochoose\Controllers\TriggerController@export');
        
        //settings
        $router->post('yoochoose/settings/', 'Yoochoose\Controllers\SettingsController@saveSettings');
        $router->get('yoochoose/settings/', 'Yoochoose\Controllers\SettingsController@loadSettings');
    }
}
