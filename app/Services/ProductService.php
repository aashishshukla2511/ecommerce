<?php

namespace App\Services;

use App\Core\HttpException;
use App\Core\Validator;
use App\Repositories\ProductRepository;

class ProductService
{
    private ProductRepository $repository;

    public function __construct(?ProductRepository $repository = null)
    {
        $this->repository = $repository ?? new ProductRepository();
    }

    public function listProducts(bool $activeOnly = false, ?string $search = null, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $products = $this->repository->findAll($activeOnly, $search, $page, $perPage);
        $total = $this->repository->countAll($activeOnly, $search);
        $totalPages = (int)ceil($total / $perPage);

        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, $totalPages),
            'search' => $search ?? '',
        ];
    }

    public function getProduct(int $id, bool $activeOnly = false): array
    {
        if ($id <= 0) {
            throw new HttpException(404, "Product not found.");
        }

        $product = $this->repository->findById($id, $activeOnly);
        if (!$product) {
            throw new HttpException(404, "Product with ID {$id} was not found.");
        }

        return $product;
    }

    public function createProduct(array $data): array
    {
        $validator = Validator::make($data);
        $isValid = $validator->validate([
            'name' => 'required|string|max_len:180',
            'sku' => 'required|string|max_len:80',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $sku = strtoupper(trim($data['sku'] ?? ''));
        if ($sku !== '' && $this->repository->findBySku($sku)) {
            $validator->addError('sku', "The SKU '{$sku}' is already taken by another product.");
            $isValid = false;
        }

        if (!$isValid) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }

        $productId = $this->repository->create($data);
        $product = $this->repository->findById($productId);

        return [
            'success' => true,
            'product' => $product,
        ];
    }

    public function updateProduct(int $id, array $data): array
    {
        $existing = $this->getProduct($id);

        $validator = Validator::make($data);
        $isValid = $validator->validate([
            'name' => 'required|string|max_len:180',
            'sku' => 'required|string|max_len:80',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $sku = strtoupper(trim($data['sku'] ?? ''));
        if ($sku !== '' && $this->repository->findBySku($sku, $id)) {
            $validator->addError('sku', "The SKU '{$sku}' is already taken by another product.");
            $isValid = false;
        }

        if (!$isValid) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }

        $this->repository->update($id, $data);
        $updatedProduct = $this->repository->findById($id);

        return [
            'success' => true,
            'product' => $updatedProduct,
        ];
    }

    public function deactivateProduct(int $id): bool
    {
        $this->getProduct($id); // Check existence
        return $this->repository->deactivate($id);
    }
}
