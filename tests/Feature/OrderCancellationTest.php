<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use Database\Seeders\DemoSeeder;
use PDO;

/**
 * Class OrderCancellationTest
 * 
 * Automated feature test suite for Phase 6 — Order Cancellation and Inventory Restoration:
 * - Customer cancels own order in eligible status (pending/confirmed).
 * - Stock quantity is restored atomically to product inventory.
 * - Double cancellation is prevented (idempotency & no duplicate stock restoration).
 * - Unauthorized customer cancellation is blocked (ownership check).
 * - Admin updates status from detail view (pending -> confirmed -> completed -> cancelled).
 * - 'shipped' status is removed and rejected.
 */
class OrderCancellationTest
{
    private OrderService $orderService;
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->orderRepository = new OrderRepository($this->db);
        $this->productRepository = new ProductRepository($this->db);
        $this->orderService = new OrderService($this->orderRepository, $this->productRepository, $this->db);
    }

    public function runAllTests(): void
    {
        echo "=====================================================\n";
        echo "RUNNING PHASE 6 AUTOMATED TESTS (ORDER CANCELLATION)\n";
        echo "=====================================================\n";

        DemoSeeder::run();
        $this->db->exec("UPDATE products SET is_active = 1, stock_quantity = 25 WHERE is_active = 1");

        $this->testCustomerCancelOrderAndRestoreStock();
        $this->testPreventDuplicateCancellationStockRestoration();
        $this->testCustomerCannotCancelOtherCustomerOrder();
        $this->testAdminChangeStatusToConfirmedAndCompleted();
        $this->testAdminCancelOrderRestoresStock();
        $this->testShippedStatusIsRemovedAndRejected();

        echo "\nALL PHASE 6 ORDER CANCELLATION TESTS PASSED SUCCESSFULLY!\n";
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            echo "[FAIL] {$message}\n";
            exit(1);
        }
        echo "[PASS] {$message}\n";
    }

    /**
     * Test Customer cancels pending order and stock is restored.
     */
    private function testCustomerCancelOrderAndRestoreStock(): void
    {
        $sku = 'CANCEL-PROD-1-' . time() . '-' . mt_rand(100, 999);
        $initialStock = 10;
        $productId = $this->productRepository->create([
            'name' => 'Cancellation Test Item 1',
            'sku' => $sku,
            'price' => 25.00,
            'stock_quantity' => $initialStock,
            'is_active' => 1,
        ]);

        // Customer 1 (user 2) creates an order for 2 units
        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 2]
        ]);
        $this->assert($orderRes['success'], "Order created successfully prior to cancellation test.");

        $postOrderProduct = $this->productRepository->findById($productId);
        $this->assert((int)$postOrderProduct['stock_quantity'] === ($initialStock - 2), "Stock deducted by 2 units on order creation.");

        // Customer 1 cancels their order
        $cancelRes = $this->orderService->cancelOrder($orderRes['order_id'], 2);
        $this->assert($cancelRes['success'], "Customer cancelled order successfully.");

        $postCancelProduct = $this->productRepository->findById($productId);
        $this->assert((int)$postCancelProduct['stock_quantity'] === $initialStock, "Stock restored back to original level ({$initialStock}) after cancellation.");

        $order = $this->orderRepository->findById($orderRes['order_id']);
        $this->assert(strtolower($order['status']) === 'cancelled', "Order status set to 'cancelled'.");
        $this->assert(!empty($order['cancelled_at']), "Order cancelled_at timestamp recorded.");
    }

    /**
     * Test idempotency: attempting to cancel an already cancelled order fails without duplicating stock.
     */
    private function testPreventDuplicateCancellationStockRestoration(): void
    {
        $sku = 'CANCEL-PROD-2-' . time() . '-' . mt_rand(100, 999);
        $productId = $this->productRepository->create([
            'name' => 'Cancellation Test Item 2',
            'sku' => $sku,
            'price' => 30.00,
            'stock_quantity' => 10,
            'is_active' => 1,
        ]);

        // Create order
        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 1]
        ]);
        $orderId = $orderRes['order_id'];

        // Cancel order 1st time
        $this->orderService->cancelOrder($orderId, 2);
        $stockAfterFirstCancel = (int)$this->productRepository->findById($productId)['stock_quantity'];

        // Attempt cancel order 2nd time
        $secondCancelRes = $this->orderService->cancelOrder($orderId, 2);
        $this->assert($secondCancelRes['success'] === false, "Second cancellation attempt rejected.");

        $stockAfterSecondCancel = (int)$this->productRepository->findById($productId)['stock_quantity'];
        $this->assert($stockAfterFirstCancel === $stockAfterSecondCancel, "Stock level unchanged after duplicate cancellation attempt.");
    }

    /**
     * Test customer cannot cancel another customer's order.
     */
    private function testCustomerCannotCancelOtherCustomerOrder(): void
    {
        $sku = 'CANCEL-PROD-3-' . time() . '-' . mt_rand(100, 999);
        $productId = $this->productRepository->create([
            'name' => 'Cancellation Test Item 3',
            'sku' => $sku,
            'price' => 20.00,
            'stock_quantity' => 10,
            'is_active' => 1,
        ]);

        // Customer 1 (User ID 2) creates order
        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 1]
        ]);
        $orderId = $orderRes['order_id'];

        // Customer 2 (User ID 3) attempts to cancel Customer 1's order
        $cancelRes = $this->orderService->cancelOrder($orderId, 3);
        $this->assert($cancelRes['success'] === false, "Customer 2 blocked from cancelling Customer 1's order.");
    }

    /**
     * Test Admin updates order status to 'confirmed' and 'completed'.
     */
    private function testAdminChangeStatusToConfirmedAndCompleted(): void
    {
        $sku = 'CANCEL-PROD-4-' . time() . '-' . mt_rand(100, 999);
        $productId = $this->productRepository->create([
            'name' => 'Cancellation Test Item 4',
            'sku' => $sku,
            'price' => 15.00,
            'stock_quantity' => 10,
            'is_active' => 1,
        ]);

        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 1]
        ]);
        $orderId = $orderRes['order_id'];

        // Admin changes status to confirmed
        $confRes = $this->orderService->updateOrderStatusByAdmin($orderId, 'confirmed');
        $this->assert($confRes['success'], "Admin changed status to 'confirmed'.");

        $order = $this->orderRepository->findById($orderId);
        $this->assert($order['status'] === 'confirmed', "Database order status is 'confirmed'.");

        // Admin changes status to completed
        $compRes = $this->orderService->updateOrderStatusByAdmin($orderId, 'completed');
        $this->assert($compRes['success'], "Admin changed status to 'completed'.");

        $order = $this->orderRepository->findById($orderId);
        $this->assert($order['status'] === 'completed', "Database order status is 'completed'.");
    }

    /**
     * Test Admin cancels order via status change, triggering inventory restoration.
     */
    private function testAdminCancelOrderRestoresStock(): void
    {
        $sku = 'CANCEL-PROD-5-' . time() . '-' . mt_rand(100, 999);
        $initialStock = 10;
        $productId = $this->productRepository->create([
            'name' => 'Cancellation Test Item 5',
            'sku' => $sku,
            'price' => 40.00,
            'stock_quantity' => $initialStock,
            'is_active' => 1,
        ]);

        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 3]
        ]);
        $orderId = $orderRes['order_id'];

        // Admin updates status to 'cancelled'
        $adminCancelRes = $this->orderService->updateOrderStatusByAdmin($orderId, 'cancelled');
        $this->assert($adminCancelRes['success'], "Admin updated status to 'cancelled'.");

        $finalStock = (int)$this->productRepository->findById($productId)['stock_quantity'];
        $this->assert($finalStock === $initialStock, "Stock restored by 3 units after Admin cancellation.");
    }

    /**
     * Test 'shipped' status is no longer valid.
     */
    private function testShippedStatusIsRemovedAndRejected(): void
    {
        $sku = 'CANCEL-PROD-6-' . time() . '-' . mt_rand(100, 999);
        $productId = $this->productRepository->create([
            'name' => 'Cancellation Test Item 6',
            'sku' => $sku,
            'price' => 50.00,
            'stock_quantity' => 10,
            'is_active' => 1,
        ]);

        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 1]
        ]);
        $orderId = $orderRes['order_id'];

        $shippedRes = $this->orderService->updateOrderStatusByAdmin($orderId, 'shipped');
        $this->assert($shippedRes['success'] === false, "Setting status to 'shipped' is rejected.");
    }
}
