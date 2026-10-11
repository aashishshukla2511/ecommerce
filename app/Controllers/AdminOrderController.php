<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\OrderService;

/**
 * Class AdminOrderController
 * 
 * Handles Admin order management endpoints: order listing with dynamic multi-criteria
 * filtering (status, search query, date range), pagination, and order details inspection.
 */
class AdminOrderController extends Controller
{
    private OrderService $orderService;

    public function __construct(?OrderService $orderService = null)
    {
        $this->orderService = $orderService ?? new OrderService();
    }

    /**
     * Display paginated list of all customer orders for Admin with status, search, and date range filters.
     *
     * GET /admin/orders
     */
    public function index(Request $request): Response
    {
        $status = $request->get('status', 'all');
        $search = $request->get('q');
        $fromDate = $request->get('from');
        $toDate = $request->get('to');
        $page = (int) $request->get('page', 1);

        $result = $this->orderService->listAllOrders($status, $search, $fromDate, $toDate, $page, 10);

        return $this->render('admin/orders/index', array_merge($result, [
            'title' => 'Manage Orders',
        ]), 'layouts/admin');
    }

    /**
     * Display individual customer order details view for Admin.
     *
     * GET /admin/orders/{id}
     */
    public function show(Request $request, string $id): Response
    {
        $orderId = (int) $id;

        // Admin can view any order header and items without customer ownership filter
        $details = $this->orderService->getOrderDetails($orderId, null);

        return $this->render('admin/orders/show', [
            'title' => 'Admin Order View #' . $details['order']['order_number'],
            'order' => $details['order'],
            'items' => $details['items'],
        ], 'layouts/admin');
    }

    /**
     * Update order status by Admin (pending, confirmed, completed, cancelled).
     * Automatically handles inventory restoration if status is updated to 'cancelled'.
     *
     * POST /admin/orders/{id}/status
     */
    public function updateStatus(Request $request, string $id): Response
    {
        $orderId = (int) $id;
        $status = (string) $request->post('status', '');

        $result = $this->orderService->updateOrderStatusByAdmin($orderId, $status);

        if (!$result['success']) {
            \App\Helpers\Flash::error($result['message'] ?? 'Failed to update order status.');
        } else {
            \App\Helpers\Flash::success($result['message']);
        }

        \App\Helpers\Url::redirect('/admin/orders/' . $orderId);
        exit;
    }
}

