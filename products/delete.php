<?php
/**
 * Supplier Performance Analysis and Management System
 * Secure Product Deletion Handler
 */

require_once __DIR__ . '/../config/config.php';
require_role(['admin', 'manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash('danger', 'Security validation mismatch.');
    header("Location: " . BASE_URL . "products/index.php");
    exit;
}

if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT product_name, product_code FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            // Check if product is in order items
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $check_stmt->execute([$id]);
            if ($check_stmt->fetchColumn() > 0) {
                // Soft deactivate if in order items
                $db->prepare("UPDATE products SET status = 'Inactive' WHERE id = ?")->execute([$id]);
                log_activity($_SESSION['user_id'], 'Product Deactivated', 'Products', $id, "Deactivated product {$product['product_name']} due to existing PO references.");
                set_flash('info', "Product '{$product['product_name']}' is referenced in purchase orders, so its status was changed to Inactive.");
            } else {
                $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
                log_activity($_SESSION['user_id'], 'Product Deleted', 'Products', $id, "Deleted product {$product['product_name']} ({$product['product_code']})");
                set_flash('success', "Product '{$product['product_name']}' was permanently deleted.");
            }
        } else {
            set_flash('danger', 'Product not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error deleting product: ' . $e->getMessage());
    }
}

header("Location: " . BASE_URL . "products/index.php");
exit;
