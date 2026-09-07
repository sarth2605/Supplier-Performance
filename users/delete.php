<?php
/**
 * Supplier Performance Analysis and Management System
 * Secure User Deletion Handler
 */

require_once __DIR__ . '/../config/config.php';
require_role(['admin']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
    set_flash('danger', 'Security validation mismatch.');
    header("Location: " . BASE_URL . "users/index.php");
    exit;
}

if ($id > 0) {
    if ($id === (int)$_SESSION['user_id']) {
        set_flash('danger', 'Self-deletion is prohibited.');
    } else {
        try {
            $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();

            if ($name) {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                log_activity($_SESSION['user_id'], 'User Deleted', 'Users', $id, "Deleted user $name");
                set_flash('success', "User '$name' was permanently deleted.");
            } else {
                set_flash('danger', 'User not found.');
            }
        } catch (Exception $e) {
            set_flash('danger', 'Deletion error: ' . $e->getMessage());
        }
    }
}

header("Location: " . BASE_URL . "users/index.php");
exit;
