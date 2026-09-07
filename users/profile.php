<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * User Profile & Account Settings
 */

$page_title = 'My Profile & Account Settings';
require_once __DIR__ . '/../includes/header.php';
require_login();

$db = get_db();
$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $shop_name = trim($_POST['shop_name'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if (empty($name)) {
            $error = 'Full name is required.';
        } else {
            try {
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters long.';
                    } else {
                        $hash = password_hash($new_password, PASSWORD_BCRYPT);
                        $upd = $db->prepare("
                            UPDATE users 
                            SET name = ?, phone = ?, city = ?, state = ?, company_name = ?, shop_name = ?, password = ? 
                            WHERE id = ?
                        ");
                        $upd->execute([$name, $phone, $city, $state, $company_name, $shop_name, $hash, $user_id]);
                    }
                } else {
                    $upd = $db->prepare("
                        UPDATE users 
                        SET name = ?, phone = ?, city = ?, state = ?, company_name = ?, shop_name = ? 
                        WHERE id = ?
                    ");
                    $upd->execute([$name, $phone, $city, $state, $company_name, $shop_name, $user_id]);
                }

                if (empty($error)) {
                    $_SESSION['user_name'] = $name;
                    if (!empty($company_name)) $_SESSION['user_company'] = $company_name;
                    if (!empty($shop_name)) $_SESSION['user_shop'] = $shop_name;

                    log_activity($user_id, 'Profile Updated', 'Users', $user_id, 'User updated their personal profile details.');
                    set_flash('success', 'Your profile information has been updated successfully!');
                    header('Location: ' . BASE_URL . 'users/profile.php');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">My Profile & Account Settings</h3>
                <p class="text-muted small mb-0">Manage your contact information, company profile, and security credentials.</p>
            </div>
            <div>
                <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace text-uppercase">
                    <?= ucfirst($user['role']) ?> Account
                </span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4 rounded-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5 flex-shrink-0"></i>
                <div><?= htmlspecialchars($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5 mb-4">
            
            <form action="<?= BASE_URL ?>users/profile.php" method="POST">
                <?= csrf_input() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label small fw-semibold text-secondary">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold text-secondary">Email Address (Read-only)</label>
                        <input type="email" id="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                </div>

                <?php if ($user['role'] === 'manufacturer' || $user['role'] === 'supplier' || $user['role'] === 'admin'): ?>
                    <div class="mb-3">
                        <label for="company_name" class="form-label small fw-semibold text-secondary">Company / Organization / Facility Name</label>
                        <input type="text" name="company_name" id="company_name" class="form-control" value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <?php if ($user['role'] === 'shopkeeper'): ?>
                    <div class="mb-3">
                        <label for="shop_name" class="form-label small fw-semibold text-secondary">Store / Beauty Boutique Name</label>
                        <input type="text" name="shop_name" id="shop_name" class="form-control" value="<?= htmlspecialchars($user['shop_name'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="city" class="form-label small fw-semibold text-secondary">City</label>
                        <input type="text" name="city" id="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="state" class="form-label small fw-semibold text-secondary">State</label>
                        <input type="text" name="state" id="state" class="form-control" value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4 pt-3 border-top">
                    <label for="new_password" class="form-label small fw-semibold text-secondary">Change Password <span class="extra-small text-muted font-normal">(Leave blank to keep current password)</span></label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password (min. 6 chars)" minlength="6">
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <span class="extra-small text-muted">Account Registered: <?= date('d M Y', strtotime($user['created_at'])) ?></span>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
