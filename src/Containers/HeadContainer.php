<?php

namespace Yoochoose\Containers;

use IO\Services\CustomerService;
use IO\Services\SessionStorageService;
use IO\Services\TemplateService;
use IO\Services\WebstoreConfigurationService;
use Plenty\Modules\Item\Variation\Contracts\VariationRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Templates\Twig;
use Yoochoose\Helpers\Data;
use Yoochoose\Providers\YoochooseServiceProvider;
use Yoochoose\Services\SettingsService;

class HeadContainer
{
    const YOOCHOOSE_CDN_SCRIPT = '//event.yoochoose.net/cdn';
    const AMAZON_CDN_SCRIPT = '//cdn.yoochoose.net';

    /**
     * HeadContainer constructor.
     */
    public function __construct()
    {}

    /**
     * @param Twig $twig
     * @param Data $dataHelper
     * @param WebstoreConfigurationService $webStoreConfig
     * @param TemplateService $templateService
     * @param SettingsService $settingsService
     * @param SessionStorageService $sessionStorage
     * @param OrderRepositoryContract $orderRepositoryContract
     * @param CustomerService $customerService
     * @param VariationRepositoryContract $variationRepository
     * @return string
     */
    public function call(
        Twig $twig,
        Data $dataHelper,
        WebstoreConfigurationService $webStoreConfig,
        templateService $templateService,
        SettingsService $settingsService,
        SessionStorageService $sessionStorage,
        OrderRepositoryContract $orderRepositoryContract,
        CustomerService $customerService,
        VariationRepositoryContract $variationRepository
    ): string
    {
        $customerId = $customerService->getContactId();
        $ycEnableSearch = $settingsService->getSettingsValue('search_enable');
        $itemType = $settingsService->getSettingsValue('item_type');
        $mandator = $settingsService->getSettingsValue('customer_id');
        $plugin = $settingsService->getSettingsValue('plugin_id');
        $ycOverwriteEndpoint = $settingsService->getSettingsValue('script_id');

        $storeConf = $webStoreConfig->getWebstoreConfig()->toArray();
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
            case 'tpl.confirmation':
                $currentPage = 'buyout';
                break;
            default:
                $currentPage = '';
                break;
        }

        $orderDetails = [];
        if ($currentTemplate === 'tpl.confirmation') {
            $orderId = $sessionStorage->getSessionValue(YoochooseServiceProvider::YC_LAST_ORDER_ID);
            if ($orderId) {
                $order = $orderRepositoryContract->findOrderById($orderId)->toArray();
                foreach ($order['orderItems'] as $orderItem) {
                    if ($orderItem['id'] == 0) {
                        continue;
                    }

                    $amount = $orderItem['amounts'][0] ?? null;
                    $price = $amount ? $amount['priceGross'] : 0;
                    $currency = $amount ? $amount['currency'] : '';

                    $variation = $variationRepository->findById($orderItem['itemVariationId']);
                    if (!$variation) {
                        continue;
                    }

                    $orderDetails[] = [
                        'id' => $variation->itemId,
                        'qty' => $orderItem['quantity'],
                        'price' => $price,
                        'currency' => $currency,
                    ];
                }
            }
        }

        if ($ycOverwriteEndpoint) {
            $scriptOverwrite = (!preg_match('/^(http|\/\/)/', $ycOverwriteEndpoint) ? '//' : '') . $ycOverwriteEndpoint;
            $scriptUrl = preg_replace('(^https?:)', '', $scriptOverwrite);
        } else {
            $scriptUrl = $settingsService->getSettingsValue('performance') == 1 ?
                self::AMAZON_CDN_SCRIPT : self::YOOCHOOSE_CDN_SCRIPT;
        }

        $scriptUrl = rtrim($scriptUrl, '/') . '/';
        $scriptUrl = $scriptUrl . "v1/{$mandator}{$plugin}/tracking.";

        $template = [
            'shopUrl' => $storeConf['domainSsl'] . '/',
            'webStoreId' => $dataHelper->getStoreId(),
            'orderData' => json_encode($orderDetails),
            'currentPage' => $currentPage,
            'ycCustomerId' => (int)$customerId,
            'ycEnableSearch' => $ycEnableSearch,
            'ycJsScript' => $scriptUrl . 'js',
            'ycCssScript' => $scriptUrl . 'css',
            'itemType' => $itemType,
        ];

        return $twig->render('YoochoosePersonalizationEngine::content.head', $template);
    }

}