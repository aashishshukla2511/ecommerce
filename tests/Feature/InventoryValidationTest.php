<?php

namespace Tests\Feature;

use App\Core\Database;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use Database\Seeders\DemoSeeder;
use PDO;

/**
 * Class InventoryValidationTest
 * 
 * Automated feature test suite for Inventory Validation:
 * - Reject orders when requested quantity exceeds available stock.
 * - Reject zero or negative quantities.
 * - Reject orders for out-of-stock items (stock = 0).
 * - Reject orders for inactive / deactivated products (is_active = 0).
 * - Reject orders for non-existent product IDs.
 * - Multi-item atomic validation (if one item fails stock check, whole order is rejected).
 * - Exact-boundary stock purchase (purchasing exact stock reduces stock to 0 safely).
 */
class InventoryValidationTest
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
        echo "=======================================================\n";
        echo "RUNNING INVENTORY VALIDATION TEST SUITE\n";
        echo "=======================================================\n";

        DemoSeeder::run();

        $this->testQuantityExceedingStockIsRejected();
        $this->testZeroAndNegativeQuantitiesAreRejected();
        $this->testOutOfStockProductOrderIsRejected();
        $this->testInactiveProductOrderIsRejected();
        $this->testNonExistentProductOrderIsRejected();
        $this->testAtomicMultiItemInventoryValidation();
        $this->testExactBoundaryStockDepletion();

        echo "\nALL INVENTORY VALIDATION TESTS PASSED SUCCESSFULLY!\n";
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
     * Test requesting quantity greater than available stock fails with clear message.
     */
    private function testQuantityExceedingStockIsRejected(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 10);
        $product = $all[0];
        $currentStock = (int)$product['stock_quantity'];

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $product['id'], 'quantity' => $currentStock + 10]
        ]);

        $this->assert($result['success'] === false, "Order exceeding available stock was rejected.");
        $this->assert(str_contains(strtolower($result['message']), 'insufficient stock'), "Insufficient stock message returned.");
        
        $postStock = (int)$this->productRepository->findById($product['id'])['stock_quantity'];
        $this->assert($postStock === $currentStock, "Stock quantity remained untouched after rejected order.");
    }

    /**
     * Test zero and negative quantity requests are rejected.
     */
    private function testZeroAndNegativeQuantitiesAreRejected(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 10);
        $productId = $all[0]['id'];

        // Test Zero Quantity
        $zeroRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => 0]
        ]);
        $this->assert($zeroRes['success'] === false, "Order with 0 quantity was rejected.");

        // Test Negative Quantity
        $negRes = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => -5]
        ]);
        $this->assert($negRes['success'] === false, "Order with negative quantity was rejected.");
    }

    /**
     * Test product with 0 stock cannot be ordered.
     */
    private function testOutOfStockProductOrderIsRejected(): void
    {
        $sku = 'OOS-VAL-' . time();
        $oosId = $this->productRepository->create([
            'name' => 'Out Of Stock Test Item',
            'sku' => $sku,
            'price' => 19.99,
            'stock_quantity' => 0,
            'is_active' => 1,
        ]);

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $oosId, 'quantity' => 1]
        ]);

        $this->assert($result['success'] === false, "Ordering out-of-stock product was rejected.");
        $this->assert(str_contains(strtolower($result['message']), 'insufficient stock'), "Error specifies insufficient stock.");
    }

    /**
     * Test inactive products (is_active = 0) are blocked from being ordered even if stock > 0.
     */
    private function testInactiveProductOrderIsRejected(): void
    {
        $sku = 'INACTIVE-VAL-' . time();
        $inactiveId = $this->productRepository->create([
            'name' => 'Inactive Test Item',
            'sku' => $sku,
            'price' => 35.00,
            'stock_quantity' => 20,
            'is_active' => 0,
        ]);

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $inactiveId, 'quantity' => 1]
        ]);

        $this->assert($result['success'] === false, "Order for inactive product was rejected.");
        $this->assert(str_contains(strtolower($result['message']), 'unavailable or inactive'), "Error specifies product is inactive.");
    }

    /**
     * Test non-existent product ID fails gracefully.
     */
    private function testNonExistentProductOrderIsRejected(): void
    {
        $result = $this->orderService->createOrder(2, [
            ['product_id' => 888888, 'quantity' => 1]
        ]);

        $this->assert($result['success'] === false, "Order for non-existent product ID was rejected.");
    }

    /**
     * Test multi-item order: if one item has insufficient stock, NO stock is deducted for ANY item (Atomic).
     */
    private function testAtomicMultiItemInventoryValidation(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 10);
        $validProd = $all[0];
        $validStockBefore = (int)$validProd['stock_quantity'];

        $sku = 'LIMITED-VAL-' . time();
        $limitedId = $this->productRepository->create([
            'name' => 'Scarce Item',
            'sku' => $sku,
            'price' => 10.00,
            'stock_quantity' => 1,
            'is_active' => 1,
        ]);

        // Request 1 valid item (valid stock) + 10 units of scarce item (exceeds 1)
        $result = $this->orderService->createOrder(2, [
            ['product_id' => $validProd['id'], 'quantity' => 1],
            ['product_id' => $limitedId, 'quantity' => 10],
        ]);

        $this->assert($result['success'] === false, "Multi-item order with one failing item was rejected.");

        // Verify valid product stock was NOT deducted
        $validStockAfter = (int)$this->productRepository->findById($validProd['id'])['stock_quantity'];
        $this->assert($validStockBefore === $validStockAfter, "Atomic integrity preserved: valid item stock unchanged.");

        $limitedStockAfter = (int)$this->productRepository->findById($limitedId)['stock_quantity'];
        $this->assert($limitedStockAfter === 1, "Scarce item stock unchanged.");
    }

    /**
     * Test purchasing exact available stock quantity transitions inventory to 0 without errors.
     */
    private function testExactBoundaryStockDepletion(): void
    {
        $sku = 'EXACT-VAL-' . time();
        $prodId = $this->productRepository->create([
            'name' => 'Exact Boundary Item',
            'sku' => $sku,
            'price' => 15.00,
            'stock_quantity' => 4,
            'is_active' => 1,
        ]);

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $prodId, 'quantity' => 4]
        ]);

        $this->assert($result['success'] === true, "Exact boundary stock purchase succeeded.");
        $finalStock = (int)$this->productRepository->findById($prodId)['stock_quantity'];
        $this->assert($finalStock === 0, "Inventory depleted to exactly 0 without constraint violation.");
    }
}
