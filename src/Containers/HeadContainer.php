<?php

namespace Yoochoose\Containers;

use IO\Constants\SessionStorageKeys;
use IO\Services\CustomerService;
use IO\Services\SessionStorageService;
use IO\Services\TemplateService;
use IO\Services\WebstoreConfigurationService;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Templates\Twig;
use Yoochoose\Helpers\Data;
use Yoochoose\Services\SettingsService;

class HeadContainer
{
    const YOOCHOOSE_CDN_SCRIPT = '//event.yoochoose.net/cdn';
    const AMAZON_CDN_SCRIPT = '//cdn.yoochoose.net';
    /**
     * @var TemplateService
     */
    private $templateService;
    /**
     * @var SettingsService
     */
    private $settingsService;
    /**
     * @var SessionStorageService
     */
    private $sessionStorage;
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepositoryContract;
    /**
     * @var CustomerService
     */
    private $customerService;

    /**
     * HeadContainer constructor.
     * @param TemplateService $templateService
     * @param SettingsService $settingsService
     * @param SessionStorageService $sessionStorage
     * @param OrderRepositoryContract $orderRepositoryContract
     * @param CustomerService $customerService
     */
    public function __construct(
        templateService $templateService,
        SettingsService $settingsService,
        SessionStorageService $sessionStorage,
        OrderRepositoryContract $orderRepositoryContract,
        CustomerService $customerService
    )
    {
        $this->templateService = $templateService;
        $this->settingsService = $settingsService;
        $this->sessionStorage = $sessionStorage;
        $this->orderRepositoryContract = $orderRepositoryContract;
        $this->customerService = $customerService;
    }

    /**
     * @param Twig $twig
     * @param Data $dataHelper
     * @param WebstoreConfigurationService $webStoreConfig
     * @return string
     */
    public function call(Twig $twig, Data $dataHelper, WebstoreConfigurationService $webStoreConfig): string
    {
        $customerId = $this->customerService->getContactId();
        $ycEnableSearch = $this->settingsService->getSettingsValue('search_enable');
        $itemType = $this->settingsService->getSettingsValue('item_type');

        $storeConf = $webStoreConfig->getWebstoreConfig()->toArray();
        $currentTemplate = $this->templateService->getCurrentTemplate();

        $orderDetails = [];
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
                $orderDetails = $this->getOrderDetails();
                break;
            default:
                $currentPage = '';
                break;
        }

        $scriptUrl = $this->getScriptUrl();

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

        return $twig->render('Yoochoose::content.head', $template);
    }

    /**
     * Returns array of order items
     *
     * @return array
     */
    private function getOrderDetails(): array
    {
        $result = [];
        $orderId = $this->sessionStorage->getSessionValue(SessionStorageKeys::LATEST_ORDER_ID);
        $order = $this->orderRepositoryContract->findOrderById($orderId)->toArray();
        foreach ($order['orderItems'] as $orderItems) {
            $result[] = [
                'itemId' => $orderItems['id'],
                'quantity' => $orderItems['quantity'],
                'price' => $orderItems['id'],
                'currency' => $orderItems['id'],
            ];
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getScriptUrl()
    {
        $mandator = $this->settingsService->getSettingsValue('customer_id');
        $plugin = $this->settingsService->getSettingsValue('plugin_id');
        $ycOverwriteEndpoint = $this->settingsService->getSettingsValue('script_id');

        if ($ycOverwriteEndpoint) {
            $scriptOverwrite = (!preg_match('/^(http|\/\/)/', $ycOverwriteEndpoint) ? '//' : '') . $ycOverwriteEndpoint;
            $scriptUrl = preg_replace('(^https?:)', '', $scriptOverwrite);
        } else {
            $scriptUrl = $this->settingsService->getSettingsValue('performance') == 1 ?
                self::AMAZON_CDN_SCRIPT : self::YOOCHOOSE_CDN_SCRIPT;
        }

        return rtrim($scriptUrl, '/') . "/v1/{$mandator}{$plugin}/tracking.";
    }
}