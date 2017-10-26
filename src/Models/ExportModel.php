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
     * @var array
     */
    private $loadedCategories = [];
    /**
     * @var CategoryRepositoryContract
     */
    private $categoryRepository;

    /**
     * ExportModel constructor.
     * @param Application $app
     * @param CategoryRepositoryContract $categoryRepository
     */
    public function __construct(Application $app, CategoryRepositoryContract $categoryRepository)
    {
        $this->app = $app;
        $this->categoryRepository = $categoryRepository;
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
            $name = $this->getCategoryNameFromDetails($category['details'], $lang);
            $result[] = [
                'id' => $category['id'],
                'url' => $storeConf['domainSsl'] . '/' . $categoryRepository->getUrl((int)$category['id'], $lang),
                'name' => $name,
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

            $texts = $this->getProductTexts($product['data']['texts'], $lang);

            $temp = [
                'id' => $product['data']['variation']['itemId'],
                'name' => isset($texts['name1']) ? $texts['name1'] : null,
                'description' => isset($texts['description']) ? $texts['description'] : null,
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

            if (isset($product['data']['texts'][0]['keywords']) && !empty($product['data']['texts'][0]['keywords'])) {
                $temp['tags'] = explode(',', $product['data']['texts'][0]['keywords']);
            }

            $categoryIds = array_column($product['data']['defaultCategories'] ?? [], 'id');
            $temp['categories'] = $this->extractCategoriesPaths($categoryIds, $lang);

            $result[] = $temp;
        }

        return $result;
    }

    /**
     * Returns names of categories separated by slash
     *
     * @param array $categoryIds
     * @param $lang
     * @return array
     */
    private function extractCategoriesPaths($categoryIds, $lang)
    {
        $categories = [];
        foreach ($categoryIds as $catId) {
            if (!array_key_exists($catId, $this->loadedCategories)) {
                $this->buildCategoryPath($catId, $lang);
            }

            $categories[] = $this->loadedCategories[$catId];
        }

        return $categories;
    }

    /**
     * @param $categoryId
     * @param $lang
     * @return string
     */
    private function buildCategoryPath($categoryId, $lang)
    {
        if (array_key_exists($categoryId, $this->loadedCategories)) {
            return $this->loadedCategories[$categoryId];
        }

        $category = $this->categoryRepository->get($categoryId, $lang)->toArray();
        $categoryPath = $category['details'][0]['name'];
        $parentId = $category['parentCategoryId'];
        if ($parentId) {
            $categoryPath = $this->buildCategoryPath($parentId, $lang) . '/' . $categoryPath;
        }

        $this->loadedCategories[$categoryId] = $categoryPath;

        return $categoryPath;
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

    /**
     * Extracting category name from details array for given language slag
     *
     * @param array $details
     * @param string $lang
     * @return string
     */
    private function getCategoryNameFromDetails($details, $lang) {
        foreach ($details as $detail) {
            if ($detail['lang'] === $lang) {
                return $detail['name'];
            }
        }
        return '';
    }

    /**
     * @param array $texts
     * @param string $lang
     * @return array
     */
    private function getProductTexts($texts, $lang) {
        foreach ($texts as $text) {
            if ($text['lang'] === $lang) {
                return $text;
            }
        }
        return array();
    }
}
