<?php

namespace App\Http\Controllers;

date_default_timezone_set('America/New_York');

use Illuminate\Routing\Controller as BaseController;
use App\Order;
use App\Http\ProductRepository;
use App\Http\OrderRepository;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends Controller
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
     * @api GET /api/orders
     */
    public function getOrders()
    {
        $repo = new OrderRepository();
        $product_repo = new ProductRepository();
        $orders = $repo->getOrders();
        foreach ($orders as $order) {
            $order->product = $product_repo->findById($order->product_id);
        }

        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setData([
            'data' => $orders,
            'message' => "Orders",
            'success' => true,
        ]);
        $this->response->send();
        return;
    }

    /**
     * @api POST /api/orders/submit
     */
    public function postSubmitOrder(Request $request)
    {
        $cart = $request->input('cart');
        $user_id = $request->input('user_id');

        $repo = new OrderRepository();
        $orders = $repo->getOrders();
        $next_id = $repo->getNewId();

        foreach ($cart as $item) {
            $data = [
                'id' => $next_id,
                'when' => date('Y-m-d H:i:s'),
                'product_id' => $item['id'],
                'fulfilled' => 0,
            ];
            if ($user_id) {
                $data['user_id'] = $user_id;
            } else {
                $data['user_id'] = null;
            }
            $order = new Order($data, $item['id']);
            $repo->addToOrders($order);
            $next_id++;
        }
        $repo->saveOrders();
        return;
    }

    /**
     * @api PATCH /orders/fulfill
     */
    public function patchFulfill(Request $request)
    {
        $id = $request->input('id');

        $repo = new OrderRepository();
        $repo->fulfillOrder($id);
        return;
    }

    /**
     * @api PATCH /orders/cancel
     */
    public function patchCancelOrder(Request $request)
    {
        $id = $request->input('id');

        $repo = new OrderRepository();
        $repo->removeFromOrders($id);
        $repo->saveOrders();
        return;
    }
}
