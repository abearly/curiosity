<?php

namespace App\Http;

use App\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductRepository
{
    /**
     * @var array
     */
    protected $available_products;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse
     */
    protected $response;

    public function __construct()
    {
        $path = storage_path();
        $this->file = $path.'/products.json';
        $json = file_get_contents($this->file);
        if (!$json) {
            throw new Exception();
        }
        $products = json_decode($json);

        foreach ($products as $product) {
            $data = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
            ];
            $this->available_products[] = new Product($data);
        }

        $this->response = new JsonResponse();
    }

    public function getProducts()
    {
        return $this->available_products;
    }

    public function findById($id)
    {
        $products = $this->getProducts();
        foreach ($products as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }
        return false;
    }


    public function addProduct(Request $request)
    {
        $name = $request->input('name');
        $price = $request->input('price');

        $this->available_products[] = new Product(['name' => $name, 'price' => $price]);
    }

    public function saveProducts($products)
    {
        $this->available_products = $products;
        if (!file_put_contents($this->file, json_encode($products))) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Could not save products",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }
        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setData([
            'data' => $products,
            'message' => "Saved!",
            'success' => true,
        ]);
        $this->response->send();
        return;
    }

    public function validateUniqueName($id, $name) {
        foreach ($this->available_products as $product) {
            if ($product['name'] === $name && $product['id'] !== $id) {
                return false;
            }
        }
        return true;
    }
}
