<?php
/**
 * Automated Database & Environment Bootstrapper
 */

$host = 'localhost';
$user = 'root';
$pass = '';

echo "1. Connecting to MySQL...\n";
$pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "2. Recreating Clean Database `supplier_performance_db`...\n";
$pdo->exec("DROP DATABASE IF EXISTS `supplier_performance_db`;");
$pdo->exec("CREATE DATABASE `supplier_performance_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$pdo->exec("USE `supplier_performance_db`;");

echo "3. Importing Schema...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

$sql1 = file_get_contents(__DIR__ . '/../database/supplier_performance.sql');
// Remove comments and execute statements
$pdo->exec($sql1);

if (file_exists(__DIR__ . '/../database/patch_transfers.sql')) {
    echo "4. Applying Transfers Patch...\n";
    $sql2 = file_get_contents(__DIR__ . '/../database/patch_transfers.sql');
    $pdo->exec($sql2);
}

echo "5. Seeding Verified Test Users...\n";
// Clear old test users
$pdo->exec("DELETE FROM users WHERE email IN ('test_admin@spas.gov', 'test_mfr@glowtech.in', 'test_sup@glowbeauty.in', 'test_shop@luxeglamour.in')");

// 1. Admin
$admin_pass = password_hash('Admin@Pass123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (name, email, password, role, company_name, phone, city, state, status) VALUES (?, ?, ?, 'admin', ?, ?, ?, ?, 'Active')")
    ->execute(['Dr. Alexander Vance', 'test_admin@spas.gov', $admin_pass, 'SPAS Central Governance', '9811001100', 'New Delhi', 'Delhi']);

// 2. Manufacturer
$mfr_pass = password_hash('Mfr@Pass123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (name, email, password, role, company_name, phone, city, state, status) VALUES (?, ?, ?, 'manufacturer', ?, ?, ?, ?, 'Active')")
    ->execute(['Dr. Rajesh Mehta', 'test_mfr@glowtech.in', $mfr_pass, 'GlowTech Formulation Labs', '9822334455', 'Pune', 'Maharashtra']);

// 3. Supplier
$sup_pass = password_hash('Sup@Pass123', PASSWORD_BCRYPT);
$sup_check = $pdo->query("SELECT id FROM suppliers WHERE email = 'test_sup@glowbeauty.in' LIMIT 1")->fetch();
if ($sup_check) {
    $sup_profile_id = $sup_check['id'];
} else {
    $pdo->prepare("INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, city, state, category, status) VALUES ('SUP901', 'Glow Beauty Distribution Hub', 'Rahul Sharma', '9876543210', 'test_sup@glowbeauty.in', 'Mumbai', 'Maharashtra', 'Skincare Products', 'Active')")->execute();
    $sup_profile_id = $pdo->lastInsertId();
}
$pdo->prepare("INSERT INTO users (name, email, password, role, company_name, phone, city, state, supplier_id, status) VALUES (?, ?, ?, 'supplier', ?, ?, ?, ?, ?, 'Active')")
    ->execute(['Rahul Sharma', 'test_sup@glowbeauty.in', $sup_pass, 'Glow Beauty Distribution Hub', '9876543210', 'Mumbai', 'Maharashtra', $sup_profile_id]);

// 4. Shopkeeper
$shop_pass = password_hash('Shop@Pass123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (name, email, password, role, shop_name, phone, city, state, status) VALUES (?, ?, ?, 'shopkeeper', ?, ?, ?, ?, 'Active')")
    ->execute(['Priya Kapoor', 'test_shop@luxeglamour.in', $shop_pass, 'Luxe Glamour Beauty Boutique', '9833445566', 'Bangalore', 'Karnataka']);

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "6. Verifying Database Tables & Counts...\n";
$tables = ['users', 'suppliers', 'products', 'purchase_orders', 'deliveries', 'quality_inspections', 'performance_scores', 'product_transfers'];
foreach ($tables as $t) {
    $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "  - Table `$t`: $c rows\n";
}

echo "DONE! Database initialized successfully.\n";
