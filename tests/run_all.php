<?php

/**
 * Unified Automated Test Suite Runner
 * 
 * Executes all feature test suites for client deliverables:
 * - Product Management
 * - Order Creation
 * - Inventory Validation
 * - Admin Order Filtering & Pagination
 * - Order Cancellation & Inventory Restoration
 * - Concurrent Stock Updates & Race Conditions
 * - Authentication & Role Access
 * - Customer Order History
 * 
 * Usage:
 *   php tests/run_all.php
 */

require_once __DIR__ . '/../config/database.php';

// Register autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "====================================================================\n";
echo "   PHP 8 CUSTOM MVC E-COMMERCE — AUTOMATED FEATURE TEST SUITES     \n";
echo "====================================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

$suites = [
    'Product Management' => \Tests\Feature\ProductManagementTest::class,
    'Order Creation & Pricing' => \Tests\Feature\OrderCreationTest::class,
    'Inventory Validation' => \Tests\Feature\InventoryValidationTest::class,
    'Admin Order Filtering & Search' => \Tests\Feature\AdminOrderFilteringTest::class,
    'Order Cancellation & Restoration' => \Tests\Feature\OrderCancellationTest::class,
    'Concurrent Stock Updates' => \Tests\Feature\ConcurrentStockUpdatesTest::class,
    'Authentication & Role Security' => \Tests\Feature\AuthenticationTest::class,
    'Customer Order History' => \Tests\Feature\CustomerOrderHistoryTest::class,
];

$passedSuites = 0;
$failedSuites = 0;
$startTime = microtime(true);
$results = [];

foreach ($suites as $title => $className) {
    echo ">>> Running Suite: {$title} ...\n";
    $suiteStart = microtime(true);
    
    try {
        if (!class_exists($className)) {
            throw new RuntimeException("Class {$className} not found.");
        }
        $instance = new $className();
        $instance->runAllTests();
        $suiteDuration = round((microtime(true) - $suiteStart) * 1000, 2);
        
        $results[$title] = ['status' => 'PASSED', 'duration' => "{$suiteDuration}ms"];
        $passedSuites++;
        echo "✓ {$title} PASSED ({$suiteDuration}ms)\n\n";
    } catch (\Throwable $e) {
        $suiteDuration = round((microtime(true) - $suiteStart) * 1000, 2);
        $results[$title] = ['status' => 'FAILED', 'duration' => "{$suiteDuration}ms", 'error' => $e->getMessage()];
        $failedSuites++;
        echo "✗ {$title} FAILED ({$suiteDuration}ms)\n";
        echo "  Error: " . $e->getMessage() . "\n\n";
    }
}

$totalDuration = round(microtime(true) - $startTime, 2);

echo "====================================================================\n";
echo "                       TEST EXECUTION SUMMARY                       \n";
echo "====================================================================\n";
foreach ($results as $title => $info) {
    $statusStr = $info['status'] === 'PASSED' ? "[ PASS ]" : "[ FAIL ]";
    printf("%-38s %-10s %s\n", $title, $statusStr, $info['duration']);
}
echo "--------------------------------------------------------------------\n";
echo "Total Suites: " . count($suites) . " | Passed: {$passedSuites} | Failed: {$failedSuites} | Total Time: {$totalDuration}s\n";
echo "====================================================================\n";

if ($failedSuites > 0) {
    exit(1);
}

exit(0);
