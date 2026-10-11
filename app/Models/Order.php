<?php

namespace App\Models;

/**
 * Class Order
 * 
 * Domain model representing a customer order header.
 * Encapsulates order identifier, user ownership, status, total amount, and timestamps.
 */
class Order
{
    public ?int $id = null;
    public string $orderNumber = '';
    public int $userId = 0;
    public string $status = 'pending';
    public float $totalAmount = 0.0;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;
    public ?string $cancelledAt = null;

    /**
     * Instantiate an Order object from a database associative array.
     *
     * @param array $data Database row data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $order = new self();
        $order->id = isset($data['id']) ? (int)$data['id'] : null;
        $order->orderNumber = $data['order_number'] ?? '';
        $order->userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
        $order->status = $data['status'] ?? 'pending';
        $order->totalAmount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0.0;
        $order->createdAt = $data['created_at'] ?? null;
        $order->updatedAt = $data['updated_at'] ?? null;
        $order->cancelledAt = $data['cancelled_at'] ?? null;

        return $order;
    }

    /**
     * Convert Order instance into an associative array for persistence or JSON response.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'user_id' => $this->userId,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'cancelled_at' => $this->cancelledAt,
        ];
    }
}
