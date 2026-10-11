<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuthService;
use Database\Seeders\DemoSeeder;
use PDO;

class AuthenticationTest
{
    private AuthService $authService;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->authService = new AuthService();
    }

    public function runAllTests(): void
    {
        echo "===============================================\n";
        echo "RUNNING PHASE 2 AUTOMATED TESTS (AUTHENTICATION)\n";
        echo "===============================================\n";

        DemoSeeder::run();

        $this->testAdminLogin();
        $this->testCustomer1Login();
        $this->testCustomer2Login();
        $this->testInvalidPasswordFails();
        $this->testNonExistentUserFails();
        $this->testInactiveAccountFails();
        $this->testLogoutClearsSession();
        $this->testCustomerCannotAccessAdminRoutes();

        echo "\nALL PHASE 2 AUTHENTICATION TESTS PASSED SUCCESSFULLY!\n";
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            echo "[FAIL] {$message}\n";
            exit(1);
        }
        echo "[PASS] {$message}\n";
    }

    private function testAdminLogin(): void
    {
        $result = $this->authService->authenticate('admin@example.com', 'password123');
        $this->assert($result['success'] === true, "Admin login succeeds with valid credentials.");
        $this->assert($result['user']['role'] === 'admin', "Authenticated user role is 'admin'.");
    }

    private function testCustomer1Login(): void
    {
        $result = $this->authService->authenticate('customer@example.com', 'password123');
        $this->assert($result['success'] === true, "Customer 1 (Alice Smith) login succeeds.");
        $this->assert($result['user']['role'] === 'customer', "Authenticated user role is 'customer'.");
    }

    private function testCustomer2Login(): void
    {
        $result = $this->authService->authenticate('john@example.com', 'password123');
        $this->assert($result['success'] === true, "Customer 2 (John Doe) login succeeds.");
        $this->assert($result['user']['role'] === 'customer', "Authenticated customer 2 role is 'customer'.");
    }

    private function testInvalidPasswordFails(): void
    {
        $result = $this->authService->authenticate('admin@example.com', 'wrongpassword');
        $this->assert($result['success'] === false, "Invalid password fails authentication.");
        $this->assert($result['message'] === 'Invalid email or password.', "Generic error message returned.");
    }

    private function testNonExistentUserFails(): void
    {
        $result = $this->authService->authenticate('nonexistent@example.com', 'password123');
        $this->assert($result['success'] === false, "Non-existent user fails authentication.");
        $this->assert($result['message'] === 'Invalid email or password.', "Generic error message returned for unknown user.");
    }

    private function testInactiveAccountFails(): void
    {
        $result = $this->authService->authenticate('inactive@example.com', 'password123');
        $this->assert($result['success'] === false, "Inactive account login is blocked.");
        $this->assert(str_contains($result['message'], 'inactive'), "Inactive account message returned.");
    }

    private function testLogoutClearsSession(): void
    {
        Auth::login([
            'id' => 10,
            'name' => 'Test Session User',
            'email' => 'testsession@example.com',
            'role' => 'customer',
        ]);
        $this->assert(Auth::check() === true, "User logged into session.");

        Auth::logout();
        $this->assert(Auth::check() === false, "Logout clears session user data.");
    }

    private function testCustomerCannotAccessAdminRoutes(): void
    {
        Auth::login([
            'id' => 2,
            'name' => 'Alice Smith',
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);

        $roleMw = new RoleMiddleware('admin');
        $exceptionCaught = false;

        try {
            $request = new \App\Core\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/products']);
            $roleMw->handle($request);
        } catch (HttpException $e) {
            $exceptionCaught = true;
            $this->assert($e->getStatusCode() === 403, "Customer access to admin route returns 403 Forbidden.");
        }

        $this->assert($exceptionCaught === true, "RoleMiddleware blocked customer from accessing admin route.");
        Auth::logout();
    }
}
