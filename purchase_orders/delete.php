<?php
/**
 * Supplier Performance Analysis and Management System
 * Secure Purchase Order Deletion Handler
 */

require_once __DIR__ . '/../config/config.php';
require_role(['admin', 'manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash('danger', 'Security validation token mismatch.');
    header("Location: " . BASE_URL . "purchase_orders/index.php");
    exit;
}

if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT po_number, status FROM purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            // If already delivered, set to Cancelled rather than hard delete to preserve historical analytics integrity
            if ($order['status'] === 'Delivered') {
                $db->prepare("UPDATE purchase_orders SET status = 'Cancelled' WHERE id = ?")->execute([$id]);
                log_activity($_SESSION['user_id'], 'PO Cancelled', 'Purchase Orders', $id, "Cancelled PO {$order['po_number']}");
                set_flash('info', "Purchase Order '{$order['po_number']}' has linked delivery history, so its status was updated to Cancelled.");
            } else {
                $db->prepare("DELETE FROM purchase_orders WHERE id = ?")->execute([$id]);
                log_activity($_SESSION['user_id'], 'PO Deleted', 'Purchase Orders', $id, "Deleted PO {$order['po_number']}");
                set_flash('success', "Purchase Order '{$order['po_number']}' was deleted.");
            }
        } else {
            set_flash('danger', 'Purchase Order not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Deletion error: ' . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "purchase_orders/index.php");
exit;
