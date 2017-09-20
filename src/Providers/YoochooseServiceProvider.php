<?php
namespace Yoochoose\Providers;

use Plenty\Plugin\ServiceProvider;

/**
 * Class YoochooseServiceProvider
 * @package Yoochoose\Providers
 */
class YoochooseServiceProvider extends ServiceProvider
{
    
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->getApplication()->register(YoochooseRouteServiceProvider::class);
    }

}
