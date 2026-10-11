<?php

namespace App\Models;

class User
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $passwordHash = '';
    public string $role = 'customer';
    public bool $isActive = true;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $user = new self();
        $user->id = isset($data['id']) ? (int)$data['id'] : null;
        $user->name = $data['name'] ?? '';
        $user->email = strtolower(trim($data['email'] ?? ''));
        $user->passwordHash = $data['password_hash'] ?? '';
        $user->role = $data['role'] ?? 'customer';
        $user->isActive = !empty($data['is_active']);
        $user->createdAt = $data['created_at'] ?? null;
        $user->updatedAt = $data['updated_at'] ?? null;

        return $user;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
