<?php
/**
 * Supplier Performance Analysis System
 * Authentication, Session Security & Role-Based Authorization Guards
 */

/**
 * Checks if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Role Checkers
 */
function is_admin(): bool {
    return is_logged_in() && strtolower($_SESSION['user_role'] ?? '') === 'admin';
}

function is_manufacturer(): bool {
    return is_logged_in() && strtolower($_SESSION['user_role'] ?? '') === 'manufacturer';
}

function is_supplier(): bool {
    return is_logged_in() && strtolower($_SESSION['user_role'] ?? '') === 'supplier';
}

function is_shopkeeper(): bool {
    return is_logged_in() && strtolower($_SESSION['user_role'] ?? '') === 'shopkeeper';
}

/**
 * Returns current logged-in user array
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'           => $_SESSION['user_id'],
        'name'         => $_SESSION['user_name'] ?? 'User',
        'email'        => $_SESSION['user_email'] ?? '',
        'role'         => $_SESSION['user_role'] ?? 'shopkeeper',
        'company_name' => $_SESSION['user_company'] ?? '',
        'shop_name'    => $_SESSION['user_shop'] ?? '',
        'supplier_id'  => $_SESSION['user_supplier_id'] ?? null,
        'department'   => $_SESSION['user_dept'] ?? 'Procurement',
        'avatar'       => $_SESSION['user_avatar'] ?? ''
    ];
}

/**
 * Redirects user to their dedicated role dashboard
 */
function redirect_to_role_dashboard($role = null) {
    if ($role === null) {
        $role = $_SESSION['user_role'] ?? 'admin';
    }
    $role = strtolower(trim($role));
    
    switch ($role) {
        case 'admin':
        case 'manager':
        case 'staff':
            header('Location: ' . BASE_URL . 'dashboard/index.php');
            exit;
        case 'manufacturer':
            header('Location: ' . BASE_URL . 'manufacturer/dashboard.php');
            exit;
        case 'supplier':
            header('Location: ' . BASE_URL . 'supplier_portal/dashboard.php');
            exit;
        case 'shopkeeper':
            header('Location: ' . BASE_URL . 'shopkeeper/dashboard.php');
            exit;
        default:
            header('Location: ' . BASE_URL . 'dashboard/index.php');
            exit;
    }
}

/**
 * Enforces authenticated session
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access your portal.');
        header('Location: ' . BASE_URL . 'auth/portal_select.php');
        exit;
    }
}

/**
 * Enforces role-based access control
 * @param array|string $allowed_roles e.g. ['admin', 'manufacturer']
 */
function require_role($allowed_roles) {
    require_login();
    
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $current_role = strtolower($_SESSION['user_role'] ?? 'shopkeeper');
    $allowed_roles = array_map('strtolower', $allowed_roles);
    
    if (!in_array($current_role, $allowed_roles, true)) {
        set_flash('danger', 'Access restricted. Your account does not have permission to view this section.');
        header('Location: ' . BASE_URL . 'errors/403.php');
        exit;
    }
}

/**
 * Generates CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies CSRF Token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to print CSRF hidden input
 */
function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

/**
 * Sets a flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Retrieves and clears flash message
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Renders Bootstrap Alert for flash message
 */
function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $icon = 'fa-circle-info';
        if ($flash['type'] === 'success') $icon = 'fa-circle-check';
        elseif ($flash['type'] === 'danger' || $flash['type'] === 'error') {
            $flash['type'] = 'danger';
            $icon = 'fa-triangle-exclamation';
        } elseif ($flash['type'] === 'warning') $icon = 'fa-circle-exclamation';

        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="fa-solid ' . $icon . ' me-2 fs-5"></i>
            <div>' . htmlspecialchars($flash['message']) . '</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}
