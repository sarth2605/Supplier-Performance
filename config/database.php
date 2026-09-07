<?php
/**
 * Supplier Performance Analysis and Management System
 * Database Connection & PDO Handler
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'supplier_performance_db');
define('DB_PORT', '3306');

/**
 * Returns active singleton PDO connection
 * @return PDO
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Fallback check for alternate database name 'supplier_performance'
            try {
                $fallback_dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=supplier_performance;charset=utf8mb4";
                $pdo = new PDO($fallback_dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $ex) {
                http_response_code(500);
                echo '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Database Connection Error</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                </head>
                <body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
                    <div class="card shadow border-0 p-4" style="max-width: 540px; border-radius: 12px;">
                        <div class="card-body text-center">
                            <h4 class="text-danger fw-bold mb-2">Database Connection Error</h4>
                            <p class="text-muted small mb-3">Unable to connect to MySQL database <code>' . DB_NAME . '</code>. Please verify MySQL service in XAMPP.</p>
                            <div class="alert alert-warning text-start small font-monospace mb-3">' . htmlspecialchars($e->getMessage()) . '</div>
                            <a href="install.php" class="btn btn-primary btn-sm px-4">Open Database Setup Wizard</a>
                        </div>
                    </div>
                </body>
                </html>';
                exit;
            }
        }
    }

    return $pdo;
}
