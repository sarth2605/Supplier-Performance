<?php
/**
 * SPAS Automated Test Suite
 * Validates endpoints, database integrity, role access, and HTTP responses
 */

define('BASE_TEST_URL', 'http://localhost/Supplier%20Performance/');

$results = [
    'passed' => 0,
    'failed' => 0,
    'tests'  => []
];

function record_test($category, $test_name, $status, $details = '') {
    global $results;
    if ($status) {
        $results['passed']++;
        echo "  [PASS] {$category} -> {$test_name}\n";
    } else {
        $results['failed']++;
        echo "  [FAIL] {$category} -> {$test_name} - Reason: {$details}\n";
    }
    $results['tests'][] = [
        'category' => $category,
        'name'     => $test_name,
        'passed'   => $status,
        'details'  => $details
    ];
}

function http_get($url, $cookie_file = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't auto follow to inspect status codes
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($cookie_file) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }
    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $body, 'redirect' => $redirect_url];
}

function http_post($url, $post_data, $cookie_file = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($cookie_file) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }
    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $body, 'redirect' => $redirect_url];
}

echo "========================================================\n";
echo "       SPAS AUTOMATED SYSTEM TEST SUITE                 \n";
echo "========================================================\n\n";

// 1. PUBLIC ENDPOINTS & REDIRECTS TEST
echo "1. Testing Public Endpoints & Unified Auth Architecture...\n";
$public_endpoints = [
    'Root Entry Point'        => [BASE_TEST_URL, 200],
    'Portal Selector'         => [BASE_TEST_URL . 'auth/portal_select.php', 200],
    'Unified Common Login'    => [BASE_TEST_URL . 'auth/login.php', 200],
    'Unified Common Register' => [BASE_TEST_URL . 'auth/register.php', 200],
    'Admin Login Redirect'    => [BASE_TEST_URL . 'auth/admin_login.php', 302],
    'Manufacturer Login Redir'=> [BASE_TEST_URL . 'auth/manufacturer_login.php', 302],
    'Supplier Login Redir'    => [BASE_TEST_URL . 'auth/supplier_login.php', 302],
    'Shopkeeper Login Redir'  => [BASE_TEST_URL . 'auth/shopkeeper_login.php', 302],
    'Admin Reg Restriction'   => [BASE_TEST_URL . 'auth/admin_register.php', 302],
    'Manufacturer Reg Redir'  => [BASE_TEST_URL . 'auth/manufacturer_register.php', 302],
    'Supplier Reg Redir'      => [BASE_TEST_URL . 'auth/supplier_register.php', 302],
    'Shopkeeper Reg Redir'    => [BASE_TEST_URL . 'auth/shopkeeper_register.php', 302]
];

foreach ($public_endpoints as $name => [$url, $expected_code]) {
    $res = http_get($url);
    $ok = ($res['code'] === $expected_code);
    record_test('Public Endpoints', $name, $ok, "Expected HTTP {$expected_code}, Got {$res['code']}");
}

// 1b. Validate Home Page Content & Structure
echo "\n1b. Testing Home Page Components & Content...\n";
$home_res = http_get(BASE_TEST_URL . 'index.php');
$home_sections = [
    'Hero Section'            => 'hero-section',
    'Dynamic Metrics Ribbon'  => 'stats-ribbon',
    '4 Stakeholder Portals'   => 'Dedicated Stakeholder Portals',
    'Evaluation 4 Pillars'    => '4-Pillar Evaluation Framework',
    'Interactive Calculator'  => 'Interactive Score & Grade Simulator',
    'Enterprise Features'     => 'Enterprise Features & System Capabilities',
    'Supply Chain Pipeline'   => 'The 6-Stage Supply Chain Pipeline',
    'Academic Specs Ch 1-8'   => 'Academic Dissertation & Documentation',
    'Evaluator Test Accounts' => 'Quick Evaluator Test Accounts',
    'FAQ Accordion'           => 'faqAccordion'
];

foreach ($home_sections as $sec_name => $needle) {
    $found = (strpos($home_res['body'], $needle) !== false);
    record_test('Home Page Sections', $sec_name, $found, $found ? 'Present' : 'Missing snippet');
}

