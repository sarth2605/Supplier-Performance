<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Legacy Multi-Card Portal Selector — Deprecated & Redirected
 * Redirects immediately to Unified Login Gateway (/auth/login.php)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect_to_role_dashboard($_SESSION['user_role'] ?? 'admin');
}

$role_param = isset($_GET['role']) ? '?role=' . urlencode($_GET['role']) : '';
header("Location: " . BASE_URL . "auth/login.php" . $role_param, true, 302);
exit;
