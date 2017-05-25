<?php
namespace Yoochoose\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Frontend\Services;
use Plenty\Modules\System\Models;

use IO\Helper\TemplateContainer;

class ContentController extends Controller
{
    
    private $container;
    
    public function __construct(TemplateContainer $container)
    {
        $this->container = $container;
    }
    
    /**
	 * @param Twig $twig
	 * @return string
	 */
	public function sayHello(Twig $twig):string
	{
        $test = $this->container->getTemplateData();
		return $twig->render('Yoochoose::content.hello', ['test' => json_encode($test)]);
        
	}
}
