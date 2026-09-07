<?php
/**
 * Supplier Performance Analysis and Management System
 * Edit User Account
 */

$page_title = "Edit User Account";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    header("Location: " . BASE_URL . "users/index.php");
    exit;
}

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
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

        if (empty($errors)) {
            // Check email collision
            $check = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->fetchColumn() > 0) {
                $errors[] = "Email address is already in use by another user.";
            } else {
                try {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
                        $up->execute([$name, $email, $hash, $role, $status, $id]);
                    } else {
                        $up = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                        $up->execute([$name, $email, $role, $status, $id]);
                    }

                    log_activity($_SESSION['user_id'], 'User Updated', 'Users', $id, "Updated user $name ($email, Role: $role, Status: $status)");
                    set_flash('success', "User '$name' updated successfully.");
                    header("Location: " . BASE_URL . "users/index.php");
                    exit;
                } catch (Exception $e) {
                    $errors[] = "Update failed: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Edit User: <?= htmlspecialchars($user['name']) ?></h1>
        <p class="text-muted small mb-0">Modify operator details, role permissions, or reset password.</p>
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen text-primary me-2"></i> Update Account</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Email Address *</label>
                <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">New Password (leave blank to keep existing)</label>
                <input type="password" name="password" class="form-control form-control-sm" placeholder="••••••••">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold text-secondary">Role Permission *</label>
                    <?php $r = $_POST['role'] ?? $user['role']; ?>
                    <select name="role" class="form-select form-select-sm" required>
                        <option value="staff" <?= $r === 'staff' ? 'selected' : '' ?>>Operations Staff</option>
                        <option value="manager" <?= $r === 'manager' ? 'selected' : '' ?>>Supply Chain Manager</option>
                        <option value="admin" <?= $r === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold text-secondary">Account Status *</label>
                    <?php $st = $_POST['status'] ?? $user['status']; ?>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="Active" <?= $st === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $st === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="text-end d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>users/index.php" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
