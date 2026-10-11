# PHP 8 Custom MVC — E-Commerce & Inventory Management System

A lightweight, high-performance, framework-free custom **PHP 8 MVC Web Application** implementing Product Management, Customer Orders, Inventory Validation, Concurrency Row-Locking, Admin Filtering/Pagination, and Atomic Order Cancellation with Stock Restoration.

Built entirely with modern OOP principles, PDO Database Abstraction, Repository Pattern, CSRF Protection, and Vanilla CSS (no external CSS/JS or heavy PHP frameworks).

---

## 📋 Client Deliverables Overview

| Deliverable Required | Implementation Location | Description |
| :--- | :--- | :--- |
| **Complete PHP source code** | `app/`, `public/`, `views/`, `routes/`, `config/` | Complete custom MVC architecture, controllers, services, repositories, middleware, and responsive views. |
| **MySQL database schema or migration files** | `database/schema.sql` & `database/seeders/` | Full relational schema (`users`, `products`, `orders`, `order_items`) with check constraints, foreign keys, and seeder. |
| **README with installation and execution instructions** | `README.md` | Comprehensive setup, configuration, credentials, and execution guide. |
| **Automated Tests: Product Management** | `tests/Feature/ProductManagementTest.php` | Product CRUD, unique SKU validation, negative price/stock guards, soft-deactivation. |
| **Automated Tests: Order Creation** | `tests/Feature/OrderCreationTest.php` | Multi-product order creation, server-side price enforcement, line-item snapshotting. |
| **Automated Tests: Inventory Validation** | `tests/Feature/InventoryValidationTest.php` | Insufficient stock rejection, zero/negative quantity guards, out-of-stock and inactive item validation. |
| **Automated Tests: Order Filtering** | `tests/Feature/AdminOrderFilteringTest.php` | Dynamic status filters, multi-field search (ID, order #, customer), date range filtering, numeric pagination. |
| **Automated Tests: Cancellation & Restoration** | `tests/Feature/OrderCancellationTest.php` | Customer & admin cancellation, atomic inventory restoration, idempotency / duplicate cancel rejection. |
| **Automated Tests: Concurrent Stock Updates** | `tests/Feature/ConcurrentStockUpdatesTest.php` | Race condition simulation, competing buyer row locks (`FOR UPDATE`), prevention of overselling, deadlock avoidance. |

---

## 🛠️ System Requirements

- **PHP**: `8.0` or higher (`8.1+` recommended)
  - Extensions required: `pdo`, `pdo_mysql`, `mbstring`, `session`
- **MySQL / MariaDB**: MySQL `5.7+` / `8.0+` or MariaDB `10.3+` (InnoDB engine required)
- **Web Server**: Apache / Nginx or PHP Built-in Server (`php -S`)
- **Composer**: Used strictly for standard PSR-4 autoloading (`vendor/autoload.php`) and `.env` parsing (`vlucas/phpdotenv`).

---

## 🚀 Setup & Installation Guide

### 1. Clone or Extract the Project
```bash
git clone https://github.com/aashishshukla2511/ecommerce.git
cd ecommerce
```

### 2. Configure Environment
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Ensure your database credentials in `.env` match your local environment:
```ini
APP_NAME="Ecommerce MVC"
APP_ENV=development
APP_DEBUG=true
APP_BASE_URL="http://localhost:8000"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ecommerce
DB_USER=root
DB_PASS=
```

### 3. Database Setup
1. Create a MySQL database named `ecommerce`:
   ```sql
   CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the database schema from `database/schema.sql`:
   ```bash
   mysql -u root -p ecommerce < database/schema.sql
   ```
   *(Alternatively, import `database/schema.sql` via phpMyAdmin or MySQL Workbench).*

### 4. Install Dependencies (Autoloader)
```bash
composer install
```

### 5. Start the Local Server
From the root directory, launch the PHP development server pointing to the `public/` directory:
```bash
php -S localhost:8000 -t public
```
The application will be live at: **`http://localhost:8000`**

---

## 👥 Demo User Accounts

The database comes with pre-configured demo accounts:

| Role | Email | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@example.com` | `password123` | Full access to Admin Portal, Product Management, Orders Listing, Status Changes, and Cancellation |
| **Customer 1** | `customer@example.com` | `password123` | Place orders, view order history (`/my-orders`), view order details, cancel own orders |
| **Customer 2** | `john@example.com` | `password123` | Independent customer account for multi-user and ownership isolation testing |

---

## 🧪 Running Automated Feature Tests

All tests can be executed with a single command via the **Unified Test Runner**:

```bash
php tests/run_all.php
```

### Test Suites Included:
1. **`tests/Feature/ProductManagementTest.php`**: Validates creation, updates, SKU uniqueness, stock validation, and soft-deactivation.
2. **`tests/Feature/OrderCreationTest.php`**: Validates atomic transaction, line-item price calculation, server-side pricing immunity against tampering, and inventory reduction.
3. **`tests/Feature/InventoryValidationTest.php`**: Validates stock guards, out-of-stock rejection, inactive item protection, and atomic rollback on multi-item stock shortage.
4. **`tests/Feature/AdminOrderFilteringTest.php`**: Validates multi-status allowlist filtering, date boundary filtering, search queries across names/emails/order numbers, and pagination calculations.
5. **`tests/Feature/OrderCancellationTest.php`**: Validates atomic stock restoration, status transitions to `cancelled`, and duplicate cancellation rejection.
6. **`tests/Feature/ConcurrentStockUpdatesTest.php`**: Validates competing purchase simulations under concurrency, deadlock-free deterministic ascending row-locking, and zero-oversell guarantees.
7. **`tests/Feature/AuthenticationTest.php`**: Validates secure login, password hashing (`password_hash`), session management, and `RoleMiddleware` 403 Forbidden enforcement.
8. **`tests/Feature/CustomerOrderHistoryTest.php`**: Validates customer-specific order list isolation, unauthorized access blocking, and historical item snapshot immutability.

---

## 🏛️ Architecture & Core Concepts

- **MVC Pattern**: Strict separation between Controllers (`app/Controllers`), Services (`app/Services`), Repositories (`app/Repositories`), and Views (`views/`).
- **Data Integrity & Concurrency**:
  - Uses InnoDB transactions with explicit `SELECT ... FOR UPDATE` row locks.
  - Product IDs are sorted in ascending order before locking to eliminate circular lock dependencies (deadlocks).
  - Uses guarded atomic updates: `UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id AND stock_quantity >= :qty`.
- **Security**:
  - **CSRF Protection**: Token validation on all state-altering `POST` requests.
  - **SQL Injection Prevention**: All queries use PDO parameterized prepared statements.
  - **XSS Prevention**: Context-aware output escaping via `Url::escape()`.
  - **Role-Based Access Control**: `AuthMiddleware` and `RoleMiddleware` guarding admin and customer routes.
