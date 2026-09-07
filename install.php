<?php
/**
 * Supplier Performance Analysis System
 * 1-Click Automated Database Installer & Setup Wizard
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$installed = false;
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_db'])) {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_port = trim($_POST['db_port'] ?? '3306');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? 'supplier_performance');

    try {
        // Connect to MySQL server without database first
        $pdo = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$db_name`;");

        // Read SQL schema file
        $sql_file = __DIR__ . '/database/supplier_performance.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("SQL schema file not found at: $sql_file");
        }

        $sql_content = file_get_contents($sql_file);

        // Execute SQL script
        $pdo->exec($sql_content);

        $installed = true;
        $message = "Database `$db_name` and all 9 tables with seed data were successfully imported!";

    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard — Supplier Performance Analysis System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .wizard-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="wizard-card p-4 p-md-5" style="max-width: 580px; width: 100%;">
    
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-25 text-primary mb-3">
            <i class="fa-solid fa-database fs-2"></i>
        </div>
        <h3 class="fw-bold mb-1">Database Setup Wizard</h3>
        <p class="text-secondary small mb-0">Supplier Performance Analysis System &bull; 1-Click XAMPP Setup</p>
    </div>

    <?php if ($installed): ?>
        <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-4 rounded-3 border-0 bg-success bg-opacity-25 text-success">
            <i class="fa-solid fa-circle-check fs-2"></i>
            <div>
                <h6 class="fw-bold mb-1">Setup Completed Successfully!</h6>
                <p class="small mb-0"><?= htmlspecialchars($message) ?></p>
            </div>
        </div>

        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-25 mb-4 small font-monospace">
            <div class="text-success fw-bold mb-2">⚡ Default Demo Credentials:</div>
            <div>&bull; Admin: <span class="text-info">admin@example.com</span> / <span class="text-warning">Admin@123</span></div>
            <div>&bull; Manager: <span class="text-info">manager@example.com</span> / <span class="text-warning">Manager@123</span></div>
            <div>&bull; Analyst: <span class="text-info">analyst@example.com</span> / <span class="text-warning">Analyst@123</span></div>
        </div>

        <a href="index.php" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow">
            Launch Application <i class="fa-solid fa-arrow-right ms-2"></i>
        </a>

    <?php else: ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show p-3 mb-4 rounded-3 small" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
            <div class="row g-3 mb-4">
                <div class="col-8">
                    <label class="form-label small fw-semibold text-secondary">MySQL Host</label>
                    <input type="text" name="db_host" class="form-control form-control-sm bg-dark text-white border-secondary" value="localhost" required>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-semibold text-secondary">Port</label>
                    <input type="text" name="db_port" class="form-control form-control-sm bg-dark text-white border-secondary" value="3306" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-secondary">MySQL Username</label>
                    <input type="text" name="db_user" class="form-control form-control-sm bg-dark text-white border-secondary" value="root" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-secondary">MySQL Password</label>
                    <input type="password" name="db_pass" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Leave blank for XAMPP default">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">Target Database Name</label>
                    <input type="text" name="db_name" class="form-control form-control-sm bg-dark text-white border-secondary font-monospace" value="supplier_performance" required>
                </div>
            </div>

            <button type="submit" name="install_db" value="1" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-bolt"></i>
                <span>Import Schema & Seed Sample Data</span>
            </button>
        </form>

    <?php endif; ?>

</div>

</body>
</html>
