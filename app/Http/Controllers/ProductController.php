<?php

namespace App\Http\Controllers;

date_default_timezone_set('America/New_York');

use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\Product;
use App\Http\ProductRepository;
use App\Http\OrderRepository;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse
     */
    protected $response;

    public function __construct()
    {
        $this->response = new JsonResponse();
    }

    /**
     * @api GET /api/products
     */
    public function getProducts()
    {
        $repo = new ProductRepository();

        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setData([
            'data' => $repo->getProducts(),
            'message' => "Products",
            'success' => true,
        ]);
        $this->response->send();
        return;
    }

    private function validateProduct($repo, $item)
    {
        if (!array_key_exists('id', $item)) {
            $item['id'] = -1;
        }

        if (!array_key_exists('name', $item) || !$item['name'] || $item['name'] === '') {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Missing product name",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        if (!$repo->validateUniqueName($item['id'], $item['name'])) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Product name is not unique",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        if (!array_key_exists('price', $item) || !$item['price'] || $item['price'] === '') {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Missing product price",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        if (!is_int($item['price']) || $item['price'] < 1) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Invalid price",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }
    }

    /**
     * @api POST /api/products
     */
    public function postAddProduct(Request $request)
    {
        $item = $request->input('item');

        $repo = new ProductRepository();

        $this->validateProduct($repo, $item);

        $products = $repo->getProducts();

        $last_id = 0;
        foreach ($products as $product) {
            if ($product->id > $last_id) {
                $last_id = $product->id;
            }
        }
        $last_id++;
        $item['id'] = $last_id;
        $products[] = new Product($item);

        $repo->saveProducts($products);
    }

    /**
     * @api PATCH /api/products
     */
    public function patchEditProduct(Request $request)
    {
        $item = $request->input('item');

        $repo = new ProductRepository();

        $this->validateProduct($repo, $item);

        $products = $repo->getProducts();

        foreach ($products as $product) {
            if ($product->id == $item['id']) {
                $product->name = $item['name'];
                $product->price = $item['price'];
                break;
            }
        }
        $repo->saveProducts($products);
    }

    /**
     * @api DELETE /api/products
     */
    public function deleteProduct(Request $request)
    {
        $item = $request->input('item');

        $repo = new ProductRepository();

        $products = $repo->getProducts();

        $index = false;
        foreach ($products as $key => $product) {
            if ($product->id == $item['id']) {
                $index = $key;
                break;
            }
        }

        if (!$index) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Could not remove products",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        unset($products[$index]);

        $order_repo = new OrderRepository();
        $orders = $order_repo->getOrders();
        $order_repo->clearOrders();
        foreach ($orders as $order) {
            if ($order->product_id === $item['id']) {
                $order->product_id = -1;
            }
            $order_repo->addToOrders($order);
        }
        $order_repo->saveOrders(false);

        $repo->saveProducts($products);
    }
}
