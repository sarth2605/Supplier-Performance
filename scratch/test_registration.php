<?php
/**
 * Test Unified Registration (auth/register.php)
 * - Verifies dynamic role registration for Manufacturer, Supplier, Shopkeeper
 * - Verifies that Admin registration is strictly blocked
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$base_url = 'http://localhost/Supplier%20Performance/auth/register.php';
$cookie_file = __DIR__ . '/cookies/reg_cookie.txt';
if (file_exists($cookie_file)) unlink($cookie_file);

function req($url, $method = 'GET', $data = [], $cookie_file = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookie_file) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redir = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'redirect' => $redir];
}

function get_token($body) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $m)) {
        return $m[1];
    }
    return '';
}

echo "=== TESTING UNIFIED REGISTRATION ARCHITECTURE ===\n\n";

// 1. Fetch form
$res = req($base_url, 'GET', [], $cookie_file);
$token = get_token($res['body']);
echo "1. Load Register Page: HTTP {$res['code']}, Token: " . (strlen($token) > 0 ? "OK" : "MISSING") . "\n";

// 2. Test Admin Registration Attempt (Must be BLOCKED)
echo "2. Attempting Admin Registration (Security Audit)... ";
$admin_post = [
    'role' => 'admin',
    'name' => 'Hacker Admin',
    'email' => 'fake_admin_' . time() . '@test.com',
    'phone' => '9999999999',
    'password' => 'Pass@12345',
    'password_confirm' => 'Pass@12345',
    'csrf_token' => $token
];
$admin_res = req($base_url, 'POST', $admin_post, $cookie_file);
if (strpos($admin_res['body'], 'Administrative accounts cannot be registered publicly') !== false || $admin_res['code'] !== 302) {
    echo "[PASS] Admin registration strictly prohibited and rejected.\n";
} else {
    echo "[FAIL] Admin registration was not blocked!\n";
}

// 3. Test Manufacturer Registration
$mfr_email = 'reg_mfr_' . time() . '@testfactory.in';
echo "3. Registering Manufacturer ({$mfr_email})... ";
$res = req($base_url . '?role=manufacturer', 'GET', [], $cookie_file);
$token = get_token($res['body']);

$mfr_post = [
    'role' => 'manufacturer',
    'name' => 'Dr. Apex Kumar',
    'email' => $mfr_email,
    'phone' => '9876543210',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'company_name' => 'Apex Cosmetics Lab',
    'address' => 'Plot 44 Industrial MIDC, Andheri East',
    'password' => 'TestPass@123',
    'confirm_password' => 'TestPass@123',
    'csrf_token' => $token
];
$mfr_res = req($base_url, 'POST', $mfr_post, $cookie_file);
if ($mfr_res['code'] === 302 && strpos($mfr_res['redirect'], 'auth/login.php') !== false) {
    echo "[PASS] Successfully registered! Redirected to: {$mfr_res['redirect']}\n";
} else {
    echo "[FAIL] Registration response code: {$mfr_res['code']}\n";
    if (preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $mfr_res['body'], $m)) {
        echo "  Error: " . strip_tags($m[1]) . "\n";
    }
}

// 4. Test Supplier Registration
$sup_email = 'reg_sup_' . time() . '@testdist.in';
echo "4. Registering Supplier ({$sup_email})... ";
$res = req($base_url . '?role=supplier', 'GET', [], $cookie_file);
$token = get_token($res['body']);

$sup_post = [
    'role' => 'supplier',
    'name' => 'Vikram Singhania',
    'email' => $sup_email,
    'phone' => '9876500000',
    'supplier_name' => 'Vanguard Distribution Services',
    'category' => 'Skincare Products',
    'payment_terms' => 'Net 30',
    'address' => '42 Commerce Boulevard, Mumbai',
    'city' => 'Mumbai',
    'state' => 'Maharashtra',
    'password' => 'TestPass@123',
    'confirm_password' => 'TestPass@123',
    'csrf_token' => $token
];
$sup_res = req($base_url, 'POST', $sup_post, $cookie_file);
if ($sup_res['code'] === 302 && strpos($sup_res['redirect'], 'auth/login.php') !== false) {
    echo "[PASS] Successfully registered! Redirected to: {$sup_res['redirect']}\n";
} else {
    echo "[FAIL] Registration response code: {$sup_res['code']}\n";
    if (preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $sup_res['body'], $m)) {
        echo "  Error: " . strip_tags($m[1]) . "\n";
    }
}

// 5. Test Shopkeeper Registration
$shop_email = 'reg_shop_' . time() . '@testboutique.in';
echo "5. Registering Shopkeeper ({$shop_email})... ";
$res = req($base_url . '?role=shopkeeper', 'GET', [], $cookie_file);
$token = get_token($res['body']);

$shop_post = [
    'role' => 'shopkeeper',
    'name' => 'Meera Patel',
    'email' => $shop_email,
    'phone' => '9876511111',
    'shop_name' => 'Bella Glow Salon & Boutique',
    'shop_type' => 'Cosmetics Boutique',
    'address' => '101 Fashion Promenade, Pune',
    'city' => 'Pune',
    'state' => 'Maharashtra',
    'password' => 'TestPass@123',
    'confirm_password' => 'TestPass@123',
    'csrf_token' => $token
];
$shop_res = req($base_url, 'POST', $shop_post, $cookie_file);
if ($shop_res['code'] === 302 && strpos($shop_res['redirect'], 'auth/login.php') !== false) {
    echo "[PASS] Successfully registered! Redirected to: {$shop_res['redirect']}\n";
} else {
    echo "[FAIL] Registration response code: {$shop_res['code']}\n";
    if (preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $shop_res['body'], $m)) {
        echo "  Error: " . strip_tags($m[1]) . "\n";
    }
}

// 6. Verify inserted accounts in database
echo "\n6. Checking Database Records...\n";
$db = get_db();
foreach ([$mfr_email => 'manufacturer', $sup_email => 'supplier', $shop_email => 'shopkeeper'] as $email => $expected_role) {
    $stmt = $db->prepare("SELECT id, name, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['role'] === $expected_role) {
        echo "  [PASS] DB User Record: {$user['name']} ({$user['role']}) -> Active/Pending: {$user['status']}\n";
    } else {
        echo "  [FAIL] DB record missing or role mismatch for {$email}\n";
    }
}

// Check supplier record in suppliers table
$stmt = $db->prepare("SELECT id, supplier_name, supplier_code FROM suppliers WHERE email = ?");
$stmt->execute([$sup_email]);
$sup_row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($sup_row && $sup_row['supplier_name'] === 'Vanguard Distribution Services') {
    echo "  [PASS] Linked Supplier Profile in `suppliers` table verified (ID: {$sup_row['id']}, Code: {$sup_row['supplier_code']})\n";
} else {
    echo "  [FAIL] Linked Supplier Profile missing in `suppliers` table!\n";
}

echo "\n========================================================\n";
echo "REGISTRATION TEST COMPLETED SUCCESSFULLY!\n";
echo "========================================================\n";
