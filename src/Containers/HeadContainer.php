<?php

namespace Yoochoose\Containers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Helpers\Data;
use Plenty\Plugin\Templates\Twig;
use IO\Services\WebstoreConfigurationService;
use IO\Services\TemplateService;

class HeadContainer
{
    const YOOCHOOSE_CDN_SCRIPT = '//event.yoochoose.net/cdn';
    const AMAZON_CDN_SCRIPT = '//cdn.yoochoose.net';

    public function __construct()
    {
    }

    /**
     * @param Twig $twig
     * @param TemplateService $templateService
     * @param SettingsService $settingsService
     * @return string
     * @throws \Exception
     */
    public function call(Twig $twig, TemplateService $templateService, SettingsService $settingsService):string
    {
        $currentPage = '';

        $mandator = $settingsService->getSettingsValue('customer_id');
        $plugin = $settingsService->getSettingsValue('plugin_id');
        $ycOverwriteEndpoint = $settingsService->getSettingsValue('script_id');
        $ycEnableSearch = $settingsService->getSettingsValue('search_enable');
        $itemType = $settingsService->getSettingsValue('item_type');

        /** @var Data $dataHelper */
        $dataHelper = pluginApp(Data::class);

        /** @var WebstoreConfigurationService $webstoreConfig */
        $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
        $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();

        $currentTemplate = $templateService->getCurrentTemplate();

        if ($currentTemplate === 'tpl.item') {
            $currentPage = 'product';
        } else if ($currentTemplate === 'tpl.category.item') {
            $currentPage = 'category';
        } else if ($currentTemplate === 'tpl.basket') {
            $currentPage = 'cart';
        } else if ($currentTemplate === 'tpl.home') {
            $currentPage = 'home';
        }

        if ($ycOverwriteEndpoint) {
            $scriptOverwrite = (!preg_match('/^(http|\/\/)/', $ycOverwriteEndpoint) ? '//' : '') . $ycOverwriteEndpoint;
            $scriptUrl = preg_replace('(^https?:)', '', $scriptOverwrite);
        }  else {
            $scriptUrl = $settingsService->getSettingsValue('performance') == 1 ?
                self::AMAZON_CDN_SCRIPT : self::YOOCHOOSE_CDN_SCRIPT;
        }

        $scriptUrl = $scriptUrl . "v1/{$mandator}/{$plugin}/tracking.";

        $template = [
            'shopUrl' => $storeConf['domainSsl'] . '/',
            'webStoreId' => $dataHelper->getStoreId(),
            'currentPage' => $currentPage,
            'ycCustomerId' => $mandator,
            'ycEnableSearch' => $ycEnableSearch,
            'ycJsScript' => $scriptUrl . 'js',
            'ycCssScript' => $scriptUrl . 'css',
            'itemType' => $itemType,
        ];

        return $twig->render('Yoochoose::content.head', $template);
    }
}