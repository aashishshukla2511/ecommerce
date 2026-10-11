<?php

namespace Database\Seeders;

use App\Core\Database;
use PDO;

class DemoSeeder
{
    public static function run(): void
    {
        $db = Database::getConnection();

        // 1. Seed Admin Account
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => 'admin@example.com']);
        if (!$stmt->fetch()) {
            $insertUser = $db->prepare("INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at) VALUES (:name, :email, :pass, 'admin', 1, NOW(), NOW())");
            $insertUser->execute([
                'name' => 'System Admin',
                'email' => 'admin@example.com',
                'pass' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            echo "Seeded Admin: admin@example.com / password123\n";
        }

        // 2. Seed Customer Account 1
        $stmt->execute(['email' => 'customer@example.com']);
        if (!$stmt->fetch()) {
            $insertCustomer = $db->prepare("INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at) VALUES (:name, :email, :pass, 'customer', 1, NOW(), NOW())");
            $insertCustomer->execute([
                'name' => 'Alice Smith',
                'email' => 'customer@example.com',
                'pass' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            echo "Seeded Customer 1: customer@example.com / password123\n";
        }

        // 3. Seed Customer Account 2
        $stmt->execute(['email' => 'john@example.com']);
        if (!$stmt->fetch()) {
            $insertCustomer = $db->prepare("INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at) VALUES (:name, :email, :pass, 'customer', 1, NOW(), NOW())");
            $insertCustomer->execute([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'pass' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            echo "Seeded Customer 2: john@example.com / password123\n";
        }

        // 4. Seed Inactive Customer Account (for testing inactive account login restriction)
        $stmt->execute(['email' => 'inactive@example.com']);
        if (!$stmt->fetch()) {
            $insertInactive = $db->prepare("INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at) VALUES (:name, :email, :pass, 'customer', 0, NOW(), NOW())");
            $insertInactive->execute([
                'name' => 'Inactive User',
                'email' => 'inactive@example.com',
                'pass' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            echo "Seeded Inactive Customer: inactive@example.com / password123\n";
        }

        // 5. Seed Sample Products if empty or if available active stock is low
        $countStmt = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity > 0");
        if ((int)$countStmt->fetchColumn() < 3) {
            $db->exec("UPDATE products SET is_active = 1, stock_quantity = 25 WHERE stock_quantity = 0 OR is_active = 0");
            
            $checkTotal = $db->query("SELECT COUNT(*) FROM products");
            if ((int)$checkTotal->fetchColumn() === 0) {
                $insertProduct = $db->prepare("INSERT INTO products (name, sku, description, price, stock_quantity, is_active, created_at, updated_at) VALUES (:name, :sku, :description, :price, :stock_quantity, 1, NOW(), NOW())");
                
                $sampleProducts = [
                    [
                        'name' => 'Wireless Headphones',
                        'sku' => 'SKU-AUDIO-001',
                        'description' => 'Premium noise-canceling over-ear wireless headphones with 30-hour battery life.',
                        'price' => 129.99,
                        'stock_quantity' => 25,
                    ],
                    [
                        'name' => 'Mechanical Gaming Keyboard',
                        'sku' => 'SKU-TECH-002',
                        'description' => 'RGB tactile mechanical switches with detachable USB-C braided cable.',
                        'price' => 89.50,
                        'stock_quantity' => 12,
                    ],
                    [
                        'name' => 'UltraWide Monitor 34"',
                        'sku' => 'SKU-DISP-003',
                        'description' => 'Curved 144Hz WQHD display with HDR400 and USB hub.',
                        'price' => 499.00,
                        'stock_quantity' => 5,
                    ],
                    [
                        'name' => 'Ergonomic Desk Chair',
                        'sku' => 'SKU-FURN-004',
                        'description' => 'Breathable mesh back with adjustable lumbar support and 3D armrests.',
                        'price' => 249.99,
                        'stock_quantity' => 8,
                    ],
                    [
                        'name' => 'USB-C Docking Station',
                        'sku' => 'SKU-ACC-005',
                        'description' => '11-in-1 USB-C hub with dual 4K HDMI, Gigabit Ethernet, and 100W Power Delivery.',
                        'price' => 65.00,
                        'stock_quantity' => 10,
                    ],
                ];

                foreach ($sampleProducts as $prod) {
                    $insertProduct->execute($prod);
                }
            }
        }
    }
}
