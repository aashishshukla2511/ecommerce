<?php

namespace App\Models;

class Product
{
    public ?int $id = null;
    public string $name = '';
    public string $sku = '';
    public ?string $description = null;
    public float $price = 0.0;
    public int $stockQuantity = 0;
    public bool $isActive = true;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $product = new self();
        $product->id = isset($data['id']) ? (int)$data['id'] : null;
        $product->name = $data['name'] ?? '';
        $product->sku = $data['sku'] ?? '';
        $product->description = $data['description'] ?? null;
        $product->price = isset($data['price']) ? (float)$data['price'] : 0.0;
        $product->stockQuantity = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
        $product->isActive = !empty($data['is_active']);
        $product->createdAt = $data['created_at'] ?? null;
        $product->updatedAt = $data['updated_at'] ?? null;

        return $product;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'stock_quantity' => $this->stockQuantity,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
