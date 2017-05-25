<?php
namespace Yoochoose\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Templates\Twig;

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

	public function boot(Twig $twig)
	{
		// Register Twig String Loader to use function: template_from_string
		$twig->addExtension('Twig_Extension_StringLoader');
	}
}
