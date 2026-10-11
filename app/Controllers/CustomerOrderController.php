<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Flash;
use App\Helpers\Url;
use App\Services\OrderService;
use App\Services\ProductService;

/**
 * Class CustomerOrderController
 * 
 * Manages customer order creation and order detail view endpoints.
 * Enforces customer authentication, owner-scoped order viewing, and flash notification feedback.
 */
class CustomerOrderController extends Controller
{
    private OrderService $orderService;
    private ProductService $productService;

    /**
     * Constructor injecting services.
     */
    public function __construct(?OrderService $orderService = null, ?ProductService $productService = null)
    {
        $this->orderService = $orderService ?? new OrderService();
        $this->productService = $productService ?? new ProductService();
    }

    /**
     * Render the order placement form displaying active products and quantity inputs.
     *
     * GET /orders/create
     */
    public function create(Request $request): Response
    {
        $result = $this->productService->listProducts(true, null, 1, 100);

        return $this->render('orders/create', [
            'title' => 'Create New Order',
            'products' => $result['products'],
        ]);
    }

    /**
     * Process order submission POST request.
     * Delegates atomic transaction and inventory deduction to OrderService.
     *
     * POST /orders
     */
    public function store(Request $request): Response
    {
        $userId = Auth::id();
        $rawItems = $request->post('items', []);

        $result = $this->orderService->createOrder($userId, $rawItems);

        if (!$result['success']) {
            Flash::error($result['message'] ?? 'Failed to place order.');
            Url::redirect('/orders/create');
            exit;
        }

        Flash::success("Order #{$result['order_number']} placed successfully!");
        Url::redirect('/orders/' . $result['order_id']);
        exit;
    }

    /**
     * Display order details view.
     * Restricts viewing to the authenticated order owner or an authorized admin.
     *
     * GET /orders/{id}
     */
    public function show(Request $request, string $id): Response
    {
        $orderId = (int)$id;
        // Admins can view any order; customers can only view their own order
        $userId = Auth::isAdmin() ? null : Auth::id();

        $details = $this->orderService->getOrderDetails($orderId, $userId);

        return $this->render('orders/show', [
            'title' => 'Order Details #' . $details['order']['order_number'],
            'order' => $details['order'],
            'items' => $details['items'],
        ]);
    }

    /**
     * Display customer's own order history.
     * Enforces customer ownership in SQL query and pagination.
     *
     * GET /my-orders
     */
    public function myOrders(Request $request): Response
    {
        $userId = Auth::id();
        $page = (int)$request->get('page', 1);

        $result = $this->orderService->listUserOrders($userId, $page, 10);

        return $this->render('orders/my_orders', array_merge($result, [
            'title' => 'My Order History',
        ]));
    }

    /**
     * Cancel an existing customer order.
     * Restores inventory and marks order status as cancelled.
     *
     * POST /orders/{id}/cancel
     */
    public function cancel(Request $request, string $id): Response
    {
        $orderId = (int)$id;
        $userId = Auth::isAdmin() ? null : Auth::id();

        $result = $this->orderService->cancelOrder($orderId, $userId);

        if (!$result['success']) {
            Flash::error($result['message'] ?? 'Failed to cancel order.');
        } else {
            Flash::success($result['message']);
        }

        Url::redirect('/orders/' . $orderId);
        exit;
    }
}

