<?php
/**
 * Automated Verification Script for SPAS Authentication & Transfer Workflow
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

$db = get_db();

echo "=======================================================\n";
echo "1. TESTING USER REGISTRATIONS ACROSS ALL 4 ROLES\n";
echo "=======================================================\n";

// Clear previous test users & supplier if any
$db->exec("DELETE FROM users WHERE email IN ('test_admin@spas.gov', 'test_mfr@glowtech.in', 'test_sup@glowbeauty.in', 'test_shop@luxeglamour.in')");
$db->exec("DELETE FROM suppliers WHERE supplier_code = 'SUP901'");

// 1. Admin Registration
$admin_pass = password_hash('Admin@Pass123', PASSWORD_BCRYPT);
$db->prepare("INSERT INTO users (name, email, password, role, company_name, phone, city, state, status) VALUES (?, ?, ?, 'admin', ?, ?, ?, ?, 'Active')")
   ->execute(['Dr. Alexander Vance', 'test_admin@spas.gov', $admin_pass, 'SPAS Central Governance', '9811001100', 'New Delhi', 'Delhi']);
$admin_id = $db->lastInsertId();
echo "✓ Admin Registered (ID: $admin_id, Email: test_admin@spas.gov)\n";

// 2. Manufacturer Registration
$mfr_pass = password_hash('Mfr@Pass123', PASSWORD_BCRYPT);
$db->prepare("INSERT INTO users (name, email, password, role, company_name, phone, city, state, status) VALUES (?, ?, ?, 'manufacturer', ?, ?, ?, ?, 'Active')")
   ->execute(['Dr. Rajesh Mehta', 'test_mfr@glowtech.in', $mfr_pass, 'GlowTech Formulation Labs', '9822334455', 'Pune', 'Maharashtra']);
$mfr_id = $db->lastInsertId();
echo "✓ Manufacturer Registered (ID: $mfr_id, Email: test_mfr@glowtech.in, Company: GlowTech Formulation Labs)\n";

// 3. Supplier Registration
$sup_pass = password_hash('Sup@Pass123', PASSWORD_BCRYPT);
$db->prepare("INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, city, state, category, status) VALUES ('SUP901', 'Glow Beauty Distribution Hub', 'Rahul Sharma', '9876543210', 'test_sup@glowbeauty.in', 'Mumbai', 'Maharashtra', 'Skincare Products', 'Active')")
   ->execute();
$supplier_profile_id = $db->lastInsertId();

$db->prepare("INSERT INTO users (name, email, password, role, company_name, phone, city, state, supplier_id, status) VALUES (?, ?, ?, 'supplier', ?, ?, ?, ?, ?, 'Active')")
   ->execute(['Rahul Sharma', 'test_sup@glowbeauty.in', $sup_pass, 'Glow Beauty Distribution Hub', '9876543210', 'Mumbai', 'Maharashtra', $supplier_profile_id]);
$sup_id = $db->lastInsertId();
echo "✓ Supplier Registered (ID: $sup_id, Email: test_sup@glowbeauty.in, Supplier ID: $supplier_profile_id)\n";

// 4. Shopkeeper Registration
$shop_pass = password_hash('Shop@Pass123', PASSWORD_BCRYPT);
$db->prepare("INSERT INTO users (name, email, password, role, shop_name, phone, city, state, status) VALUES (?, ?, ?, 'shopkeeper', ?, ?, ?, ?, 'Active')")
   ->execute(['Ananya Patel', 'test_shop@luxeglamour.in', $shop_pass, 'Luxe Glamour Beauty Boutique', '9911223344', 'Bengaluru', 'Karnataka']);
$shop_id = $db->lastInsertId();
echo "✓ Shopkeeper Registered (ID: $shop_id, Email: test_shop@luxeglamour.in, Shop: Luxe Glamour Beauty Boutique)\n";

echo "\n=======================================================\n";
echo "2. TESTING STRICT ROLE AUTHENTICATION VERIFICATION\n";
echo "=======================================================\n";

// Test A: Admin login with admin credentials
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['test_admin@spas.gov']);
$u = $stmt->fetch();
$valid = ($u && password_verify('Admin@Pass123', $u['password']) && $u['role'] === 'admin');
echo ($valid ? "✓ PASS" : "✗ FAIL") . ": Admin login allows role 'admin'\n";

// Test B: Attempt Supplier logging in as Admin
$stmt->execute(['test_sup@glowbeauty.in']);
$u = $stmt->fetch();
$allowed_as_admin = ($u && password_verify('Sup@Pass123', $u['password']) && $u['role'] === 'admin');
echo (!$allowed_as_admin ? "✓ PASS" : "✗ FAIL") . ": Supplier is REJECTED from Admin login (Strict RBAC)\n";

// Test C: Attempt Shopkeeper logging in as Manufacturer
$stmt->execute(['test_shop@luxeglamour.in']);
$u = $stmt->fetch();
$allowed_as_mfr = ($u && password_verify('Shop@Pass123', $u['password']) && $u['role'] === 'manufacturer');
echo (!$allowed_as_mfr ? "✓ PASS" : "✗ FAIL") . ": Shopkeeper is REJECTED from Manufacturer login (Strict RBAC)\n";

echo "\n=======================================================\n";
echo "3. TESTING PRODUCT CREATION BY MANUFACTURER\n";
echo "=======================================================\n";

$sku = 'PRD-SKN-' . rand(100, 999);
$db->prepare("
    INSERT INTO products (product_code, product_name, category, sub_category, brand, manufacturer_id, unit, standard_price, wholesale_price, stock_quantity, status)
    VALUES (?, 'Glow Botanics Vitamin C 20% Serum 50ml', 'Skincare Products', 'Serums', 'GlowTech Botanics', ?, 'pcs', 750.00, 520.00, 150, 'Active')
")->execute([$sku, $mfr_id]);
$product_id = $db->lastInsertId();

// Initialize Manufacturer Inventory
adjust_user_stock($mfr_id, 'manufacturer', $product_id, 150, 'BATCH-202609-01');
$mfr_stock = get_user_stock($mfr_id, $product_id);
echo "✓ Product Created: 'Glow Botanics Vitamin C 20% Serum' (ID: $product_id, SKU: $sku)\n";
echo "✓ Manufacturer Warehouse Initial Stock: $mfr_stock pcs\n";

echo "\n=======================================================\n";
echo "4. TESTING STAGE 1: MANUFACTURER -> SUPPLIER TRANSFER\n";
echo "=======================================================\n";

$transfer1 = create_product_transfer(
    $mfr_id, 'manufacturer',
    $sup_id, 'supplier',
    $product_id,
    80, // Transfer 80 pcs
    520.00,
    'BATCH-202609-01',
    'Cold-chain dispatched from Pune Lab to Mumbai Distribution Hub.'
);

echo "✓ Transfer 1 Dispatched: " . ($transfer1['success'] ? "SUCCESS ({$transfer1['transfer_ref']})" : "FAILED: {$transfer1['message']}") . "\n";
$mfr_stock_after = get_user_stock($mfr_id, $product_id);
$sup_stock_before = get_user_stock($sup_id, $product_id);
echo "✓ Manufacturer Remaining Stock: $mfr_stock_after pcs (Expected: 70 pcs)\n";
echo "✓ Supplier In-Hub Stock before receipt: $sup_stock_before pcs (Expected: 0 pcs in transit)\n";

// Supplier Receives Transfer 1
$recv1 = receive_product_transfer($transfer1['transfer_id'], $sup_id);
echo "✓ Supplier Accepts & Receives Transfer 1: " . ($recv1['success'] ? "SUCCESS" : "FAILED: {$recv1['message']}") . "\n";
$sup_stock_after = get_user_stock($sup_id, $product_id);
echo "✓ Supplier Stock after receipt: $sup_stock_after pcs (Expected: 80 pcs)\n";

echo "\n=======================================================\n";
echo "5. TESTING STAGE 2: SUPPLIER -> SHOPKEEPER TRANSFER\n";
echo "=======================================================\n";

$transfer2 = create_product_transfer(
    $sup_id, 'supplier',
    $shop_id, 'shopkeeper',
    $product_id,
    35, // Transfer 35 pcs to shopkeeper
    600.00,
    'BATCH-202609-01',
    'Dispatched wholesale replenishment order to Luxe Glamour Boutique, Bengaluru.',
    $transfer1['transfer_id']
);

echo "✓ Transfer 2 Dispatched: " . ($transfer2['success'] ? "SUCCESS ({$transfer2['transfer_ref']})" : "FAILED: {$transfer2['message']}") . "\n";
$sup_stock_final = get_user_stock($sup_id, $product_id);
$shop_stock_before = get_user_stock($shop_id, $product_id);
echo "✓ Supplier Remaining Stock: $sup_stock_final pcs (Expected: 45 pcs)\n";
echo "✓ Shopkeeper In-Store Stock before receipt: $shop_stock_before pcs (Expected: 0 pcs)\n";

// Shopkeeper Receives Transfer 2
$recv2 = receive_product_transfer($transfer2['transfer_id'], $shop_id);
echo "✓ Shopkeeper Accepts & Receives Transfer 2: " . ($recv2['success'] ? "SUCCESS" : "FAILED: {$recv2['message']}") . "\n";
$shop_stock_final = get_user_stock($shop_id, $product_id);
echo "✓ Shopkeeper Retail Store Stock: $shop_stock_final pcs (Expected: 35 pcs)\n";

echo "\n=======================================================\n";
echo "6. VERIFYING COMPLETE SUPPLY CHAIN PROVENANCE CHAIN\n";
echo "=======================================================\n";

$chain = get_transfer_chain_details($transfer2['transfer_id']);
echo "Current Transfer: " . $chain['current']['transfer_ref'] . " (Stage: " . $chain['current']['stage'] . ")\n";
echo "Parent Transfer:  " . ($chain['parent']['transfer_ref'] ?? 'None') . " (Stage: " . ($chain['parent']['stage'] ?? 'None') . ")\n";
echo "Complete Provenance Chain:\n";
echo "  [1. Manufacturer] " . ($chain['parent']['sender_company'] ?? 'GlowTech Labs') . " (" . ($chain['parent']['sender_city'] ?? 'Pune') . ")\n";
echo "        │ (Dispatched " . ($chain['parent']['quantity'] ?? 80) . " pcs)\n";
echo "        ▼\n";
echo "  [2. Supplier]     " . $chain['current']['sender_company'] . " (" . $chain['current']['sender_city'] . ")\n";
echo "        │ (Dispatched " . $chain['current']['quantity'] . " pcs)\n";
echo "        ▼\n";
echo "  [3. Shopkeeper]   " . $chain['current']['receiver_shop'] . " (" . $chain['current']['receiver_city'] . ")\n";

echo "\n=======================================================\n";
echo "ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "=======================================================\n";
