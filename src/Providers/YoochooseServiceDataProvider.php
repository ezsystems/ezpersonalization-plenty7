<?php

namespace Yoochoose\Providers;

use Plenty\Plugin\Templates\Twig;

/**
 * Class YoochooseServiceDataProvider
 * @package Yoochoose\Providers
 */
class YoochooseServiceDataProvider
{
    /**
     * @param Twig $twig
     * @param $args
     * @return string
     */
    public function call(
        Twig $twig,
        $args
    ) {
        return $twig->render('Yoochoose::content.head');
    }
}