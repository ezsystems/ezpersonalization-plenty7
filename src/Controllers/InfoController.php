<?php

namespace Yoochoose\Controllers;

use Yoochoose\Services\SettingsService;
use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Log\Loggable;
use IO\Services\WebstoreConfigurationService;

class InfoController extends Controller
{
    use Loggable;

    const YOOCHOOSE_CDN_SCRIPT = '//event.test.yoochoose.net/cdn';
    const AMAZON_CDN_SCRIPT = '//cdn.yoochoose.net';
    const SHOP_VERSION = '7.0.0';
    const PLUGIN_VERSION = '1.0.0';

    /**
     * @var Application
     */
    private $app;

    /**
     * @var null|Response
     */
    private $response;

    /**
     * @var Response
     */
    private $request;

    /**
     * @var SettingsService
     */
    private $settingsService;

    public function __construct(
        Application $app,
        Response $response,
        Request $request,
        SettingsService $settingsService)
    {
        $this->app = $app;
        $this->response = $response;
        $this->request = $request;
        $this->settingsService = $settingsService;
    }

    public function init()
    {
        try {
            $licenceKey = $this->settingsService->getSettingsValue('license_key');
            $header = $this->request->header('Authorization');
            $appSecret = str_replace('Bearer ', '', $header);

            if (md5($licenceKey) == $appSecret) {
                $mandator = $this->settingsService->getSettingsValue('customer_id');
                $pluginId = $this->settingsService->getSettingsValue('plugin_id');
                $plugin = $pluginId ? '/' . $pluginId : '';
                $ycOverwriteEndpoint = $this->settingsService->getSettingsValue('script_id');

                if ($ycOverwriteEndpoint) {
                    $scriptOverwrite = (!preg_match('/^(http|\/\/)/', $ycOverwriteEndpoint) ? '//' : '') .
                        $ycOverwriteEndpoint;
                    $scriptUrl = preg_replace('(^https?:)', '', $scriptOverwrite);
                } else {
                    $scriptUrl = $this->settingsService->getSettingsValue('performance') == 1 ?
                        self::AMAZON_CDN_SCRIPT : self::YOOCHOOSE_CDN_SCRIPT;
                }

                $scriptUrl = rtrim($scriptUrl, '/') . '/';
                $scriptUrl = $scriptUrl . "v1/{$mandator}{$plugin}/tracking.";

                /** @var WebstoreConfigurationService $webstoreConfig */
                $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
                $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();

                $response = [
                    'shop' => $storeConf['name'],
                    'shop_version' => self::SHOP_VERSION,
                    'plugin_version' => self::PLUGIN_VERSION,
                    'mandator' => $mandator,
                    'license_key' => $licenceKey,
                    'plugin_id' => $pluginId,
                    'endpoint' => $this->settingsService->getSettingsValue('endpoint'),
                    'design' => $this->settingsService->getSettingsValue('design'),
                    'itemtype' => $this->settingsService->getSettingsValue('item_type'),
                    'script_uris' => [
                        $scriptUrl . 'js',
                        $scriptUrl . 'css'
                    ],
                    'overwrite_endpoint' => $ycOverwriteEndpoint,
                    'search_enabled' => $this->settingsService->getSettingsValue('search_enable'),
                    'php_version' => PHP_VERSION,
                    'os' => PHP_OS
                ];

                return $this->response->json([$response]);

            } else {
                return $this->response->json(['Authentication failed'], 401);
            }

        } catch (\Exception $exc) {
            return $this->response->json($exc->getMessage(), 400);
        }
    }

}
