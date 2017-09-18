<?php

namespace Yoochoose\Helpers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Models\ExportModel;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;
use IO\Services\WebstoreConfigurationService;

class ExportHelper
{
    use Loggable;

    const YC_DIRECTORY_NAME = 'yoochoose';

    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * @var StorageRepositoryContract
     */
    private $storageRepo;

    /**
     * @var WebstoreConfigurationService
     */
    private $webstoreConfigurationService;

    /**
     * SettingsController constructor.
     * @param SettingsService $settingsService
     * @param StorageRepositoryContract $storageRepositoryContract
     * @param WebstoreConfigurationService $webstoreConfigurationService
     */
    public function __construct(
        SettingsService $settingsService,
        StorageRepositoryContract $storageRepositoryContract,
        WebstoreConfigurationService $webstoreConfigurationService)
    {
        $this->settingsService = $settingsService;
        $this->storageRepo = $storageRepositoryContract;
        $this->webstoreConfigurationService = $webstoreConfigurationService;
    }

    /**
     * export to files
     *
     * @param int $limit
     * @param string $lang
     * @param int $transaction
     * @param int $mandatorId
     * @return array postData
     */
    public function export($lang, $transaction, $limit, $mandatorId)
    {
        $this->getLogger('ExportHelper_export')->info('Yoochoose::log.exportStartedAllResources', []);
        $shopIds = [];
        $formatsMap = [
            'PLENTY7' => 'Products',
            'PLENTY7_CATEGORIES' => 'Categories',
            'PLENTY7_VENDORS' => 'Vendors',
        ];

        $postData = [
            'transaction' => $transaction,
            'events' => [],
        ];

        $languages = empty($lang) ? $this->webstoreConfigurationService->getActiveLanguageList() : [$lang];

        /** @var @Data $dataHelper */
        $dataHelper = pluginApp(Data::class);

        foreach ($formatsMap as $format => $method) {
            foreach ($languages as $language) {
                $postData['events'][] = [
                    'action' => 'FULL',
                    'format' => $format,
                    'contentTypeId' => $this->settingsService->getSettingsValue('item_type'),
                    'shopViewId' => $dataHelper->getStoreId(),
                    'lang' => $language,
                    'credentials' => [
                        'login' => null,
                        'password' => null,
                    ],
                    'uri' => [],
                ];
                $shopIds[$method][] = $dataHelper->getStoreId();
            }
        }

        $i = 0;

        foreach ($postData['events'] as $event) {
            $method = $formatsMap[$event['format']] ? $formatsMap[$event['format']] : null;
            if ($method) {
                $postData = self::exportData($method, $postData, $limit, $i, $event['shopViewId'],
                    $mandatorId, $event['lang']);
            }
            $i++;
        }
        $this->getLogger('ExportHelper_export')->info('Yoochoose::log.exportFinishedAllResources', []);
        return $postData;
    }

    /**
     * Generates random string with $length characters
     *
     * @param int $length
     * @return string
     */
    public function generateRandomString($length = 20)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Exports data to file and returns $postData parameter
     *   with URLs to files
     *
     * @param string $method
     * @param array $postData
     * @param int $limit
     * @param int $exportIndex
     * @param integer $shopId
     * @param string $mandatorId
     * @param string $lang
     * @return array $postData
     */
    private function exportData($method, $postData, $limit, $exportIndex, $shopId, $mandatorId, $lang)
    {
        $this->getLogger('ExportHelper_exportData')->info(
            'Yoochoose::log.exportStartedForResource' . $method,
            ['shopId' => $shopId]
        );

        /** @var ExportModel $model */
        $model = pluginApp(ExportModel::class);

        $offset = 0;

        do {
            $this->getLogger('ExportHelper_exportData')->info(
                'Yoochoose::log.exportBulkStarted' . $method,
                ['shopId' => $shopId, 'limit' => $limit, 'offset' => $offset]
            );
            switch ($method) {
                case 'Products':
                    $results = $model->getProducts($shopId, $offset, $limit, $lang);
                    break;
                case 'Categories':
                    $results = $model->getCategories($shopId, $offset, $limit, $lang);
                    break;
                case 'Vendors':
                    $results = $model->getVendors($offset, $limit);
                    break;
            }

            if (!empty($results)) {
                $filename = $this->generateRandomString() . '.json';
                $file = self::YC_DIRECTORY_NAME . '/' . $filename;
                $this->storageRepo->uploadObject('Yoochoose', $file, json_encode($results), true);
                $signedUrl = $this->storageRepo->getObjectUrl(
                    'Yoochoose',
                    $file,
                    true,
                    15
                );
                //remove query parameters since signed URL doesn't load
                $postData['events'][$exportIndex]['uri'][] = preg_replace('/\\?.*/', '', $signedUrl);
                $this->getLogger('ExportHelper_exportData')->info(
                    'Yoochoose::log.exportBulkFinished' . $method,
                    ['shopId' => $shopId, 'limit' => $limit, 'offset' => $offset, 'file' => $file]
                );
                $offset = $offset + $limit;
            }
        } while (!empty($results));

        $this->getLogger('ExportHelper_exportData')->info(
            'Yoochoose::log.exportFinishedForResource' . $method,
            ['shopId' => $shopId]
        );

        return $postData;
    }
}