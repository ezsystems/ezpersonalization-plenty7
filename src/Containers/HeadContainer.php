<?php

namespace Yoochoose\Containers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Helpers\Data;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use IO\Services\WebstoreConfigurationService;
use IO\Services\TemplateService;
use IO\Services\SessionStorageService;
use IO\Constants\SessionStorageKeys;
use IO\Services\CustomerService;

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
     * @param SessionStorageService $sessionStorage
     * @param OrderRepositoryContract $orderRepositoryContract
     * @return string
     * @throws \Exception
     */
    public function call(Twig $twig, templateService $templateService, SettingsService $settingsService,
                         SessionStorageService $sessionStorage, OrderRepositoryContract $orderRepositoryContract,
                         CustomerService $customerService):string
    {
        $mandator = $settingsService->getSettingsValue('customer_id');
        $customerId = $customerService->getContactId();
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

        switch ($currentTemplate) {
            case 'tpl.item':
                $currentPage = 'product';
                break;
            case 'tpl.category.item':
                $currentPage = 'category';
                break;
            case 'tpl.basket':
                $currentPage = 'cart';
                break;
            case 'tpl.home':
                $currentPage = 'home';
                break;
            default:
                $currentPage = '';
                break;
        }

        $order = '';
        if ($currentTemplate === 'tpl.confirmation') {
            $orderId = $sessionStorage->getSessionValue(SessionStorageKeys::LATEST_ORDER_ID);
            $order = $orderRepositoryContract->findOrderById($orderId)->toArray();
            foreach ($order['orderItems'] as $orderItems) {
                $orderDetails[] = [
                    'itemId' => $orderItems['id'],
                    'quantity' => $orderItems['quantity'],
                    'price' => $orderItems['id'],
                    'currency' => $orderItems['id'],
                ];
            }
        }

        if ($ycOverwriteEndpoint) {
            $scriptOverwrite = (!preg_match('/^(http|\/\/)/', $ycOverwriteEndpoint) ? '//' : '') . $ycOverwriteEndpoint;
            $scriptUrl = preg_replace('(^https?:)', '', $scriptOverwrite);
        }  else {
            $scriptUrl = $settingsService->getSettingsValue('performance') == 1 ?
                self::AMAZON_CDN_SCRIPT : self::YOOCHOOSE_CDN_SCRIPT;
        }
        
        $scriptUrl = rtrim($scriptUrl, '/') . '/';
        $scriptUrl = $scriptUrl . "v1/{$mandator}{$plugin}/tracking.";

        $template = [
            'shopUrl' => $storeConf['domainSsl'] . '/',
            'webStoreId' => $dataHelper->getStoreId(),
            'orderData' => json_encode($order),
            'currentPage' => $currentPage,
            'ycCustomerId' => (int)$customerId,
            'ycEnableSearch' => $ycEnableSearch,
            'ycJsScript' => $scriptUrl . 'js',
            'ycCssScript' => $scriptUrl . 'css',
            'itemType' => $itemType,
        ];

        return $twig->render('Yoochoose::content.head', $template);
    }
}