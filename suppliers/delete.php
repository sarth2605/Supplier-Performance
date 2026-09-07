<?php
/**
 * Supplier Performance Analysis System
 * Secure Supplier Deletion Handler
 */

require_once __DIR__ . '/../config/config.php';
require_role('Admin');

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$csrf_token = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    set_flash('danger', 'Security validation token mismatch.');
    header("Location: " . BASE_URL . "suppliers/index.php");
    exit;
}

if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT supplier_name, supplier_code FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        $supplier = $stmt->fetch();

        if ($supplier) {
            $del_stmt = $db->prepare("DELETE FROM suppliers WHERE id = ?");
            $del_stmt->execute([$id]);

            log_activity($_SESSION['user_id'], 'Supplier Deleted', "Deleted supplier: {$supplier['supplier_name']} ({$supplier['supplier_code']}) and all associated records.");
            create_notification($_SESSION['user_id'], 'Supplier Removed', "{$supplier['supplier_name']} was removed from the system.", 'Info');

            set_flash('success', "Supplier '{$supplier['supplier_name']}' was successfully deleted.");
        } else {
            set_flash('danger', 'Supplier not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Deletion error: ' . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "suppliers/index.php");
exit;
