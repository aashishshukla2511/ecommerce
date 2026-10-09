# PHP 8 Custom MVC - Admin Products Module

PHP MVC Ecommerce Application: Product, Order and Inventory Management.
A lightweight, framework-free custom PHP 8 MVC Web Application featuring a complete **Admin Product Management Module** built with OOP principles, PDO Database Abstraction, Repository Pattern, and Vanilla CSS.

---

## 🚀 Key Features

### 1. Core Architecture (Phase 0)
- **Front Controller Router**: Clean URL routing (`/admin/products`, `/admin/products/{id}`, etc.) via Regex matching.
- **Dependency Injection & PDO Abstraction**: Centralized database connection management with prepared statement bindings.
- **Repository Pattern**: Strict separation of direct SQL data access (`ProductRepository`) from domain business logic (`ProductService`).
- **Input Validation**: `Validator` service for server-side rule evaluation.
- **Flash Messages & Response Helper**: Session flash alerts and HTML view rendering layout engine.
- **Vanilla CSS Layouts**: Dark/Light mode theme system with split sidebar/content admin interface.

### 2. Admin Products Module (Phase 1)
- **Product Listing & Pagination**: View all products with active/inactive filtering and search.
- **Product Details View**: Comprehensive item inspection panel.
- **Create Product**: Add products with server-side SKU uniqueness checks, positive price/stock guards.
- **Edit & Update Product**: Modify product name, SKU, price, description, and stock quantity.
- **Soft Deactivation**: Deactivate products safely without losing transaction history.

---

## 🛠️ Setup & Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/aashishshukla2511/ecommerce.git
   cd ecommerce
   ```

2. **Database Configuration**:
   - Create a MySQL database named `ecommerce`.
   - Import `database/schema.sql`.
   - Update database credentials in `config/database.php`.

3. **Start Local Server**:
   ```bash
   php -S localhost:8000 -t public
   ```

4. **Access Admin Products Module**:
   - Open browser to `http://localhost:8000/admin/products`

---

## 🧪 Automated Testing
Run the automated feature test suite for the Products module:
```bash
php scratch/run_product_test.php
```
