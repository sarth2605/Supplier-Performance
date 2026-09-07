<?php
/**
 * Supplier Performance Analysis and Management System
 * Global Application Configuration & Session Handler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Constants
define('APP_NAME', 'SPAS');
define('APP_FULL_NAME', 'Supplier Performance Analysis and Management System');
define('APP_VERSION', '2.0.0');
define('APP_AUTHOR', 'Final Year B.Sc. CS Project');

// Auto-detect dynamic Base URL for XAMPP folder structures and PHP Built-in server
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $proj_root = str_replace('\\', '/', realpath(dirname(__DIR__)));

    $base_path = '/';
    if (!empty($doc_root) && strpos($proj_root, $doc_root) === 0) {
        $sub = trim(substr($proj_root, strlen($doc_root)), '/');
        $base_path = !empty($sub) ? '/' . $sub . '/' : '/';
    } else {
        $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (!empty($script_dir) && $script_dir !== '/') {
            $parts = explode('/', trim($script_dir, '/'));
            $known_modules = [
                'activity_logs', 'assets', 'auth', 'comparison', 'config', 'css', 'dashboard',
                'database', 'deliveries', 'docs', 'errors', 'includes', 'js', 'manufacturer',
                'manufacturers', 'notifications', 'payments', 'performance', 'products',
                'purchase_orders', 'quality', 'reports', 'returns', 'scratch', 'settings',
                'shopkeeper', 'shopkeepers', 'supplier_portal', 'suppliers', 'transfers', 'users'
            ];
            $clean_parts = [];
            foreach ($parts as $p) {
                if (in_array(strtolower($p), $known_modules)) break;
                $clean_parts[] = $p;
            }
            $base_path = !empty($clean_parts) ? '/' . implode('/', $clean_parts) . '/' : '/';
        }
    }
    
    define('BASE_URL', $protocol . $host . $base_path);
}

// Error reporting configuration for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide raw errors from end-users; handled cleanly
ini_set('log_errors', 1);

// Load Database & Auth Helpers
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
