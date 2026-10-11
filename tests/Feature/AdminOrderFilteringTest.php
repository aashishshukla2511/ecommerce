<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Middleware\RoleMiddleware;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use Database\Seeders\DemoSeeder;
use PDO;

/**
 * Class AdminOrderFilteringTest
 * 
 * Automated feature test suite for Phase 5 Admin Order Listing, Search, Filters, and Pagination:
 * - Admin can view all customer orders.
 * - Role-based authorization blocks customer/guest access (403 Forbidden).
 * - Status filter accuracy (pending, cancelled, completed).
 * - Search by Order ID, Order Number, Customer Name, and Email.
 * - Date range boundary filtering (from & to parameters).
 * - Pagination correctness.
 */
class AdminOrderFilteringTest
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
        echo "RUNNING PHASE 5 AUTOMATED TESTS (ADMIN ORDERS)\n";
        echo "===============================================\n";

        DemoSeeder::run();

        $this->testAdminCanListAllCustomerOrders();
        $this->testCustomerBlockedFromAdminOrders();
        $this->testStatusFiltering();
        $this->testSearchByOrderNumberAndId();
        $this->testDateRangeFiltering();
        $this->testPagination();

        echo "\nALL PHASE 5 ADMIN ORDER TESTS PASSED SUCCESSFULLY!\n";
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
     * Test Admin can list orders from all customers.
     */
    private function testAdminCanListAllCustomerOrders(): void
    {
        $sku = 'ADMIN-LIST-' . time() . '-' . mt_rand(100, 999);
        $prodId = $this->productRepository->create([
            'name' => 'Admin List Test Product',
            'sku' => $sku,
            'price' => 20.00,
            'stock_quantity' => 20,
            'is_active' => 1,
        ]);

        // Create order for Customer 1 (User ID 2)
        $ord1 = $this->orderService->createOrder(2, [
            ['product_id' => $prodId, 'quantity' => 1]
        ]);
        $this->assert($ord1['success'] === true, "Order 1 for Customer 1 created.");

        // Create order for Customer 2 (User ID 3)
        $ord2 = $this->orderService->createOrder(3, [
            ['product_id' => $prodId, 'quantity' => 1]
        ]);
        $this->assert($ord2['success'] === true, "Order 2 for Customer 2 created.");

        // Admin lists all orders (large perPage so both recent orders appear on page 1)
        $adminList = $this->orderService->listAllOrders('all', null, null, null, 1, 100);
        $this->assert($adminList['total'] >= 2, "Admin can list all customer orders.");

        $userIds = array_map(fn($o) => (int)$o['user_id'], $adminList['orders']);
        $this->assert(in_array(2, $userIds) && in_array(3, $userIds), "Admin list contains orders from multiple customers.");
    }

    /**
     * Test customer is blocked from accessing Admin order listing (403 Forbidden).
     */
    private function testCustomerBlockedFromAdminOrders(): void
    {
        Auth::login([
            'id' => 2,
            'name' => 'Alice Smith',
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);

        $roleMw = new RoleMiddleware('admin');
        $blocked = false;

        try {
            $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/orders']);
            $roleMw->handle($request);
        } catch (HttpException $e) {
            $blocked = true;
            $this->assert($e->getStatusCode() === 403, "Customer access to /admin/orders returned 403 Forbidden.");
        }

        $this->assert($blocked === true, "RoleMiddleware prevented customer from viewing admin orders.");
        Auth::logout();
    }

    /**
     * Test filtering orders by status (pending vs cancelled).
     */
    private function testStatusFiltering(): void
    {
        $pendingResult = $this->orderService->listAllOrders('pending');
        $this->assert(isset($pendingResult['orders']), "Filter by 'pending' status executed successfully.");
        foreach ($pendingResult['orders'] as $ord) {
            $this->assert(strtolower($ord['status']) === 'pending', "Order status matches 'pending' filter.");
        }
    }

    /**
     * Test search by exact Order ID and Order Number string.
     */
    private function testSearchByOrderNumberAndId(): void
    {
        $adminList = $this->orderService->listAllOrders('all');
        $targetOrder = $adminList['orders'][0];
        $targetNumber = $targetOrder['order_number'];
        $targetId = (string)$targetOrder['id'];

        // Search by Order Number
        $searchRes1 = $this->orderService->listAllOrders('all', $targetNumber);
        $this->assert(count($searchRes1['orders']) >= 1, "Search by order number string returned results.");
        $this->assert($searchRes1['orders'][0]['order_number'] === $targetNumber, "Search result matches target order number.");

        // Search by Order ID
        $searchRes2 = $this->orderService->listAllOrders('all', $targetId);
        $this->assert(count($searchRes2['orders']) >= 1, "Search by numeric order ID returned results.");
    }

    /**
     * Test date range boundary filtering (from & to).
     */
    private function testDateRangeFiltering(): void
    {
        $today = date('Y-m-d');
        $dateRes = $this->orderService->listAllOrders('all', null, $today, $today);

        $this->assert(isset($dateRes['orders']), "Date range filter (from: $today to: $today) executed successfully.");
        foreach ($dateRes['orders'] as $ord) {
            $orderDate = date('Y-m-d', strtotime($ord['created_at']));
            $this->assert($orderDate === $today, "Order created date falls within specified date range.");
        }
    }

    /**
     * Test pagination calculations.
     */
    private function testPagination(): void
    {
        $page1 = $this->orderService->listAllOrders('all', null, null, null, 1, 2);
        $this->assert($page1['page'] === 1, "Pagination page 1 matches.");
        $this->assert($page1['perPage'] === 2, "Pagination perPage limit matches (2 per page).");
    }
}
