<?php
/**
 * End-to-End Authentication & Page Rendering Test
 */

define('BASE_URL', 'http://localhost/Supplier%20Performance/');
$cookie_dir = __DIR__ . '/cookies';
if (!is_dir($cookie_dir)) mkdir($cookie_dir, 0777, true);

function run_request($url, $method = 'GET', $data = [], $cookie_file = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $body, 'final_url' => $final_url];
}

function get_csrf_token($body) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $matches)) {
        return $matches[1];
    }
    return '';
}

function check_page_errors($body) {
    $errors = [];
    if (preg_match('/Fatal error:(.*?)(?:<br|\n)/i', $body, $m)) {
        $errors[] = 'Fatal Error: ' . strip_tags($m[1]);
    }
    if (preg_match('/Warning:(.*?)(?:<br|\n)/i', $body, $m)) {
        $errors[] = 'Warning: ' . strip_tags($m[1]);
    }
    if (preg_match('/Notice:(.*?)(?:<br|\n)/i', $body, $m)) {
        $errors[] = 'Notice: ' . strip_tags($m[1]);
    }
    if (preg_match('/SQLSTATE\[.*?\]/i', $body, $m)) {
        $errors[] = 'SQL Exception detected in body';
    }
    return $errors;
}

echo "=== STARTING E2E AUTHENTICATION & PAGE TESTING ===\n\n";

$roles = [
    'admin' => [
        'login_page' => 'auth/admin_login.php',
        'email'      => 'test_admin@spas.gov',
        'password'   => 'Admin@Pass123',
        'pages_to_test' => [
            'dashboard/index.php' => 'Executive Dashboard',
            'reports/index.php' => 'Reports Studio',
            'suppliers/index.php' => 'Suppliers Management',
            'products/index.php' => 'Products Management',
            'transfers/index.php' => 'Product Transfers',
            'performance/index.php' => 'Performance Evaluation',
            'performance/ranking.php' => 'Supplier Rankings',
            'performance/comparison.php' => 'Supplier Comparison',
            'performance/trends.php' => 'Performance Trends',
            'quality/index.php' => 'Quality Inspections',
            'deliveries/index.php' => 'Deliveries Log',
            'purchase_orders/index.php' => 'Purchase Orders',
            'users/index.php' => 'User Administration'
        ]
    ],
    'manufacturer' => [
        'login_page' => 'auth/manufacturer_login.php',
        'email'      => 'test_mfr@glowtech.in',
        'password'   => 'Mfr@Pass123',
        'pages_to_test' => [
            'manufacturer/dashboard.php' => 'Manufacturer Command Center',
            'transfers/index.php' => 'Supply Chain Transfers',
            'products/index.php' => 'Formulations & Products'
        ]
    ],
    'supplier' => [
        'login_page' => 'auth/supplier_login.php',
        'email'      => 'test_sup@glowbeauty.in',
        'password'   => 'Sup@Pass123',
        'pages_to_test' => [
            'supplier_portal/dashboard.php' => 'Supplier Portal Dashboard',
            'transfers/index.php' => 'Supplier Transfers Flow',
            'products/index.php' => 'Products Supplied'
        ]
    ],
    'shopkeeper' => [
        'login_page' => 'auth/shopkeeper_login.php',
        'email'      => 'test_shop@luxeglamour.in',
        'password'   => 'Shop@Pass123',
        'pages_to_test' => [
            'shopkeeper/dashboard.php' => 'Shopkeeper Retail Dashboard',
            'shopkeeper/catalog.php' => 'Wholesale Catalog',
            'shopkeeper/my_orders.php' => 'Retail Order History',
            'shopkeeper/return_claim.php' => 'Returns & Defect Claim Form',
            'transfers/index.php' => 'Incoming Retail Transfers'
        ]
    ]
];

$all_passed = true;

foreach ($roles as $role => $cfg) {
    echo "--------------------------------------------------------\n";
    echo "Testing Role: " . strtoupper($role) . "\n";
    echo "--------------------------------------------------------\n";
    
    $cookie_file = $cookie_dir . '/' . $role . '_cookie.txt';
    if (file_exists($cookie_file)) unlink($cookie_file);

    // 1. Fetch login form to get CSRF token
    $form_res = run_request(BASE_URL . $cfg['login_page'], 'GET', [], $cookie_file);
    if ($form_res['code'] !== 200) {
        echo "  [FAIL] Cannot load {$cfg['login_page']} (HTTP {$form_res['code']})\n";
        $all_passed = false;
        continue;
    }
    $token = get_csrf_token($form_res['body']);

    // 2. Submit credentials
    $post_data = [
        'role' => $role,
        'email' => $cfg['email'],
        'password' => $cfg['password'],
        'csrf_token' => $token
    ];
    $login_res = run_request(BASE_URL . 'auth/login.php?role=' . $role, 'POST', $post_data, $cookie_file);

    echo "  Login POST result: HTTP {$login_res['code']}, Final URL: {$login_res['final_url']}\n";
    
    // 3. Visit role pages
    foreach ($cfg['pages_to_test'] as $path => $page_title) {
        $page_res = run_request(BASE_URL . $path, 'GET', [], $cookie_file);
        $errs = check_page_errors($page_res['body']);

        if ($page_res['code'] === 200 && empty($errs)) {
            echo "  [PASS] {$page_title} ({$path}) -> HTTP 200 OK\n";
        } else {
            $all_passed = false;
            echo "  [FAIL] {$page_title} ({$path}) -> HTTP {$page_res['code']}";
            if (!empty($errs)) {
                echo " | Errors: " . implode('; ', $errs);
            }
            echo "\n";
        }
    }
    echo "\n";
}

echo "========================================================\n";
echo ($all_passed ? "ALL PAGES AND WORKFLOWS PASSED VALIDATION!" : "SOME CHECKS FAILED - SEE ABOVE") . "\n";
echo "========================================================\n";
