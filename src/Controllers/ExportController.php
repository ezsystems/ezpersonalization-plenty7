<?php
namespace Yoochoose\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Frontend\Services;
use Plenty\Modules\Helper\Services\WebstoreHelper;

use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use IO\Services\ItemService;

/**
 * Class ContentController
 * @package Yoochoose\Controllers
 */
class ExportController extends Controller
{

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
     * @var WebstoreHelper
     */
    private $storeHelper;


    /**
     * ExportController constructor.
     * @param Response $response
     * @param Request $request
     * @param ItemService $service
     * @param WebstoreHelper $webstoreHelper
     */
    public function __construct(
        Response $response,
        Request $request,
        ItemService $service,
        WebstoreHelper $webstoreHelper
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->itemService = $service;
        $this->storeHelper = $webstoreHelper;
    }

    /**
     * Returning item details
     *
     */
    public function export()
    {

        $productIds = $this->request->get('productIds');
        $productIds = isset($productIds) ? explode(',', $productIds) : null;
        $webstoreConfig = $this->storeHelper->getCurrentWebstoreConfiguration();

        foreach ($productIds as $productId) {
            $product = $this->itemService->getItem($productId);
            $products[] = [
                'id' => $product->itemBase->id,
                'link' => $this->itemService->getItemURL($product->itemBase->id),
                'price' => $product->variationRetailPrice->price,
                'image' => $this->itemService->getItemImage($product->itemBase->id),
                'title' => $product->itemDescription->name1,
            ];
        }

        return $this->response->json($products);
    }
}
