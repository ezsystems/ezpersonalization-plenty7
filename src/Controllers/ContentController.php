<?php
namespace Yoochoose\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Frontend\Services;
use Plenty\Modules\System\Models;

use IO\Helper\TemplateContainer;

/**
 * Class ContentController
 * @package Yoochoose\Controllers
 */
class ContentController extends Controller
{
    /**
     * @var TemplateContainer
     */
    private $container;

    /**
     * ContentController constructor.
     * @param TemplateContainer $container
     */
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
