<?php

namespace Yoochoose\Helpers;

use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Modules\Template\Design\Config\Contracts\DesignRepositoryContract;
use Plenty\Plugin\Application;
use Plenty\Plugin\Log\Loggable;

class Data
{
    use Loggable;

    /**
     * @var DesignRepositoryContract
     */
    private $designRepository;

    /**
     * @var WebstoreHelper
     */
    private $storeHelper;

    /**
     * @var Application
     */
    private $app;

    /**
     * Data constructor.
     * @param Application $app
     * @param WebstoreHelper $storeHelper
     * @param DesignRepositoryContract $designRepository
     */
    public function __construct(
        Application $app,
        WebstoreHelper $storeHelper,
        DesignRepositoryContract $designRepository
    ){
        $this->app = $app;
        $this->storeHelper = $storeHelper;
        $this->designRepository = $designRepository;
    }
    
    public function getStoreId()
    {
        return $this->app->getWebstoreId();
    }

    /**
     * @param $url
     * @param $body
     * @param $customerId
     * @param $licenceKey
     * @throws \Exception
     * @return mixed
     */
    public function getHttpPage($url, $body, $customerId, $licenceKey)
    {
        $bodyString = json_encode($body);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$customerId:$licenceKey");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
            ['Content-Type: application/json', 'Content-Length: ' . strlen($bodyString),]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodyString);

        $response = curl_exec($curl);
        $result = json_decode($response, true);

        $this->getLogger('Data_getHttpPage')->info('Yoochoose::log.configurationSaved', []);
        
        curl_close($curl);
        
        return $result;
    }


    /**
     * Returns base url of the store
     *
     * @return string
     */
    public function getWebsiteBaseUrl()
    {
        /** @var \Plenty\Modules\System\Models\WebstoreConfiguration $webStoreConfig */
        $webStoreConfig = $this->storeHelper->getCurrentWebstoreConfiguration();
        if (is_null($webStoreConfig)) {
            return '';
        }

        return $webStoreConfig->domain;
    }

    /**
     * Returns default design template
     *
     * @return string
     */
    public function getDefaultDesign()
    {
        $designArray = $this->designRepository->loadAll();

        return $designArray[0]['designName'] ?? '';
    }

}
