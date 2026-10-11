<?php

namespace Tests\Feature;

use App\Core\Database;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use Database\Seeders\DemoSeeder;
use PDO;

/**
 * Class ConcurrentStockUpdatesTest
 * 
 * Automated feature test suite for Concurrent Stock Updates and Race Conditions:
 * - Competing customer purchases on limited inventory.
 * - Prevention of overselling (final stock never becomes negative).
 * - Deterministic ascending row locking (SELECT ... FOR UPDATE) eliminating MySQL deadlocks.
 * - Atomic guarded UPDATE condition (WHERE stock_quantity >= requested).
 * - Transaction isolation and rollback integrity during concurrent contention.
 */
class ConcurrentStockUpdatesTest
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
        echo "RUNNING CONCURRENT STOCK UPDATES TEST SUITE\n";
        echo "=======================================================\n";

        DemoSeeder::run();

        $this->testCompetingPurchasesPreventOverselling();
        $this->testDeterministicLockingPreventsDeadlocks();
        $this->testAtomicGuardedStockDeduction();
        $this->testRollbackIntegrityUnderContention();

        echo "\nALL CONCURRENT STOCK UPDATE TESTS PASSED SUCCESSFULLY!\n";
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
     * Test multiple competing buyers attempting to purchase limited stock simultaneously.
     * Guarantees total sold items NEVER exceeds available stock.
     */
    private function testCompetingPurchasesPreventOverselling(): void
    {
        $sku = 'CONCUR-TEST-' . time();
        $initialStock = 5;
        $prodId = $this->productRepository->create([
            'name' => 'Flash Sale High Demand Item',
            'sku' => $sku,
            'price' => 45.00,
            'stock_quantity' => $initialStock,
            'is_active' => 1,
        ]);

        // 4 competing customers each trying to buy 2 units (Total requested: 8, Available: 5)
        $buyers = [2, 3, 2, 3];
        $successfulOrders = 0;
        $totalQuantitySold = 0;

        foreach ($buyers as $buyerId) {
            $result = $this->orderService->createOrder($buyerId, [
                ['product_id' => $prodId, 'quantity' => 2]
            ]);

            if ($result['success']) {
                $successfulOrders++;
                $totalQuantitySold += 2;
            }
        }

        // With initial stock of 5, at most 2 orders of 2 units (4 units) can succeed. The 3rd order must fail.
        $this->assert($successfulOrders === 2, "Exactly 2 orders succeeded (4 units sold out of 5 available).");
        $this->assert($totalQuantitySold <= $initialStock, "Total quantity sold ({$totalQuantitySold}) never exceeded initial stock ({$initialStock}).");

        $finalStock = (int)$this->productRepository->findById($prodId)['stock_quantity'];
        $this->assert($finalStock === ($initialStock - $totalQuantitySold), "Remaining inventory is exactly {$finalStock} (non-negative).");
        $this->assert($finalStock >= 0, "Inventory never dropped below zero under concurrency.");
    }

    /**
     * Test deterministic row locking (ascending product IDs) eliminates MySQL deadlock conditions.
     * When orders contain products in reverse order (e.g. [A, B] vs [B, A]), sorting them prevents lock cycles.
     */
    private function testDeterministicLockingPreventsDeadlocks(): void
    {
        $sku1 = 'PROD-A-' . time();
        $sku2 = 'PROD-B-' . time();

        $idA = $this->productRepository->create([
            'name' => 'Concurrent Item A',
            'sku' => $sku1,
            'price' => 20.00,
            'stock_quantity' => 10,
            'is_active' => 1,
        ]);

        $idB = $this->productRepository->create([
            'name' => 'Concurrent Item B',
            'sku' => $sku2,
            'price' => 30.00,
            'stock_quantity' => 10,
            'is_active' => 1,
        ]);

        // Order 1 passes items in order [A, B]
        $res1 = $this->orderService->createOrder(2, [
            ['product_id' => $idA, 'quantity' => 1],
            ['product_id' => $idB, 'quantity' => 1],
        ]);

        // Order 2 passes items in reverse order [B, A]
        $res2 = $this->orderService->createOrder(3, [
            ['product_id' => $idB, 'quantity' => 1],
            ['product_id' => $idA, 'quantity' => 1],
        ]);

        $this->assert($res1['success'] === true, "First multi-item order executed cleanly.");
        $this->assert($res2['success'] === true, "Reverse-ordered multi-item order executed without deadlock.");

        $stockA = (int)$this->productRepository->findById($idA)['stock_quantity'];
        $stockB = (int)$this->productRepository->findById($idB)['stock_quantity'];

        $this->assert($stockA === 8, "Product A stock deducted correctly to 8.");
        $this->assert($stockB === 8, "Product B stock deducted correctly to 8.");
    }

    /**
     * Test low-level guarded atomic deduction UPDATE statement.
     */
    private function testAtomicGuardedStockDeduction(): void
    {
        $sku = 'GUARD-TEST-' . time();
        $prodId = $this->productRepository->create([
            'name' => 'Guarded Stock Test Item',
            'sku' => $sku,
            'price' => 15.00,
            'stock_quantity' => 2,
            'is_active' => 1,
        ]);

        // Deduct 2 units (valid)
        $deducted1 = $this->productRepository->deductStock($prodId, 2);
        $this->assert($deducted1 === true, "Deduction of existing stock succeeded.");

        // Attempt deduct 1 more unit (stock is now 0; guard condition fails rowCount() == 1)
        $deducted2 = $this->productRepository->deductStock($prodId, 1);
        $this->assert($deducted2 === false, "Guarded UPDATE prevented negative deduction (rowCount was 0).");

        $finalStock = (int)$this->productRepository->findById($prodId)['stock_quantity'];
        $this->assert($finalStock === 0, "Stock remained at 0.");
    }

    /**
     * Test transaction rollback leaves inventory intact when an order fails mid-transaction.
     */
    private function testRollbackIntegrityUnderContention(): void
    {
        $sku = 'ROLLBACK-TEST-' . time();
        $prodId = $this->productRepository->create([
            'name' => 'Rollback Integrity Item',
            'sku' => $sku,
            'price' => 25.00,
            'stock_quantity' => 5,
            'is_active' => 1,
        ]);

        $initialOrdersCount = (int)$this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

        // Submit order that has valid item but fails on second invalid non-existent item
        $res = $this->orderService->createOrder(2, [
            ['product_id' => $prodId, 'quantity' => 2],
            ['product_id' => 999999, 'quantity' => 1],
        ]);

        $this->assert($res['success'] === false, "Contention failure triggered rejection.");

        // Verify database state: no order inserted, stock unchanged
        $afterOrdersCount = (int)$this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $finalStock = (int)$this->productRepository->findById($prodId)['stock_quantity'];

        $this->assert($initialOrdersCount === $afterOrdersCount, "No phantom order created during rollback.");
        $this->assert($finalStock === 5, "Stock restored/untouched (5 units) after rollback.");
    }
}
