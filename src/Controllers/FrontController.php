<?php

namespace Yoochoose\Controllers;

use IO\Builder\Item\ItemColumnBuilder;
use IO\Builder\Item\ItemFilterBuilder;
use IO\Builder\Item\ItemParamsBuilder;
use IO\Builder\Item\Params\ItemColumnsParams;
use IO\Extensions\Filters\URLFilter;
use IO\Services\ItemService;
use IO\Services\SessionStorageService;
use IO\Services\WebstoreConfigurationService;
use Plenty\Modules\Item\DataLayer\Contracts\ItemDataLayerRepositoryContract;
use Plenty\Modules\Item\DataLayer\Models\Record;
use Plenty\Modules\Item\ItemImage\Contracts\ItemImageRepositoryContract;
use Plenty\Modules\Item\ItemImage\Models\ItemImage;
use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

class FrontController extends Controller
{
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
     * @var ItemImageRepositoryContract
     */
    private $imageRepository;

    public function __construct(
        Application $app,
        Response $response,
        Request $request,
        ItemService $service,
        ItemDataLayerRepositoryContract $itemRepository,
        SessionStorageService $sessionStorage,
        URLFilter $urlFilter,
        ItemImageRepositoryContract $imageRepository
    ) {
        $this->app = $app;
        $this->response = $response;
        $this->request = $request;
        $this->itemService = $service;
        $this->itemRepository = $itemRepository;
        $this->sessionStorage = $sessionStorage;
        $this->urlFilter = $urlFilter;
        $this->imageRepository = $imageRepository;
    }

    /**
     * Returning item details
     */
    public function export()
    {
        $products = [];
        $productIds = $this->request->get('productIds');
        $productIds = isset($productIds) ? explode(',', $productIds) : null;

        /** @var WebstoreConfigurationService $webstoreConfig */
        $webstoreConfig = pluginApp(WebstoreConfigurationService::class);
        $storeConf = $webstoreConfig->getWebstoreConfig()->toArray();

        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                try {
                    /** @var Record $product */
                    $product = $this->getItem([$productId])->current();
                } catch (\Exception $e) {
                    // SQL error is thrown if there's not product with ID
                    $product = false;
                }

                if ($product) {
                    $variationUrl = $this->urlFilter->buildVariationURL((int)$product->variationBase->id);
                    /** @var ItemImage $image */
                    $image = $this->imageRepository->findByItemId($productId);

                    $products[] = [
                        'id' => $productId,
                        'link' => $storeConf['domainSsl'] . '/' . $product->itemDescription->urlContent . '_' .
                            ltrim($variationUrl, '/'),
                        'newPrice' => isset($product->variationRetailPrice->price) ?
                            $product->variationRetailPrice->price : null,
                        'oldPrice' => isset($product->variationRecommendedRetailPrice->price) ?
                            $product->variationRecommendedRetailPrice->price : null,
                        'image' => $image->url,
                        'title' => $product->itemDescription->name1,
                        'debug' => [
                            'item' => $product->toArray(),
                            'class' => get_class($product),
                            'image_item' => $image->toArray(),
                            'image_class' => get_class($image),
                        ],
                    ];
                }
            }
        }

        return $this->response->json($products);
    }

    /**
     * @param $itemIds
     *
     * @return \Plenty\Modules\Item\DataLayer\Models\RecordList
     */
    private function getItem($itemIds)
    {
        /** @var ItemColumnBuilder $columnBuilder */
        $columnBuilder = pluginApp(ItemColumnBuilder::class);
        $columns = $columnBuilder
            ->defaults()
            ->build();

        // Filter the current item by item ID
        /** @var ItemFilterBuilder $filterBuilder */
        $filterBuilder = pluginApp(ItemFilterBuilder::class);
        $filter = $filterBuilder
            ->hasId($itemIds)
            ->variationIsActive()
            ->build();

        // Set the parameters
        /** @var ItemParamsBuilder $paramsBuilder */
        $paramsBuilder = pluginApp(ItemParamsBuilder::class);
        $params = $paramsBuilder
            ->withParam(ItemColumnsParams::LANGUAGE, $this->sessionStorage->getLang())
            ->withParam(ItemColumnsParams::PLENTY_ID, $this->app->getPlentyId())
            ->build();

        return $this->itemRepository->search(
            $columns,
            $filter,
            $params
        );
    }
}
