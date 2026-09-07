<?php
/**
 * Supplier Performance Analysis System
 * Secure Logout Handler
 */

require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'User Sign Out', 'User logged out of session.');
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start new session for flash message
session_start();
set_flash('info', 'You have been successfully signed out.');
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
