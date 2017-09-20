<?php

namespace Yoochoose\Controllers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Helpers\Data;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;
use IO\Services\SessionStorageService;

class SettingsController extends Controller
{
    use Loggable;

    const YOOCHOOSE_LICENSE_URL = 'https://admin.yoochoose.net/api/v4/';
    const YOOCHOOSE_ADMIN_URL = '//admin.yoochoose.net/';

    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var SessionStorageService
     */
    private $sessionStorage;

    /**
     * SettingsController constructor.
     * @param SettingsService $settingsService
     * @param Data $helper
     * @param Response $response
     * @param SessionStorageService $sessionStorage
     */
    public function __construct
    (
        SettingsService $settingsService,
        Data $helper,
        Response $response,
        SessionStorageService $sessionStorage
    ) {
        $this->settingsService = $settingsService;
        $this->helper = $helper;
        $this->response = $response;
        $this->sessionStorage = $sessionStorage;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response;
     * @throws \Exception
     */
    public function saveSettings(Request $request)
    {
        $configFields = [];

        $configFields['customer_id'] = $request->get('customer_id');
        $configFields['license_key'] = $request->get('license_key');
        $configFields['plugin_id'] = $request->get('plugin_id');
        $configFields['item_type'] = $request->get('item_type', 1);
        $configFields['script_id'] = $request->get('script_id');
        $configFields['search_enable'] = $request->get('search_enable');
        $configFields['performance'] = $request->get('performance', 1);
        $configFields['log_severity'] = $request->get('log_severity', 2);
        $configFields['design'] = $this->helper->getDefaultDesign();

        $baseUrl = $this->helper->getWebsiteBaseUrl();
        $configFields['endpoint'] = $baseUrl;

        foreach ($configFields as $key => $value) {
                switch ($key) {
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
                    case 'endpoint':
                        $this->settingsService->setSettingsValue('endpoint', $value);
                        break;
                }
        }

        $customerId = $this->settingsService->getSettingsValue('customer_id');
        $licenseKey = $this->settingsService->getSettingsValue('license_key');

        $body = [
            'base' => [
                'type' => "PLENTY7",
                'pluginId' => $this->settingsService->getSettingsValue('plugin_id'),
                'endpoint' => $baseUrl,
                'appKey' => '',
                'appSecret' => md5($configFields['license_key']),
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

        if ($response['statusCode'] == 200 || $response['statusCode'] == 409) {
            $result = [
                'status' => true,
                'message' => 'Configuration successfully saved',
            ];
        } else {
            $result = [
                'status' => false,
                'message' =>  'Configuration saved but could not connect to Yoochoose. Error '
                    . $response['statusCode'] . ' ' . $response['faultMessage'],
            ];
        }

        $this->getLogger('SettingsController_saveSettings')->info('Yoochoose::log.configurationSaved', []);

        return $this->response->json($result);
    }

    /**
     * Load settings for configuration page
     *
     * @return bool|mixed
     */
    public function loadSettings()
    {
        $data = [
            'customer_id' => $this->settingsService->getSettingsValue('customer_id'),
            'license_key' => $this->settingsService->getSettingsValue('license_key'),
            'plugin_id' => $this->settingsService->getSettingsValue('plugin_id'),
            'design' => $this->helper->getDefaultDesign(),
            'item_type' => $this->settingsService->getSettingsValue('item_type', 1),
            'script_id' => $this->settingsService->getSettingsValue('script_id'),
            'search_enable' => $this->settingsService->getSettingsValue('search_enable', 2),
            'performance' => $this->settingsService->getSettingsValue('performance', 1),
            'log_severity' => $this->settingsService->getSettingsValue('log_severity'),
            'endpoint' => $this->helper->getWebsiteBaseUrl(),
            'register_url' => $this->getRegistrationLink(),
        ];

        return $this->response->json($data);
    }

    /**
     * Get registration URL
     *
     * @return string
     */
    private function getRegistrationLink()
    {
        return self::YOOCHOOSE_ADMIN_URL . 'login.html?product=plenty_Direct&lang=' . $this->sessionStorage->getLang();
    }

}
