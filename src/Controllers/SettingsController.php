<?php

namespace Yoochoose\Controllers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Helpers\Data;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Plugin\Http\Response;

class SettingsController extends Controller
{
    const YOOCHOOSE_LICENSE_URL = 'https://admin.yoochoose.net/api/v4/';

    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var WebstoreHelper
     */
    private $storeHelper;

    /**
     * @var Response
     */
    private $response;

    /**
     * SettingsController constructor.
     * @param SettingsService $settingsService
     * @param Data $helper
     * @param WebstoreHelper $storeHelper
     * @param Response $response
     */
    public function __construct
    (
        SettingsService $settingsService,
        Data $helper,
        WebstoreHelper $storeHelper,
        Response $response
    ) {
        $this->settingsService = $settingsService;
        $this->helper = $helper;
        $this->storeHelper = $storeHelper;
        $this->response = $response;
    }

    /**
     * @param Request $request
     * @return mixed|string
     */
    public function saveSettings(Request $request)
    {
        $configFields = [];

        $configFields['yc_test'] = $request->get('yc_test');
        $configFields['customer_id'] = $request->get('customer_id');
        $configFields['license_key'] = $request->get('license_key');
        $configFields['plugin_id'] = $request->get('plugin_id');
        $configFields['design'] = $request->get('design');
        $configFields['item_type'] = $request->get('item_type');
        $configFields['script_id'] = $request->get('script_id');
        $configFields['search_enable'] = $request->get('search_enable');
        $configFields['performance'] = $request->get('performance');
        $configFields['log_severity'] = $request->get('log_severity');
        $configFields['token'] = $request->get('token');

        foreach ($configFields as $key => $value) {
            if(!empty($value)) {
                switch ($key) {
                    case 'yc_test':
                        $this->settingsService->setSettingsValue('yc_test', $value);
                        break;
                    case 'customer_id':
                        $this->settingsService->setSettingsValue('customer_id', $value);
                        break;
                    case 'license_key':
                        $this->settingsService->setSettingsValue('license_key', $value);
                        break;
                    case 'plugin_id':
                        $this->settingsService->setSettingsValue('plugin_id', $value);
                        break;
                    case 'design':
                        $this->settingsService->setSettingsValue('design', $value);
                        break;
                    case 'item_type':
                        $this->settingsService->setSettingsValue('item_type', $value);
                        break;
                    case 'script_id':
                        $this->settingsService->setSettingsValue('script_id', $value);
                        break;
                    case 'search_enable':
                        $this->settingsService->setSettingsValue('search_enable', $value);
                        break;
                    case 'performance':
                        $this->settingsService->setSettingsValue('performance', $value);
                        break;
                    case 'log_severity':
                        $this->settingsService->setSettingsValue('log_severity', $value);
                        break;
                    case 'token':
                        $this->settingsService->setSettingsValue('token', $value);
                        break;
                }
            }
        }

        $token = $this->settingsService->getSettingsValue('token');
        if (!$token) {
			throw new \Exception('Token must be set!');
        }

        /** @var \Plenty\Modules\System\Models\WebstoreConfiguration $webstoreConfig */
        $webstoreConfig = $this->storeHelper->getCurrentWebstoreConfiguration();
        if (is_null($webstoreConfig)) {
            throw new \Exception('error');
        }
        $baseURL = $webstoreConfig->domain;
        $customerId = $this->settingsService->getSettingsValue('customer_id');
        $licenseKey = $this->settingsService->getSettingsValue('license_key');

        $body = [
            'base' => [
                'type' => "MAGENTO2",
                'pluginId' => $this->settingsService->getSettingsValue('plugin_id'),
                'endpoint' => $baseURL,
                'appKey' => '',
                'appSecret' => $token,
            ],
            'frontend' => [
                'design' => $this->settingsService->getSettingsValue('design'),
            ],
            'search' => [
                'design' => $this->settingsService->getSettingsValue('design'),
            ],
        ];

        $url = self::YOOCHOOSE_LICENSE_URL . $customerId . '/plugin/update?createIfNeeded=true&fallbackDesign=true';
        $response = $this->helper->getHttpPage($url, $body, $customerId, $licenseKey);
        $resault = json_decode($response);

        if ($resault['statusCode'] == 200) {
            return $this->response->json('User verification successful');
        } else {
            throw new \Exception('User is not verified!');
        }
    }

    /**
     * @return bool|mixed
     */
    public function loadSettings()
    {
        $data = array(
            'yc_test' => $this->settingsService->getSettingsValue('yc_test'),
            'customer_id' => $this->settingsService->getSettingsValue('customer_id'),
            'license_key' => $this->settingsService->getSettingsValue('license_key'),
            'plugin_id' => $this->settingsService->getSettingsValue('plugin_id'),
            'design' => $this->settingsService->getSettingsValue('design'),
            'item_type' => $this->settingsService->getSettingsValue('item_type'),
            'script_id' => $this->settingsService->getSettingsValue('script_id'),
            'search_enable' => $this->settingsService->getSettingsValue('search_enable'),
            'performance' => $this->settingsService->getSettingsValue('performance'),
            'log_severity' => $this->settingsService->getSettingsValue('log_severity'),
            'token' => $this->settingsService->getSettingsValue('token'),
        );
        return $this->response->json($data);
    }

}