<?php

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Validator;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use PDO;
use Throwable;

/**
 * Class OrderService
 * 
 * Implements core transactional business logic for order creation and order detail retrieval.
 * Features atomic database transactions, deterministic row locking (FOR UPDATE),
 * server-side price/total calculations, and guarded inventory deduction.
 */
class OrderService
{
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private PDO $db;

    /**
     * Constructor initializing repositories and PDO connection.
     */
    public function __construct(?OrderRepository $orderRepository = null, ?ProductRepository $productRepository = null, ?PDO $db = null)
    {
        $this->orderRepository = $orderRepository ?? new OrderRepository();
        $this->productRepository = $productRepository ?? new ProductRepository();
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Atomically create a customer order with inventory deduction inside a MySQL transaction.
     *
     * Transactional Workflow:
     * 1. Validate items input & combine duplicate product quantities.
     * 2. Begin PDO transaction ($db->beginTransaction()).
     * 3. Sort product IDs ascending to prevent deadlocks across concurrent requests.
     * 4. Acquire exclusive row locks using SELECT ... FOR UPDATE.
     * 5. Verify product active status and stock availability against database state.
     * 6. Calculate unit prices, line totals, and order total server-side.
     * 7. Insert orders header row and order_items snapshot rows.
     * 8. Atomically deduct inventory for each product using guarded UPDATE statements.
     * 9. Commit transaction on success, or execute rollBack() if any failure occurs.
     *
     * @param int $userId Authenticated customer user ID.
     * @param array $rawItems Raw array of items e.g., [['product_id' => 1, 'quantity' => 2]]
     * @return array Result array containing success flag, order ID, or errors.
     * @throws Exception
     */
    public function createOrder(int $userId, array $rawItems): array
    {
        // Step 1: Input Array Validation
        if (empty($rawItems) || !is_array($rawItems)) {
            return [
                'success' => false,
                'message' => 'Your order must contain at least one product line.',
            ];
        }

        // Normalize & Combine Duplicate Product Quantities for Selected Items Only
        $itemMap = [];
        foreach ($rawItems as $index => $item) {
            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            $hasSelectedKey = isset($item['selected']);
            $isSelected = !empty($item['selected']);

            // If item has a selection checkbox and is not selected, or quantity is <= 0, skip it
            if ($quantity === false || $quantity <= 0 || ($hasSelectedKey && !$isSelected)) {
                continue;
            }

            if ($productId === false || $productId <= 0) {
                continue;
            }

            if (isset($itemMap[$productId])) {
                $itemMap[$productId] += $quantity;
            } else {
                $itemMap[$productId] = $quantity;
            }
        }

        if (empty($itemMap)) {
            return [
                'success' => false,
                'message' => 'Please select at least one product with a quantity greater than zero.',
            ];
        }

        // Extract and sort unique Product IDs ascending to eliminate deadlock risk during row locking
        $productIds = array_keys($itemMap);
        sort($productIds);

        // Step 2: Begin Database Transaction
        $this->db->beginTransaction();

        try {
            // Step 3: Lock Product Rows with SELECT ... FOR UPDATE
            $lockedProducts = $this->productRepository->findAndLockProducts($productIds);

            // Step 4: Verify Product Existence, Active Status, and Stock Availability
            $orderItemsToInsert = [];
            $totalAmount = 0.0;

            foreach ($itemMap as $productId => $requestedQty) {
                if (!isset($lockedProducts[$productId])) {
                    throw new HttpException(400, "Product ID #{$productId} is currently unavailable or inactive.");
                }

                $product = $lockedProducts[$productId];
                $currentStock = (int)$product['stock_quantity'];

                if ($currentStock < $requestedQty) {
                    throw new HttpException(400, "Insufficient stock for product '{$product['name']}'. Requested: {$requestedQty}, Available: {$currentStock}.");
                }

                // Step 5: Server-Side Price & Line Total Calculation
                $unitPrice = (float)$product['price'];
                $lineTotal = round($unitPrice * $requestedQty, 2);
                $totalAmount = round($totalAmount + $lineTotal, 2);

                $orderItemsToInsert[] = [
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'sku' => $product['sku'],
                    'quantity' => $requestedQty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            // Step 6: Insert Order Header Row
            $orderNumber = $this->orderRepository->generateOrderNumber();
            $orderId = $this->orderRepository->createOrder($userId, $orderNumber, $totalAmount);

            // Step 7: Insert Order Items Snapshot Rows & Deduct Stock
            foreach ($orderItemsToInsert as $item) {
                $this->orderRepository->createOrderItem(
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['sku'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['line_total']
                );

                // Step 8: Guarded Stock Deduction (UPDATE products SET stock = stock - qty WHERE stock >= qty)
                $deducted = $this->productRepository->deductStock($item['product_id'], $item['quantity']);
                if (!$deducted) {
                    throw new HttpException(400, "Failed to update stock for product '{$item['product_name']}'. Inventory changed during processing.");
                }
            }

            // Step 9: Commit Database Transaction
            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
            ];
        } catch (Throwable $e) {
            // Rollback all database mutations on any exception or failure
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve complete order details including itemized product snapshots,
     * verifying customer ownership or admin authorization.
     *
     * @param int $orderId Order ID.
     * @param int|null $userId Optional customer user ID filter.
     * @return array Order details dictionary.
     * @throws HttpException
     */
    public function getOrderDetails(int $orderId, ?int $userId = null): array
    {
        $order = $this->orderRepository->findById($orderId, $userId);

        if (!$order) {
            throw new HttpException(404, "Order #{$orderId} was not found or you are not authorized to view it.");
        }

        $items = $this->orderRepository->findItemsByOrderId($orderId);

        return [
            'order' => $order,
            'items' => $items,
        ];
    }

    /**
     * Retrieve paginated order history belonging strictly to the authenticated customer.
     * Enforces user ownership in database query.
     *
     * @param int $userId Authenticated customer user ID.
     * @param int $page Current page number.
     * @param int $perPage Items per page.
     * @return array Paginated order history dictionary.
     */
    public function listUserOrders(int $userId, int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $orders = $this->orderRepository->findUserOrders($userId, $page, $perPage);
        $total = $this->orderRepository->countUserOrders($userId);
        $totalPages = (int)ceil($total / $perPage);

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, $totalPages),
        ];
    }

    /**
     * Retrieve paginated order list for Admin with filter criteria (status, search query, date range).
     *
     * @param string|null $status Allowlisted status filter.
     * @param string|null $search Search string (Order ID/number/customer).
     * @param string|null $fromDate Start date string.
     * @param string|null $toDate End date string.
     * @param int $page Current page number.
     * @param int $perPage Records per page.
     * @return array Paginated order listing dictionary.
     */
    public function listAllOrders(?string $status = null, ?string $search = null, ?string $fromDate = null, ?string $toDate = null, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $orders = $this->orderRepository->findAllOrders($status, $search, $fromDate, $toDate, $page, $perPage);
        $total = $this->orderRepository->countAllOrders($status, $search, $fromDate, $toDate);
        $totalPages = (int)ceil($total / $perPage);

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, $totalPages),
            'filters' => [
                'status' => $status ?? 'all',
                'search' => $search ?? '',
                'from' => $fromDate ?? '',
                'to' => $toDate ?? '',
            ],
        ];
    }

    /**
     * Atomically cancel an order and restore stock quantities for all order line items inside a transaction.
     *
     * @param int $orderId Target order ID.
     * @param int|null $userId Authenticated customer user ID (null for admin execution).
     * @return array Result array e.g., ['success' => true, 'message' => '...']
     */
    public function cancelOrder(int $orderId, ?int $userId = null): array
    {
        $this->db->beginTransaction();

        try {
            // Lock order header row FOR UPDATE
            $order = $this->orderRepository->findForUpdate($orderId);

            if (!$order) {
                throw new HttpException(404, "Order #{$orderId} was not found.");
            }

            // Verify customer ownership if customer user ID is provided
            if ($userId !== null && (int)$order['user_id'] !== $userId) {
                throw new HttpException(403, "You are not authorized to cancel this order.");
            }

            // Check if order is already cancelled
            if (strtolower($order['status']) === 'cancelled') {
                throw new HttpException(400, "Order #{$orderId} is already cancelled.");
            }

            // If customer is cancelling, check eligible status (pending or confirmed)
            if ($userId !== null && strtolower($order['status']) === 'completed') {
                throw new HttpException(400, "Completed orders cannot be cancelled.");
            }

            // Fetch order items to restore stock
            $items = $this->orderRepository->findItemsByOrderId($orderId);

            if (!empty($items)) {
                // Extract product IDs & sort ascending to prevent deadlocks
                $productIds = array_map(fn($item) => (int)$item['product_id'], $items);
                $productIds = array_unique($productIds);
                sort($productIds);

                // Lock product rows FOR UPDATE
                $this->productRepository->findAndLockProducts($productIds);

                // Add stock back for each order item
                foreach ($items as $item) {
                    $this->productRepository->restoreStock((int)$item['product_id'], (int)$item['quantity']);
                }
            }

            // Update order status to cancelled
            $this->orderRepository->updateStatus($orderId, 'cancelled');

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Order #{$orderId} has been cancelled successfully and product inventory has been restored.",
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Change order status by Admin (pending, confirmed, completed, cancelled).
     * Automatically handles inventory restoration if status is changed to 'cancelled'.
     *
     * @param int $orderId Target order ID.
     * @param string $newStatus Allowed status ('pending', 'confirmed', 'completed', 'cancelled').
     * @return array Result array e.g., ['success' => true, 'message' => '...']
     */
    public function updateOrderStatusByAdmin(int $orderId, string $newStatus): array
    {
        $newStatus = strtolower(trim($newStatus));
        $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

        if (!in_array($newStatus, $validStatuses, true)) {
            return [
                'success' => false,
                'message' => "Invalid order status '{$newStatus}'. Allowed values: pending, confirmed, completed, cancelled.",
            ];
        }

        $this->db->beginTransaction();

        try {
            $order = $this->orderRepository->findForUpdate($orderId);

            if (!$order) {
                throw new HttpException(404, "Order #{$orderId} was not found.");
            }

            $currentStatus = strtolower($order['status']);

            if ($currentStatus === $newStatus) {
                $this->db->commit();
                return [
                    'success' => true,
                    'message' => "Order status is already set to '{$newStatus}'.",
                ];
            }

            if ($currentStatus === 'cancelled') {
                throw new HttpException(400, "Cannot change the status of an already cancelled order.");
            }

            if ($newStatus === 'cancelled') {
                // Execute inventory restoration
                $items = $this->orderRepository->findItemsByOrderId($orderId);
                if (!empty($items)) {
                    $productIds = array_map(fn($item) => (int)$item['product_id'], $items);
                    $productIds = array_unique($productIds);
                    sort($productIds);

                    $this->productRepository->findAndLockProducts($productIds);

                    foreach ($items as $item) {
                        $this->productRepository->restoreStock((int)$item['product_id'], (int)$item['quantity']);
                    }
                }
            }

            // Update order status
            $this->orderRepository->updateStatus($orderId, $newStatus);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Order #{$orderId} status updated from '{$currentStatus}' to '{$newStatus}' successfully.",
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
