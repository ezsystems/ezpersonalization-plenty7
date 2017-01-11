<?php

namespace Yoochoose\Containers;

use Plenty\Plugin\Templates\Twig;

class HeadContainer
{
    /**
     * @param Twig $twig
     * @return string
     */
    public function call(Twig $twig):string
    {
        return $twig->render('Yoochoose::content.head');
    }
}