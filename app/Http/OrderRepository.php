<?php

namespace App\Http;

use App\Order;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Http\ProductRepository;

class OrderRepository
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $orders;

    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse
     */
    protected $response;

    public function __construct()
    {
        $path = storage_path();
        $this->file = $path.'/orders.json';

        $json = file_get_contents($this->file);
        if (!$json) {
            throw new Exception('');
        }

        $this->orders = [];
        $orders = json_decode($json);

        foreach ($orders as $order) {
            $data = [
                'id' => $order->id,
                'when' => $order->when,
                'product_id' => $order->product_id,
                'fulfilled' => isset($order->fulfilled) ? $order->fulfilled : false,
                'user_id' => $order->user_id,
            ];

            $this->orders[] = new Order($data, $order->product_id);
        }
        if (!empty($this->orders)) {
            usort($this->orders, array($this, "cmp"));
        }

        $this->response = new JsonResponse();
    }

    private function cmp($a, $b)
    {
        return strcmp($a->when, $b->when);
    }

    public function getOrders()
    {
        return $this->orders;
    }

    public function getNewId()
    {
        $last_id = 0;
        foreach ($this->orders as $order) {
            if ($order['id'] > $last_id) {
                $last_id = $order['id'];
            }
        }
        $last_id++;
        return $last_id;
    }

    public function addToOrders($order)
    {
        $this->orders[] = $order;
    }

    public function clearOrders()
    {
        $this->orders = [];
    }

    public function removeFromOrders($id)
    {
        foreach ($this->orders as $key => $order) {
            if ($order->id == $id) {
                unset($this->orders[$key]);
            }
        }
    }

    public function saveOrders($respond = true)
    {
        if (!file_put_contents($this->file, json_encode($this->orders))) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Could not save orders",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $product_repo = new ProductRepository();
        foreach ($this->orders as $order) {
            $order->product = $product_repo->findById($order->product_id);
        }
        if ($respond) {
            $this->response->setStatusCode(Response::HTTP_OK);
            $this->response->setData([
                'data' => $this->orders,
                'message' => "Saved!",
                'success' => true,
            ]);
            $this->response->send();
            return;
        }
        return;
    }

    public function fulfillOrder($id)
    {
        foreach ($this->orders as &$order) {
            if ($order->id == $id) {
                $order->fulfilled = 1;
                break;
            }
        }

        $this->saveOrders();
    }
}
