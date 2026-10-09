<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use Database\Seeders\DemoSeeder;
use PDO;

class ProductManagementTest
{
    private ProductService $productService;
    private ProductRepository $repository;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->repository = new ProductRepository($this->db);
        $this->productService = new ProductService($this->repository);
    }

    public function runAllTests(): void
    {
        echo "===============================================\n";
        echo "RUNNING PHASE 1 AUTOMATED TESTS (PRODUCT MODULE)\n";
        echo "===============================================\n";

        DemoSeeder::run();

        $this->testCreateValidProduct();
        $this->testDuplicateSkuRejection();
        $this->testNegativePriceRejection();
        $this->testNegativeStockRejection();
        $this->testMissingNameRejection();
        $this->testUpdateProduct();
        $this->testDeactivateProduct();
        $this->testCustomerCatalogOnlyActive();

        echo "\nALL PHASE 1 PRODUCT TESTS PASSED SUCCESSFULLY!\n";
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            echo "[FAIL] {$message}\n";
            exit(1);
        }
        echo "[PASS] {$message}\n";
    }

    private function testCreateValidProduct(): void
    {
        $testSku = 'TEST-SKU-' . time();
        $data = [
            'name' => 'Smart Mechanical Watch',
            'sku' => $testSku,
            'description' => 'A stylish hybrid smartwatch',
            'price' => 199.99,
            'stock_quantity' => 15,
            'is_active' => 1,
        ];

        $result = $this->productService->createProduct($data);
        $this->assert($result['success'] === true, "Admin can create a valid product.");
        $this->assert($result['product']['sku'] === $testSku, "Product SKU matched created value.");
        $this->assert((float)$result['product']['price'] === 199.99, "Product price stored correctly.");
    }

    private function testDuplicateSkuRejection(): void
    {
        $existing = $this->repository->findAll(false, null, 1, 1)[0];
        $data = [
            'name' => 'Duplicate SKU Product',
            'sku' => $existing['sku'],
            'price' => 49.99,
            'stock_quantity' => 10,
        ];

        $result = $this->productService->createProduct($data);
        $this->assert($result['success'] === false, "Duplicate SKU is rejected.");
        $this->assert(isset($result['errors']['sku']), "Validation error returned for SKU field.");
    }

    private function testNegativePriceRejection(): void
    {
        $data = [
            'name' => 'Invalid Price Product',
            'sku' => 'INVALID-PRICE-SKU',
            'price' => -10.00,
            'stock_quantity' => 5,
        ];

        $result = $this->productService->createProduct($data);
        $this->assert($result['success'] === false, "Negative price is rejected.");
        $this->assert(isset($result['errors']['price']), "Validation error returned for price.");
    }

    private function testNegativeStockRejection(): void
    {
        $data = [
            'name' => 'Invalid Stock Product',
            'sku' => 'INVALID-STOCK-SKU',
            'price' => 15.00,
            'stock_quantity' => -5,
        ];

        $result = $this->productService->createProduct($data);
        $this->assert($result['success'] === false, "Negative stock quantity is rejected.");
        $this->assert(isset($result['errors']['stock_quantity']), "Validation error returned for stock.");
    }

    private function testMissingNameRejection(): void
    {
        $data = [
            'name' => '',
            'sku' => 'NO-NAME-SKU',
            'price' => 20.00,
            'stock_quantity' => 10,
        ];

        $result = $this->productService->createProduct($data);
        $this->assert($result['success'] === false, "Missing product name is rejected.");
        $this->assert(isset($result['errors']['name']), "Validation error returned for name.");
    }

    private function testUpdateProduct(): void
    {
        $product = $this->repository->findAll(false, null, 1, 1)[0];
        $id = (int)$product['id'];

        $updateData = [
            'name' => 'Updated Name ' . rand(100, 999),
            'sku' => $product['sku'], // Same SKU should be allowed for current product
            'description' => 'Updated description content',
            'price' => 299.95,
            'stock_quantity' => 50,
            'is_active' => 1,
        ];

        $result = $this->productService->updateProduct($id, $updateData);
        $this->assert($result['success'] === true, "Admin can update product details.");
        $this->assert((float)$result['product']['price'] === 299.95, "Updated product price matches.");
        $this->assert((int)$result['product']['stock_quantity'] === 50, "Updated stock quantity matches.");
    }

    private function testDeactivateProduct(): void
    {
        $product = $this->repository->findAll(false, null, 1, 1)[0];
        $id = (int)$product['id'];

        $success = $this->productService->deactivateProduct($id);
        $this->assert($success === true, "Product deactivated successfully.");

        $updated = $this->repository->findById($id);
        $this->assert((int)$updated['is_active'] === 0, "Product is_active flag is 0.");
    }

    private function testCustomerCatalogOnlyActive(): void
    {
        $activeProducts = $this->repository->findAll(true);
        foreach ($activeProducts as $ap) {
            $this->assert((int)$ap['is_active'] === 1, "Customer catalog returns only active products.");
        }
    }
}
