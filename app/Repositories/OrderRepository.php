<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Class OrderRepository
 * 
 * Handles all direct PDO SQL execution for orders and order_items tables,
 * including creating order headers, persisting itemized product snapshots,
 * and fetching order history by ID and customer ownership.
 */
class OrderRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Generate a unique human-readable order number string.
     * Format: ORD-YYYYMMDD-XXXX (e.g. ORD-20261009-A8F2)
     *
     * @return string
     */
    public function generateOrderNumber(): string
    {
        $datePrefix = date('Ymd');
        do {
            $randomSuffix = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 4));
            $orderNumber = "ORD-{$datePrefix}-{$randomSuffix}";

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE order_number = :num");
            $stmt->execute(['num' => $orderNumber]);
            $exists = (int)$stmt->fetchColumn() > 0;
        } while ($exists);

        return $orderNumber;
    }

    /**
     * Insert a new order header record into the database.
     *
     * @param int $userId Customer ID owning the order.
     * @param string $orderNumber Generated unique order number string.
     * @param float $totalAmount Calculated order total amount.
     * @return int Inserted order primary key ID.
     */
    public function createOrder(int $userId, string $orderNumber, float $totalAmount): int
    {
        $sql = "INSERT INTO orders (order_number, user_id, status, total_amount, created_at, updated_at) 
                VALUES (:order_number, :user_id, 'pending', :total_amount, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_number', $orderNumber, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':total_amount', $totalAmount);

        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Insert a snapshot row for an order line item into order_items table.
     * Stores historical snapshot values for product_name, sku, unit_price, and line_total.
     *
     * @param int $orderId Order foreign key ID.
     * @param int $productId Product foreign key ID.
     * @param string $productName Product name snapshot at purchase.
     * @param string $sku Product SKU snapshot at purchase.
     * @param int $quantity Ordered quantity.
     * @param float $unitPrice Product unit price snapshot.
     * @param float $lineTotal Line total amount (quantity * unitPrice).
     * @return bool True if inserted successfully.
     */
    public function createOrderItem(int $orderId, int $productId, string $productName, string $sku, int $quantity, float $unitPrice, float $lineTotal): bool
    {
        $sql = "INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, unit_price, line_total, created_at, updated_at) 
                VALUES (:order_id, :product_id, :product_name, :sku, :quantity, :unit_price, :line_total, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':product_name', $productName, PDO::PARAM_STR);
        $stmt->bindValue(':sku', $sku, PDO::PARAM_STR);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':unit_price', $unitPrice);
        $stmt->bindValue(':line_total', $lineTotal);

        return $stmt->execute();
    }

    /**
     * Find order header by ID with optional customer ownership restriction.
     *
     * @param int $orderId Target order ID.
     * @param int|null $userId Optional customer user ID filter.
     * @return array|null Order record array or null if not found/unauthorized.
     */
    public function findById(int $orderId, ?int $userId = null): ?array
    {
        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = :id";
        $params = ['id' => $orderId];

        if ($userId !== null) {
            $sql .= " AND o.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        return $order ?: null;
    }

    /**
     * Retrieve paginated order history records belonging strictly to the specified customer user ID.
     * Enforces user ownership directly in the database query.
     *
     * @param int $userId Authenticated customer user ID.
     * @param int $page Current page number.
     * @param int $perPage Items per page.
     * @return array List of customer's order records.
     */
    public function findUserOrders(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM orders 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC, id DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total orders owned by a specific customer for pagination.
     *
     * @param int $userId Customer user ID.
     * @return int Total order count.
     */
    public function countUserOrders(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM orders WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Retrieve all order item line snapshots for a specific order.
     *
     * @param int $orderId Target order ID.
     * @return array List of order item arrays.
     */
    public function findItemsByOrderId(int $orderId): array
    {
        $sql = "SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve paginated list of all customer orders for Admin with dynamic filtering.
     * Supports status allowlist filter, search query (ID / order number / customer name / email),
     * and start/end date range filtering.
     *
     * @param string|null $status Status filter ('pending', 'confirmed', 'shipped', 'completed', 'cancelled').
     * @param string|null $search Search query for order number, ID, or customer details.
     * @param string|null $fromDate Start date string (YYYY-MM-DD).
     * @param string|null $toDate End date string (YYYY-MM-DD).
     * @param int $page Current page number.
     * @param int $perPage Records per page.
     * @return array List of matching order records with customer details JOINed.
     */
    public function findAllOrders(?string $status = null, ?string $search = null, ?string $fromDate = null, ?string $toDate = null, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        // 1. Status allowlist filter
        $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        if ($status !== null && in_array(strtolower($status), $validStatuses, true)) {
            $where[] = "o.status = :status";
            $params['status'] = strtolower($status);
        }

        // 2. Search filter (Order ID, Order Number, Customer Name or Email)
        if ($search !== null && trim($search) !== '') {
            $searchTerm = '%' . trim($search) . '%';
            if (is_numeric(trim($search))) {
                $where[] = "(o.id = :search_id OR o.order_number LIKE :st1 OR u.name LIKE :st2 OR u.email LIKE :st3)";
                $params['search_id'] = (int)trim($search);
            } else {
                $where[] = "(o.order_number LIKE :st1 OR u.name LIKE :st2 OR u.email LIKE :st3)";
            }
            $params['st1'] = $searchTerm;
            $params['st2'] = $searchTerm;
            $params['st3'] = $searchTerm;
        }

        // 3. Date range filters
        if ($fromDate !== null && trim($fromDate) !== '') {
            $where[] = "o.created_at >= :from_date";
            $params['from_date'] = trim($fromDate) . ' 00:00:00';
        }

        if ($toDate !== null && trim($toDate) !== '') {
            $where[] = "o.created_at <= :to_date";
            $params['to_date'] = trim($toDate) . ' 23:59:59';
        }

        $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY o.created_at DESC, o.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total matching orders for Admin pagination calculation.
     */
    public function countAllOrders(?string $status = null, ?string $search = null, ?string $fromDate = null, ?string $toDate = null): int
    {
        $where = [];
        $params = [];

        $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        if ($status !== null && in_array(strtolower($status), $validStatuses, true)) {
            $where[] = "o.status = :status";
            $params['status'] = strtolower($status);
        }

        if ($search !== null && trim($search) !== '') {
            $searchTerm = '%' . trim($search) . '%';
            if (is_numeric(trim($search))) {
                $where[] = "(o.id = :search_id OR o.order_number LIKE :st1 OR u.name LIKE :st2 OR u.email LIKE :st3)";
                $params['search_id'] = (int)trim($search);
            } else {
                $where[] = "(o.order_number LIKE :st1 OR u.name LIKE :st2 OR u.email LIKE :st3)";
            }
            $params['st1'] = $searchTerm;
            $params['st2'] = $searchTerm;
            $params['st3'] = $searchTerm;
        }

        if ($fromDate !== null && trim($fromDate) !== '') {
            $where[] = "o.created_at >= :from_date";
            $params['from_date'] = trim($fromDate) . ' 00:00:00';
        }

        if ($toDate !== null && trim($toDate) !== '') {
            $where[] = "o.created_at <= :to_date";
            $params['to_date'] = trim($toDate) . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) 
                FROM orders o 
                JOIN users u ON o.user_id = u.id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Lock single order header row inside an active MySQL transaction using SELECT ... FOR UPDATE.
     *
     * @param int $orderId Target order ID.
     * @return array|null Order row array or null.
     */
    public function findForUpdate(int $orderId): ?array
    {
        $sql = "SELECT * FROM orders WHERE id = :id FOR UPDATE";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update order status (and timestamp cancelled_at if status is 'cancelled').
     *
     * @param int $orderId Target order ID.
     * @param string $status New status value.
     * @return bool True if updated successfully.
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        if ($status === 'cancelled') {
            $sql = "UPDATE orders SET status = :status, cancelled_at = NOW(), updated_at = NOW() WHERE id = :id";
        } else {
            $sql = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
