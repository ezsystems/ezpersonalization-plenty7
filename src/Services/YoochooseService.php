<?php

namespace Yoochoose\Services;

use IO\Services\WebstoreConfigurationService;
use IO\Helper\TemplateContainer;

class YoochooseService
{
    public function getShopUrl() {
        /** @var WebstoreConfigurationService $webstoreConfig */
        $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
        $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();

        return $storeConf['domainSsl'];
    }

    public function getTemplate() {
        /** @var TemplateContainer $templateContainer */
        $templateContainer = pluginApp(TemplateContainer::class);
        $storeConf = $templateContainer->getTemplate();

        return $storeConf['domainSsl'];
    }
}