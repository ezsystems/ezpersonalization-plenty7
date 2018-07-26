<?php
namespace Yoochoose\Controllers;

use Yoochoose\Helpers\ExportHelper;
use Yoochoose\Services\SettingsService;
use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;
use Plenty\Modules\Item\DataLayer\Contracts\ItemDataLayerRepositoryContract;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Log\Loggable;
use IO\Extensions\Filters\URLFilter;

class TriggerController extends Controller
{
    use Loggable;

    /**
     * @var SettingsService
     */
    private $settingsService;
    
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
     * @var ItemDataLayerRepositoryContract
     */
    private $itemRepository;

    /**
     * @var URLFilter
     */
    private $urlFilter;

    /**
     * @var ExportHelper
     */
    private $helper;

    public function __construct(
        Application $app,
        Response $response,
        Request $request,
        ItemDataLayerRepositoryContract $itemRepository,
        URLFilter $urlFilter,
        ExportHelper $helper,
        SettingsService $settingsService)
    {
        $this->app = $app;
        $this->response = $response;
        $this->request = $request;
        $this->itemRepository = $itemRepository;
        $this->urlFilter = $urlFilter;
        $this->helper = $helper;
        $this->settingsService = $settingsService;
    }

    /**
     * Returning item details
     *
     */
    public function export()
    {
        $this->getLogger('TriggerController_export')->info('YoochoosePersonalizationEngine::log.triggerExportStarted', []);
        $limit = $this->request->get('limit');
        $callbackUrl = $this->request->get('webHook');
        $postPassword = $this->request->get('password');
        $transaction = $this->request->get('transaction');
        $lang = $this->request->get('lang');
        $customerId = $this->request->get('mandator');
        $password = $this->settingsService->getSettingsValue('yc_password');
        $licenceKey = $this->settingsService->getSettingsValue('license_key');

        if ($password == $postPassword) {
            $this->settingsService->setSettingsValue('enable_flag', 1);
            try {
                $postData = $this->helper->export($lang, $transaction, $limit, $customerId);
                $this->setCallback($callbackUrl, $postData, $customerId, $licenceKey);
                $response['success'] = true;
            } catch (\Throwable $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $this->getLogger('TriggerController_export')->error(
                    'YoochoosePersonalizationEngine::log.triggerExportFailed', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                ]);
            } finally {
                $this->settingsService->setSettingsValue('enable_flag', 0);
            }
        } else {
            $response['message'] = 'Passwords do not match!';
        }

        return $this->response->json($response);
    }

    /**
     * Creates request and returns response
     *
     * @param string $url
     * @param array $post
     * @param string $customerId
     * @param string $licenceKey
     * @return \Symfony\Component\HttpFoundation\Response
     * @internal param mixed $params
     */
    private function setCallback($url, $post, $customerId, $licenceKey)
    {
        $postString = json_encode($post);

        $cURL = curl_init();
        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($cURL, CURLOPT_USERPWD, "$customerId:$licenceKey");
        curl_setopt($cURL, CURLOPT_HTTPHEADER, ['Content-Type: application/json',]);
        curl_setopt($cURL, CURLOPT_POST, 1);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cURL, CURLOPT_HEADER, true);

        $response = curl_exec($cURL);

        $this->getLogger('TriggerController_setCallback')->info(
            'YoochoosePersonalizationEngine::log.callbackSent' . $url,
            ['post' => $postString]
        );

        curl_close($cURL);

        return $this->response->json(['result' => json_encode($response)]);
    }
}
