<?php
namespace Yoochoose\Controllers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Helpers\ExportHelper;
use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Modules\Item\DataLayer\Contracts\ItemDataLayerRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use IO\Services\ItemService;
use IO\Extensions\Filters\URLFilter;
use IO\Services\WebstoreConfigurationService;
use IO\Services\SessionStorageService;

class ExportController extends Controller
{
    use Loggable;

    /**
     * @var string
     */
    protected $language;
    /**
     * @var int
     */
    protected $limit;
    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $shopId;

    /**
     * @var string
     */
    protected $mandator;

    /**
     * @var string
     */
    protected $webHook;

    /**
     * @var string
     */
    protected $transaction;

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
     * @var ItemService
     */
    private $itemService;

    /**
     * @var ItemDataLayerRepositoryContract
     */
    private $itemRepository;
    
    /**
     * SessionStorageService
     */
    private $sessionStorage;

    /**
     * @var URLFilter
     */
    private $urlFilter;

    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var ExportHelper
     */
    private $exportHelper;

    public function __construct(
        Application $app,
        Response $response,
        Request $request,
        ItemService $service,
        ItemDataLayerRepositoryContract $itemRepository,
        SessionStorageService $sessionStorage,
        URLFilter $urlFilter,
        SettingsService $settingsService,
        ExportHelper $exportHelper)
    {
        $this->app = $app;
        $this->response = $response;
        $this->request = $request;
        $this->itemService = $service;
        $this->itemRepository = $itemRepository;
        $this->sessionStorage = $sessionStorage;
        $this->urlFilter = $urlFilter;
        $this->settingsService = $settingsService;
        $this->exportHelper = $exportHelper;
    }

    public function init()
    {
        try {
            $licenceKey = null;
            $mandator = $this->request->get('mandator');
            $limit = $this->request->get('limit');
            $webHook = $this->request->get('webHook');
            if (!empty($mandator) && !empty($limit) && !empty($webHook)) {
                $licenceKey = $this->settingsService->getSettingsValue('license_key');
            } else {
                return $this->response->json(["Limit, mandator and webHook parameters must be set."], 400);
            }

            $appSecret = [];
            $appSecret[] = str_replace('Bearer ', '', $this->request->header('Authorization', ''));
            $appSecret[] = str_replace('Bearer ', '', $this->request->header('YCAuth', ''));
            $appSecret[] = urldecode($this->request->get('ycauth', ''));

            if (in_array(md5($licenceKey), $appSecret, true)) {
                $this->limit = $this->request->get('limit');
                $this->offset = $this->request->get('offset');
                $this->language = $this->request->get('lang');
                $this->shopId = $this->request->get('shop');
                $this->mandator = $this->request->get('mandator');
                $this->webHook = $this->request->get('webHook');
                $this->transaction = $this->request->get('transaction');
            } else {
                return $this->response->json(['Authentication failed'], 401);
            }
            $response = $this->startExport();
            return $this->response->json(['success' => $response]);
        } catch (\Exception $exc) {
            return $this->response->json($exc->getMessage(), 400);
        }
    }

    /**
     * Returning item details
     *
     * @throws \Exception
     */
    private function startExport()
    {
        $post = [];

        if (!empty($this->request->get('forceStart'))) {
            $this->settingsService->setSettingsValue('enable_flag', 0);
        }

        $flag = $this->settingsService->getSettingsValue('enable_flag');

        if ($flag != 1) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $queryString = substr($requestUri, strpos($requestUri, '?') + 1);
            $this->getLogger('ExportController_start')->info('YoochoosePersonalizationEngine::log.exportStarted' . $queryString, []);

            $post['mandator'] = $this->mandator;
            $post['limit'] = $this->limit;
            $post['webHook'] = $this->webHook;
            $post['password'] = $this->exportHelper->generateRandomString();
            $post['transaction'] = $this->transaction;
            $post['lang'] = $this->language;

            $this->settingsService->setSettingsValue('yc_password', $post['password']);

            /** @var WebstoreConfigurationService $webstoreConfig */
            $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
            $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();
            $baseUrl = $storeConf['domainSsl'] . '/';

            $this->triggerExport($baseUrl . 'yoochoose/trigger?' . http_build_query($post));
            $response = ['status' => true];
        } else {
            $response = ['message' => 'Job not sent.'];
        }

        return $response;
    }
    
    /**
     * triggerExport
     *
     * @param string @url
     * @return string execute
     */
    private function triggerExport($url)
    {
        $cURL = curl_init();
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($cURL, CURLOPT_HEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($cURL, CURLOPT_NOBODY, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($cURL, CURLOPT_TIMEOUT, 1);

        $test = curl_exec($cURL);

        return $test;
    }
}
