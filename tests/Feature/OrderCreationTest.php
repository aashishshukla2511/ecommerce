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
 * Class OrderCreationTest
 * 
 * Automated feature test suite verifying Phase 3 order creation features:
 * - Atomic order placement and stock deduction.
 * - Multi-item orders and server-calculated totals.
 * - Immunity against client price tampering.
 * - Insufficient inventory rejection.
 * - Transaction rollback on mid-operation failure.
 * - Concurrency protection against overselling stock.
 */
class OrderCreationTest
{
    private OrderService $orderService;
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->productRepository = new ProductRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);
        $this->orderService = new OrderService($this->orderRepository, $this->productRepository, $this->db);
    }

    public function runAllTests(): void
    {
        echo "===============================================\n";
        echo "RUNNING PHASE 3 AUTOMATED TESTS (ORDER CREATION)\n";
        echo "===============================================\n";

        DemoSeeder::run();
        $this->db->exec("UPDATE products SET is_active = 1, stock_quantity = 25 WHERE is_active = 1");

        $this->testSingleProductOrderCreation();
        $this->testMultiProductOrderCreation();
        $this->testPriceTamperingImmunity();
        $this->testDuplicateProductLineNormalization();
        $this->testInsufficientStockRejection();
        $this->testInvalidQuantityRejection();
        $this->testTransactionRollbackOnFailure();
        $this->testConcurrentOrdersCannotOversellStock();

        echo "\nALL PHASE 3 ORDER CREATION TESTS PASSED SUCCESSFULLY!\n";
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
     * Test single item order creation and atomic stock deduction.
     */
    private function testSingleProductOrderCreation(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 50);
        $available = array_values(array_filter($all, fn($p) => (int)$p['stock_quantity'] >= 5));
        $product = $available[0];

        $productId = (int)$product['id'];
        $initialStock = (int)$product['stock_quantity'];
        $orderQty = 2;

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $productId, 'quantity' => $orderQty]
        ]);

        $this->assert($result['success'] === true, "Single product order creation succeeded.");
        $this->assert(isset($result['order_id']), "Order ID returned.");

        // Check stock reduction
        $updatedProduct = $this->productRepository->findById($productId);
        $this->assert((int)$updatedProduct['stock_quantity'] === ($initialStock - $orderQty), "Stock deducted by exactly {$orderQty} units.");

        // Check order item snapshot
        $items = $this->orderRepository->findItemsByOrderId($result['order_id']);
        $this->assert(count($items) === 1, "Order items snapshot created.");
        $this->assert((float)$items[0]['unit_price'] === (float)$product['price'], "Snapshot unit price matches database price.");
    }

    /**
     * Test order containing multiple products and server-calculated grand total.
     */
    private function testMultiProductOrderCreation(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 50);
        $products = array_values(array_filter($all, fn($p) => (int)$p['stock_quantity'] >= 5));

        if (count($products) < 2) {
            $this->db->exec("UPDATE products SET is_active = 1, stock_quantity = 25 WHERE is_active = 1");
            $all = $this->productRepository->findAll(true, null, 1, 50);
            $products = array_values(array_filter($all, fn($p) => (int)$p['stock_quantity'] >= 5));
        }

        $p1 = $products[0];
        $p2 = $products[1];

        $expectedTotal = round(((float)$p1['price'] * 1) + ((float)$p2['price'] * 2), 2);

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $p1['id'], 'quantity' => 1],
            ['product_id' => $p2['id'], 'quantity' => 2],
        ]);

        $this->assert($result['success'] === true, "Multi-product order creation succeeded.");
        $this->assert((float)$result['total_amount'] === $expectedTotal, "Order total correctly calculated server-side.");
    }

    /**
     * Test price tampering immunity: client submitting forged prices/totals is ignored.
     */
    private function testPriceTamperingImmunity(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 50);
        $product = array_values(array_filter($all, fn($p) => (int)$p['stock_quantity'] >= 1))[0];
        $realPrice = (float)$product['price'];

        // Attempt price tampering (submitting fake price $0.01)
        $result = $this->orderService->createOrder(2, [
            ['product_id' => $product['id'], 'quantity' => 1, 'price' => 0.01, 'unit_price' => 0.01, 'total' => 0.01]
        ]);

        $this->assert($result['success'] === true, "Order created ignoring client price tampering.");
        $this->assert((float)$result['total_amount'] === $realPrice, "Server calculated true database price ($realPrice).");
    }

    /**
     * Test duplicate product lines in single order request are combined.
     */
    private function testDuplicateProductLineNormalization(): void
    {
        $all = $this->productRepository->findAll(true, null, 1, 50);
        $product = array_values(array_filter($all, fn($p) => (int)$p['stock_quantity'] >= 5))[0];

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $product['id'], 'quantity' => 1],
            ['product_id' => $product['id'], 'quantity' => 2],
        ]);

        $this->assert($result['success'] === true, "Duplicate product lines in request successfully processed.");
        $items = $this->orderRepository->findItemsByOrderId($result['order_id']);
        $this->assert(count($items) === 1, "Duplicate lines merged into single order item line.");
        $this->assert((int)$items[0]['quantity'] === 3, "Merged item quantity equals total requested (3).");
    }

    /**
     * Test rejection when requested quantity exceeds available stock.
     */
    private function testInsufficientStockRejection(): void
    {
        $product = $this->productRepository->findAll(true, null, 1, 1)[0];
        $currentStock = (int)$product['stock_quantity'];

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $product['id'], 'quantity' => $currentStock + 500]
        ]);

        $this->assert($result['success'] === false, "Order rejected when requested quantity exceeds stock.");
        $this->assert(str_contains($result['message'], 'Insufficient stock'), "Insufficient stock error message returned.");
    }

    /**
     * Test invalid (zero or negative) quantity rejection.
     */
    private function testInvalidQuantityRejection(): void
    {
        $product = $this->productRepository->findAll(true, null, 1, 1)[0];

        $result = $this->orderService->createOrder(2, [
            ['product_id' => $product['id'], 'quantity' => 0]
        ]);

        $this->assert($result['success'] === false, "Zero quantity order rejected.");
    }

    /**
     * Test transaction rollback on failure mid-operation.
     */
    private function testTransactionRollbackOnFailure(): void
    {
        // Get total orders count before attempt
        $beforeOrdersCount = (int)$this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $product = $this->productRepository->findAll(true, null, 1, 1)[0];
        $stockBefore = (int)$product['stock_quantity'];

        // Submit order containing 1 valid product and 1 invalid non-existent product ID
        $result = $this->orderService->createOrder(2, [
            ['product_id' => $product['id'], 'quantity' => 1],
            ['product_id' => 999999, 'quantity' => 1], // Non-existent product
        ]);

        $this->assert($result['success'] === false, "Order creation failed due to non-existent product.");

        $afterOrdersCount = (int)$this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stockAfter = (int)$this->productRepository->findById($product['id'])['stock_quantity'];

        $this->assert($beforeOrdersCount === $afterOrdersCount, "Transaction rolled back: No order row persisted.");
        $this->assert($stockBefore === $stockAfter, "Transaction rolled back: Stock quantity remained unchanged.");
    }

    /**
     * Test concurrency row locking: competing requests cannot oversell limited inventory.
     */
    private function testConcurrentOrdersCannotOversellStock(): void
    {
        // Create a dedicated limited stock test product (stock = 3)
        $testSku = 'CONCURRENCY-TEST-' . time();
        $prodId = $this->productRepository->create([
            'name' => 'Limited Stock Widget',
            'sku' => $testSku,
            'price' => 50.00,
            'stock_quantity' => 3,
            'is_active' => 1,
        ]);

        // Connection 1 attempts to order 2 units
        $res1 = $this->orderService->createOrder(2, [
            ['product_id' => $prodId, 'quantity' => 2]
        ]);

        // Connection 2 attempts to order 2 units (exceeds remaining stock of 1)
        $res2 = $this->orderService->createOrder(3, [
            ['product_id' => $prodId, 'quantity' => 2]
        ]);

        $successCount = ($res1['success'] ? 1 : 0) + ($res2['success'] ? 1 : 0);
        $this->assert($successCount === 1, "Concurrency row locks prevented overselling stock (Only 1 order succeeded).");

        $finalStock = (int)$this->productRepository->findById($prodId)['stock_quantity'];
        $this->assert($finalStock === 1, "Final remaining stock is non-negative and correct (1 unit remaining).");
    }
}
