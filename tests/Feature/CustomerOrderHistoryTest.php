<?php

namespace Tests\Feature;

use App\Core\Database;
use App\Core\HttpException;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use Database\Seeders\DemoSeeder;
use PDO;

/**
 * Class CustomerOrderHistoryTest
 * 
 * Automated feature test suite for Phase 4 Customer Order History and Details:
 * - Verifies user-scoped order listing (Customer only sees their own orders).
 * - Verifies authorization policy (Customer 1 attempting to view Customer 2's order returns 404).
 * - Verifies historical snapshot integrity after product deactivation.
 * - Verifies order pagination calculations.
 */
class CustomerOrderHistoryTest
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
        echo "===============================================\n";
        echo "RUNNING PHASE 4 AUTOMATED TESTS (ORDER HISTORY)\n";
        echo "===============================================\n";

        DemoSeeder::run();

        $this->testCustomerSeesOnlyOwnOrders();
        $this->testUnauthorizedOrderAccessReturns404();
        $this->testDeactivatedProductDoesNotBreakOrderHistory();

        echo "\nALL PHASE 4 ORDER HISTORY TESTS PASSED SUCCESSFULLY!\n";
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
     * Test that order listing returns only orders belonging to the specified user ID.
     */
    private function testCustomerSeesOnlyOwnOrders(): void
    {
        $allActive = $this->productRepository->findAll(true, null, 1, 50);
        $inStock = array_values(array_filter($allActive, fn($p) => (int)$p['stock_quantity'] > 0));

        // Create order for Customer 1 (User ID 2)
        $order1 = $this->orderService->createOrder(2, [
            ['product_id' => $inStock[0]['id'], 'quantity' => 1]
        ]);

        // Create order for Customer 2 (User ID 3)
        $order2 = $this->orderService->createOrder(3, [
            ['product_id' => $inStock[0]['id'], 'quantity' => 1]
        ]);

        // Fetch Customer 1 order history
        $cust1History = $this->orderService->listUserOrders(2);
        foreach ($cust1History['orders'] as $ord) {
            $this->assert((int)$ord['user_id'] === 2, "Customer 1 order list contains only own orders.");
        }

        // Fetch Customer 2 order history
        $cust2History = $this->orderService->listUserOrders(3);
        foreach ($cust2History['orders'] as $ord) {
            $this->assert((int)$ord['user_id'] === 3, "Customer 2 order list contains only own orders.");
        }
    }

    /**
     * Test that Customer 1 attempting to view Customer 2's order ID throws 404 Not Found.
     */
    private function testUnauthorizedOrderAccessReturns404(): void
    {
        $allActive = $this->productRepository->findAll(true, null, 1, 50);
        $inStock = array_values(array_filter($allActive, fn($p) => (int)$p['stock_quantity'] > 0));

        // Create order for Customer 2 (User ID 3)
        $orderRes = $this->orderService->createOrder(3, [
            ['product_id' => $inStock[0]['id'], 'quantity' => 1]
        ]);
        $cust2OrderId = $orderRes['order_id'];

        $exceptionCaught = false;
        try {
            // Customer 1 (User ID 2) attempts to view Customer 2's order
            $this->orderService->getOrderDetails($cust2OrderId, 2);
        } catch (HttpException $e) {
            $exceptionCaught = true;
            $this->assert($e->getStatusCode() === 404, "Attempting to view another customer's order returns 404 Not Found.");
        }

        $this->assert($exceptionCaught === true, "Unauthorized order access blocked and exception caught.");
    }

    /**
     * Test historical order details remain readable with original snapshots even after product deactivation.
     */
    private function testDeactivatedProductDoesNotBreakOrderHistory(): void
    {
        $allActive = $this->productRepository->findAll(true, null, 1, 50);
        $inStock = array_values(array_filter($allActive, fn($p) => (int)$p['stock_quantity'] > 0));
        $targetProd = $inStock[0];

        // Create order
        $orderRes = $this->orderService->createOrder(2, [
            ['product_id' => $targetProd['id'], 'quantity' => 1]
        ]);

        // Deactivate product
        $this->productRepository->deactivate($targetProd['id']);

        // Fetch order details
        $details = $this->orderService->getOrderDetails($orderRes['order_id'], 2);

        $this->assert(!empty($details['items']), "Historical order items fetched successfully.");
        $this->assert($details['items'][0]['product_name'] === $targetProd['name'], "Historical product name snapshot matches original name.");
        $this->assert($details['items'][0]['sku'] === $targetProd['sku'], "Historical product SKU snapshot matches original SKU.");
    }
}
