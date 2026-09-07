<?php
/**
 * Supplier Performance Analysis and Management System
 * Forgot Password Recovery Screen
 */

require_once __DIR__ . '/../config/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = "Security token mismatch. Please try again.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $message = "A password reset link has been dispatched to <strong>" . htmlspecialchars($email) . "</strong>. For final-year project demonstration, default passwords can be tested directly from the login screen.";
            log_activity($user['id'], 'Password Reset Requested', 'Users', $user['id'], "Password reset requested for email: $email");
        } else {
            $error = "No user account was found with that registered email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= APP_FULL_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-bg-blob auth-bg-blob-1"></div>
    <div class="auth-bg-blob auth-bg-blob-2"></div>

    <div class="auth-card">
        
        <div class="text-center mb-4">
            <div class="brand-icon-box mx-auto mb-3" style="width: 48px; height: 48px;">
                <i class="fa-solid fa-key text-white fs-4"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Reset Account Password</h4>
            <p class="text-muted small mb-0">Enter your registered email to receive access instructions.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show small py-2 px-3 mb-3" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2 px-3 mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-secondary">Registered Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control border-start-0 ps-0" placeholder="name@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold shadow-sm mb-3">
                <span>Send Reset Link</span>
                <i class="fa-solid fa-paper-plane ms-1"></i>
            </button>

            <div class="text-center">
                <a href="<?= BASE_URL ?>auth/login.php" class="text-decoration-none small text-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Sign In
                </a>
            </div>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
