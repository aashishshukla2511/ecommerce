<?php

use App\Controllers\AdminOrderController;
use App\Controllers\AuthController;
use App\Controllers\CustomerOrderController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

/** @var \App\Core\Router $router */

// Home Route
$router->get('/', [HomeController::class, 'index']);

// Authentication Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Customer Account Profile Route (Protected)
$router->get('/account', [AuthController::class, 'account'], [AuthMiddleware::class]);

// Customer Order Routes (Protected by AuthMiddleware)
$router->get('/my-orders', [CustomerOrderController::class, 'myOrders'], [AuthMiddleware::class]);
$router->get('/orders/create', [CustomerOrderController::class, 'create'], [AuthMiddleware::class]);
$router->post('/orders', [CustomerOrderController::class, 'store'], [AuthMiddleware::class]);
$router->get('/orders/{id}', [CustomerOrderController::class, 'show'], [AuthMiddleware::class]);
$router->post('/orders/{id}/cancel', [CustomerOrderController::class, 'cancel'], [AuthMiddleware::class]);

// Public Customer Product Catalog Routes
$router->get('/products', [ProductController::class, 'customerIndex']);
$router->get('/products/{id}', [ProductController::class, 'customerShow']);

// Protected Admin Management Routes (Protected by AuthMiddleware & RoleMiddleware('admin'))
$adminMiddleware = [AuthMiddleware::class, RoleMiddleware::class];

// Admin Product Management
$router->get('/admin/products', [ProductController::class, 'adminIndex'], $adminMiddleware);
$router->get('/admin/products/create', [ProductController::class, 'adminCreate'], $adminMiddleware);
$router->post('/admin/products', [ProductController::class, 'adminStore'], $adminMiddleware);
$router->get('/admin/products/{id}', [ProductController::class, 'adminShow'], $adminMiddleware);
$router->get('/admin/products/{id}/edit', [ProductController::class, 'adminEdit'], $adminMiddleware);
$router->post('/admin/products/{id}/update', [ProductController::class, 'adminUpdate'], $adminMiddleware);
$router->post('/admin/products/{id}/deactivate', [ProductController::class, 'adminDeactivate'], $adminMiddleware);

// Admin Order Management
$router->get('/admin/orders', [AdminOrderController::class, 'index'], $adminMiddleware);
$router->get('/admin/orders/{id}', [AdminOrderController::class, 'show'], $adminMiddleware);
$router->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'], $adminMiddleware);

