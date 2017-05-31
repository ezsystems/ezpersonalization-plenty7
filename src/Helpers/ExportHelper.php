<?php

namespace Yoochoose\Helpers;

use Yoochoose\Services\SettingsService;
use Yoochoose\Models\ExportModel;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;


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
     * SettingsController constructor.
     * @param SettingsService $settingsService
     * @param StorageRepositoryContract $storageRepositoryContract
     */
    public function __construct(SettingsService $settingsService, StorageRepositoryContract $storageRepositoryContract)
    {
        $this->settingsService = $settingsService;
        $this->storageRepo = $storageRepositoryContract;
    }

    /**
     * export to files
     *
     * @param int $limit
     * @param string $lang
     * @param int $transaction
     * @return array postData
     */
    public function export($lang, $transaction, $limit)
    {
        $shopIds = [];
        $formatsMap = [
            'PLENTY' => 'Products',
            'PLENTY_CATEGORIES' => 'Categories',
            'PLENTY_VENDORS' => 'Vendors',
        ];

        $postData = [
            'transaction' => $transaction,
            'events' => [],
        ];

        /** @var Data $dataHelper */
        $dataHelper = pluginApp(Data::class);

        foreach ($formatsMap as $format => $method) {
            $postData['events'][] = [
                'action' => 'FULL',
                'format' => $format,
                'contentTypeId' => $this->settingsService->getSettingsValue('item_type'),
                'shopViewId' => $dataHelper->getStoreId(),
                'lang' => $lang,
                'credentials' => [
                    'login' => null,
                    'password' => null,
                ],
                'uri' => [],
            ];
            $shopIds[$method][] = $dataHelper->getStoreId();
        }

        $i = 0;

        foreach ($postData['events'] as $event) {
            $method = $formatsMap[$event['format']] ? $formatsMap[$event['format']] : null;
            if ($method) {
                $postData = self::exportData($method, $postData, $limit, $i, $event['shopViewId'], $event['lang']);
            }

            $i++;
        }

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
     * @param string $lang
     * @return array $postData
     */
    private function exportData($method, $postData, $limit, $exportIndex, $shopId, $lang)
    {
        /** @var ExportModel $model */
        $model = pluginApp(ExportModel::class);

        $offset = 0;
        $logNames = '';

        do {
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
                $offset = $offset + $limit;
                $logNames .= $filename . ', ';
            }
        } while (!empty($results));

        $logNames = $logNames ?: 'there are no files';
        $this->getLogger('SettingsController_saveSettings')->info('Export has finished for ' . $method
            . ' with file names : ' . $logNames, []);

        return $postData;
    }
}