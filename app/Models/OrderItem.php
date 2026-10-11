<?php

namespace App\Models;

/**
 * Class OrderItem
 * 
 * Domain model representing an itemized product line within an order.
 * Stores historical snapshots of product name, SKU, unit price, and line total
 * at the time of order creation.
 */
class OrderItem
{
    public ?int $id = null;
    public int $orderId = 0;
    public int $productId = 0;
    public string $productName = '';
    public string $sku = '';
    public int $quantity = 0;
    public float $unitPrice = 0.0;
    public float $lineTotal = 0.0;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    /**
     * Instantiate an OrderItem object from database record.
     *
     * @param array $data Database row array.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $item = new self();
        $item->id = isset($data['id']) ? (int)$data['id'] : null;
        $item->orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
        $item->productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
        $item->productName = $data['product_name'] ?? '';
        $item->sku = $data['sku'] ?? '';
        $item->quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
        $item->unitPrice = isset($data['unit_price']) ? (float)$data['unit_price'] : 0.0;
        $item->lineTotal = isset($data['line_total']) ? (float)$data['line_total'] : 0.0;
        $item->createdAt = $data['created_at'] ?? null;
        $item->updatedAt = $data['updated_at'] ?? null;

        return $item;
    }

    /**
     * Convert OrderItem instance into array format.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
