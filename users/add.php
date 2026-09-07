<?php
/**
 * Supplier Performance Analysis and Management System
 * Create System User Account
 */

$page_title = "Add New User";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin']);

$db = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = trim($_POST['role'] ?? 'staff');
        $status   = trim($_POST['status'] ?? 'Active');

        if (empty($name)) $errors[] = "Full Name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email address is required.";
        if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) $errors[] = "Invalid role selected.";

        if (empty($errors)) {
            $check = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                $errors[] = "Email address '$email' is already registered in the system.";
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $db->prepare("
                        INSERT INTO users (name, email, password, role, status, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $ins->execute([$name, $email, $hash, $role, $status]);
                    $new_uid = $db->lastInsertId();

                    log_activity($_SESSION['user_id'], 'User Created', 'Users', $new_uid, "Created user account for $name ($email, Role: $role)");
                    set_flash('success', "User account for '$name' created successfully.");
                    header("Location: " . BASE_URL . "users/index.php");
                    exit;
                } catch (Exception $e) {
                    $errors[] = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Add System User</h1>
        <p class="text-muted small mb-0">Create new user account and set system privileges.</p>
    </div>
    <a href="<?= BASE_URL ?>users/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Users
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Errors:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-saas" style="max-width: 650px;">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-plus text-primary me-2"></i> User Credentials</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. Sarthak Sharma" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Email Address *</label>
                <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="e.g. user@spas.local" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Password *</label>
                <input type="password" name="password" class="form-control form-control-sm" placeholder="Minimum 6 characters" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-secondary">Role Permission *</label>
                    <select name="role" class="form-select form-select-sm" required>
                        <option value="staff" selected>Operations Staff</option>
                        <option value="manager">Supply Chain Manager</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-secondary">Account Status *</label>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="Active" selected>Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="text-end d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>users/index.php" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Create Account</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
