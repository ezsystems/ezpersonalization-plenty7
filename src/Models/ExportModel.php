<?php

namespace Yoochoose\Models;

use Plenty\Plugin\Application;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Processor\DocumentProcessor;
use Plenty\Modules\Cloud\ElasticSearch\Lib\Search\Document\DocumentSearch;
use Plenty\Modules\Item\Search\Contracts\VariationElasticSearchSearchRepositoryContract;
use Plenty\Modules\Item\Search\Filter\ClientFilter;
use Plenty\Modules\Item\Search\Filter\VariationBaseFilter;
use Plenty\Modules\Category\Contracts\CategoryRepositoryContract;
use Plenty\Modules\Item\Manufacturer\Contracts\ManufacturerRepositoryContract;

use IO\Services\WebstoreConfigurationService;
use IO\Extensions\Filters\URLFilter;

class ExportModel
{
    /**
     * @var Application
     */
    private $app;

    /**
     * ExportModel constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Returns list of categories that are visible on frontend
     *
     * @param string $shopId
     * @param integer $offset
     * @param integer $limit
     * @param string $lang
     * @return array
     */
    public function getCategories($shopId, $offset, $limit, $lang)
    {

        $result = [];
        $page = $offset == 0 ? 1 : (int)$offset / (int)$limit + 1;

        /** @var WebstoreConfigurationService $webstoreConfig */
        $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
        $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();

        /** @var CategoryRepositoryContract $categoryRepository */
        $categoryRepository = pluginApp(CategoryRepositoryContract::class);
        $searchCategories = $categoryRepository->search(null, $page, $limit, ['type' => 'item'], ['type' => 'item']);
        $categories = $searchCategories->toArray();

        foreach ($categories['entries'] as $category) {
            $result[] = [
                'id' => $category['id'],
                'url' => $storeConf['domainSsl'] . '/' . $categoryRepository->getUrl((int)$category['id'], $lang),
                'name' => $category['details'][0]['name'],
                'level' => $category['level'],
                'parentId' => $category['parentCategoryId'],
                'path' => $categoryRepository->getUrl((int)$category['id'], $lang),
                'shopId' => $shopId,
            ];
        }

        return $result;
    }

    /**
     * Returns list of products that are visible on frontend
     *
     * @param int $shopId
     * @param int $offset
     * @param int $limit
     * @param string $lang
     * @return array
     */
    public function getProducts($shopId, $offset, $limit, $lang)
    {
        $result = [];
        $page = $offset == 0 ? 1 : (int)$offset / (int)$limit + 1;

        /** @var WebstoreConfigurationService $webstoreConfig */
        $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
        $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();

        /** @var URLFilter $urlFilter */
        $urlFilter = pluginApp(URLFilter::class);

        /** @var CategoryRepositoryContract $categoryRepository */
        $categoryRepository = pluginApp(CategoryRepositoryContract::class);

        /** @var DocumentProcessor $documentProcessor */
        $documentProcessor = pluginApp(DocumentProcessor::class);

        /** @var DocumentSearch $documentSearch */
        $documentSearch = pluginApp(DocumentSearch::class, [$documentProcessor]);

        /** @var VariationElasticSearchSearchRepositoryContract $elasticSearchRepo */
        $elasticSearchRepo = pluginApp(VariationElasticSearchSearchRepositoryContract::class);
        $elasticSearchRepo->addSearch($documentSearch);

        /** @var ClientFilter $clientFilter */
        $clientFilter = pluginApp(ClientFilter::class);
        $clientFilter->isVisibleForClient($this->app->getPlentyId());

        /** @var VariationBaseFilter $variationFilter */
        $variationFilter = pluginApp(VariationBaseFilter::class);
        $variationFilter->isActive();
        if (!empty($lang)) {
            $variationFilter->hasADescriptionInLanguage($lang);
        }

        $documentSearch
            ->addFilter($clientFilter)
            ->addFilter($variationFilter)
            ->setPage($page, $limit);

        $products = $elasticSearchRepo->execute();

        foreach ($products['documents'] as $product) {
            $temp = [
                'id' => $product['data']['variation']['itemId'],
                'name' => isset($product['data']['texts'][0]['name1']) ?
                    $product['data']['texts'][0]['name1'] : null,
                'description' => isset($product['data']['texts'][0]['description']) ?
                    $product['data']['texts'][0]['description'] : null,
                'price' => $this->getProductPrice($product['data']['salesPrices']),
                'url' => $storeConf['domainSsl'] .
                    $urlFilter->buildVariationURL((int)$product['data']['variation']['id'], true),
                'image' => isset($product['data']['images']['all'][0]['url']) ?
                    $product['data']['images']['all'][0]['url'] : null,
                'manufacturer' => isset($product['data']['item']['manufacturer']['name']) ?
                    $product['data']['item']['manufacturer']['name'] : null,
                'categories' => [],
                'tags' => [],
                'shopId' => $shopId,
            ];

            $imageInfo = getimagesize($temp['image']);
            if (is_array($imageInfo)) {
                $temp['image_size'] = $imageInfo[0] . 'x' . $imageInfo[1];
            }

            if (isset($product['data']['texts'][0]['keywords']) && !empty($product['data']['texts'][0]['keywords'])) {
                $temp['tags'] = explode(',', $product['data']['texts'][0]['keywords']);
            }

            $categoryIds = explode(',', $product['data']['categories']['paths'][0]);
            foreach ($categoryIds as $categoryId) {
                $temp['categories'][] = $categoryRepository->getUrl((int)$categoryId, $lang);
            }

            $result[] = $temp;
        }

        return $result;
    }

    /**
     * Returns list of manufacturers that are visible on frontend
     *
     * @param integer $offset
     * @param integer $limit
     * @return array
     */
    public function getVendors($offset, $limit)
    {
        $result = [];

        $page = $offset == 0 ? 1 : (int)$offset / (int)$limit + 1;

        /** @var ManufacturerRepositoryContract $manufacturerRepository */
        $manufacturerRepository = pluginApp(ManufacturerRepositoryContract::class);
        $vendors = $manufacturerRepository->all(['id', 'name'], $limit, $page)->toArray();

        foreach ($vendors['entries'] as $vendor) {
            $result[] = [
                'id' => $vendor['id'],
                'name' => $vendor['name'],
            ];
        }

        return $result;
    }

    /** 
     * Returns default product price
     * 
     * @param array $prices
     * @return null|string
     */
    private function getProductPrice($prices)
    {
        $price = null;

        foreach ($prices as $price) {
            if ($price['type'] === 'default') {
                $price = $price['price'];
                break;
            }
        }

        return $price;
    }
}