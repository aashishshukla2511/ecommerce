<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Class ProductRepository
 * 
 * Handles all direct PDO database queries for products table including
 * CRUD operations, SKU uniqueness validation queries, transaction row locks,
 * and guarded stock quantity updates.
 */
class ProductRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Retrieve paginated list of products with optional search and active status filter.
     */
    public function findAll(bool $activeOnly = false, ?string $search = null, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if ($activeOnly) {
            $where[] = "is_active = 1";
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = "(name LIKE :search OR sku LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }

        $sql = "SELECT * FROM products";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total matching products for pagination calculation.
     */
    public function countAll(bool $activeOnly = false, ?string $search = null): int
    {
        $where = [];
        $params = [];

        if ($activeOnly) {
            $where[] = "is_active = 1";
        }

        if ($search !== null && trim($search) !== '') {
            $where[] = "(name LIKE :search OR sku LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }

        $sql = "SELECT COUNT(*) FROM products";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Find single product by primary key ID.
     */
    public function findById(int $id, bool $activeOnly = false): ?array
    {
        $sql = "SELECT * FROM products WHERE id = :id";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find single product by unique SKU (with optional ID exclusion for updates).
     */
    public function findBySku(string $sku, ?int $excludeId = null): ?array
    {
        $sql = "SELECT * FROM products WHERE sku = :sku";
        $params = ['sku' => trim($sku)];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lock specific product rows inside an active MySQL InnoDB transaction using SELECT ... FOR UPDATE.
     * Product IDs must be sorted in ascending order prior to calling this method to eliminate deadlocks.
     *
     * @param array<int> $productIds List of product primary key IDs.
     * @return array Map of product ID => product row array.
     */
    public function findAndLockProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        // Generate positional placeholders e.g., ?, ?, ?
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT * FROM products WHERE id IN ({$placeholders}) AND is_active = 1 FOR UPDATE";

        $stmt = $this->db->prepare($sql);

        foreach ($productIds as $index => $id) {
            $stmt->bindValue($index + 1, (int)$id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int)$row['id']] = $row;
        }

        return $mapped;
    }

    /**
     * Atomically deduct stock quantity for a product with guard condition preventing negative stock.
     * Returns true only if 1 row was updated.
     *
     * @param int $productId Target product ID.
     * @param int $quantity Quantity to deduct.
     * @return bool True if stock update succeeded, false if stock was insufficient.
     */
    public function deductStock(int $productId, int $quantity): bool
    {
        $sql = "UPDATE products 
                SET stock_quantity = stock_quantity - :qty, 
                    updated_at = NOW() 
                WHERE id = :id AND stock_quantity >= :qty_check";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':qty', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':qty_check', $quantity, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    /**
     * Create new product record.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO products (name, sku, description, price, stock_quantity, is_active, created_at, updated_at) 
                VALUES (:name, :sku, :description, :price, :stock_quantity, :is_active, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', trim($data['name']), PDO::PARAM_STR);
        $stmt->bindValue(':sku', strtoupper(trim($data['sku'])), PDO::PARAM_STR);
        $stmt->bindValue(':description', isset($data['description']) && trim($data['description']) !== '' ? trim($data['description']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':price', (float)$data['price']);
        $stmt->bindValue(':stock_quantity', (int)$data['stock_quantity'], PDO::PARAM_INT);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);

        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update product record.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE products 
                SET name = :name, 
                    sku = :sku, 
                    description = :description, 
                    price = :price, 
                    stock_quantity = :stock_quantity, 
                    is_active = :is_active, 
                    updated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', trim($data['name']), PDO::PARAM_STR);
        $stmt->bindValue(':sku', strtoupper(trim($data['sku'])), PDO::PARAM_STR);
        $stmt->bindValue(':description', isset($data['description']) && trim($data['description']) !== '' ? trim($data['description']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':price', (float)$data['price']);
        $stmt->bindValue(':stock_quantity', (int)$data['stock_quantity'], PDO::PARAM_INT);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Soft-deactivate product record.
     */
    public function deactivate(int $id): bool
    {
        $sql = "UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Atomically restore stock quantity for a product when an order is cancelled.
     *
     * @param int $productId Target product ID.
     * @param int $quantity Quantity to add back to stock.
     * @return bool True if stock update succeeded.
     */
    public function restoreStock(int $productId, int $quantity): bool
    {
        $sql = "UPDATE products 
                SET stock_quantity = stock_quantity + :qty, 
                    updated_at = NOW() 
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':qty', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}

