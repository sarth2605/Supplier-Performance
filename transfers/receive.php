<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Handle Inbound Product Transfer Receipt Confirmation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security session expired. Please try again.');
        header('Location: ' . BASE_URL . 'transfers/index.php');
        exit;
    }

    $transfer_id = (int)($_POST['transfer_id'] ?? 0);
    $user_data = get_current_user_data();
    $user_id = (int)$user_data['id'];

    if ($transfer_id <= 0) {
        set_flash('danger', 'Invalid transfer request.');
        header('Location: ' . BASE_URL . 'transfers/index.php');
        exit;
    }

    $res = receive_product_transfer($transfer_id, $user_id);
    if ($res['success']) {
        set_flash('success', $res['message']);
    } else {
        set_flash('danger', $res['message']);
    }
}

header('Location: ' . BASE_URL . 'transfers/index.php');
exit;