// 2. UNAUTHENTICATED PROTECTION TEST (RBAC)
echo "\n2. Testing Protected Page Access (Unauthenticated)...\n";
$protected_endpoints = [
    'Executive Dashboard' => BASE_TEST_URL . 'dashboard/index.php',
    'Reports Studio'      => BASE_TEST_URL . 'reports/index.php',
    'Suppliers Directory' => BASE_TEST_URL . 'suppliers/index.php',
    'Manufacturer Panel'  => BASE_TEST_URL . 'manufacturer/dashboard.php',
    'Supplier Portal'     => BASE_TEST_URL . 'supplier_portal/dashboard.php',
    'Shopkeeper Portal'   => BASE_TEST_URL . 'shopkeeper/dashboard.php',
    'User Administration' => BASE_TEST_URL . 'users/index.php'
];

foreach ($protected_endpoints as $name => $url) {
    $res = http_get($url);
    // Must redirect to portal select or login (302) or return 403
    $ok = ($res['code'] === 302 || $res['code'] === 403);
    record_test('Security / RBAC', "Block Guest from {$name}", $ok, "HTTP {$res['code']}");
}

// 3. DATABASE INTEGRITY TESTS
echo "\n3. Testing Database Relational Integrity...\n";
require_once __DIR__ . '/../config/database.php';
$db = get_db();

try {
    $supplier_count = (int)$db->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    record_test('Database', 'Suppliers Table Populated', $supplier_count > 0, "Count: {$supplier_count}");

    $products_count = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    record_test('Database', 'Products Table Populated', $products_count > 0, "Count: {$products_count}");

    $po_count = (int)$db->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
    record_test('Database', 'Purchase Orders Table Populated', $po_count > 0, "Count: {$po_count}");

    $qc_count = (int)$db->query("SELECT COUNT(*) FROM quality_inspections")->fetchColumn();
    record_test('Database', 'Quality Inspections Table Populated', $qc_count > 0, "Count: {$qc_count}");

    $scores_count = (int)$db->query("SELECT COUNT(*) FROM performance_scores")->fetchColumn();
    record_test('Database', 'Performance Scores Table Populated', $scores_count > 0, "Count: {$scores_count}");

    $transfers_count = (int)$db->query("SELECT COUNT(*) FROM product_transfers")->fetchColumn();
    record_test('Database', 'Product Transfers Table Populated', $transfers_count > 0, "Count: {$transfers_count}");

    $inv_count = (int)$db->query("SELECT COUNT(*) FROM user_inventory")->fetchColumn();
    record_test('Database', 'Inventory Balances Table Populated', $inv_count > 0, "Count: {$inv_count}");
} catch (Exception $e) {
    record_test('Database', 'Database Integrity Queries', false, $e->getMessage());
}

// 4. REPORTS STUDIO QUERY VALIDATION
echo "\n4. Testing Reports Studio Analytical SQL Queries...\n";
$report_queries = [
    'Summary Metrics' => "SELECT COUNT(*) as total_suppliers, AVG(overall_score) as avg_score FROM performance_scores",
    'Top Ranked Suppliers' => "SELECT s.supplier_name, p.overall_score, p.grade FROM performance_scores p JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.overall_score DESC LIMIT 5",
    'Delivery Performance' => "SELECT delivery_status, COUNT(*) as cnt FROM deliveries GROUP BY delivery_status",
    'Defect Inspections' => "SELECT AVG(defect_rate) as avg_defect, AVG(quality_score) as avg_quality FROM quality_inspections",
    'Transfer Ledger' => "SELECT stage, status, COUNT(*) as cnt FROM product_transfers GROUP BY stage, status"
];

foreach ($report_queries as $name => $sql) {
    try {
        $stmt = $db->query($sql);
        $data = $stmt->fetchAll();
        record_test('Reports Studio SQL', $name, is_array($data), "Fetched " . count($data) . " rows");
    } catch (Exception $e) {
        record_test('Reports Studio SQL', $name, false, $e->getMessage());
    }
}

// 5. AUTHENTICATION & LOGIN SIMULATION
echo "\n5. Testing Role-Based Authentication Simulation...\n";
// Fetch one user of each role from database to test login
$roles_to_test = ['admin', 'manufacturer', 'supplier', 'shopkeeper'];
foreach ($roles_to_test as $role) {
    $stmt = $db->prepare("SELECT email FROM users WHERE role = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$role]);
    $user = $stmt->fetch();
    if ($user) {
        record_test('Auth System', "Active {$role} Account Exists", true, "Email: {$user['email']}");
    } else {
        record_test('Auth System', "Active {$role} Account Exists", false, "No active {$role} in users table");
    }
}

echo "\n========================================================\n";
echo "TEST RESULTS SUMMARY: {$results['passed']} PASSED, {$results['failed']} FAILED\n";
echo "========================================================\n";
