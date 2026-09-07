<?php
/**
 * Supplier Performance Analysis and Management System
 * User Management & RBAC Administration
 */

$page_title = "User Management";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin']);

$db = get_db();

$users = $db->query("
    SELECT id, name, email, role, status, created_at
    FROM users
    ORDER BY role ASC, name ASC
")->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">User Management & Access Control</h1>
        <p class="text-muted small mb-0">Manage system operator accounts, assign security roles, and control access permissions.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>users/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-user-plus"></i> Add New User
        </a>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">System Operator Accounts (<?= count($users) ?>)</h6>
        <span class="badge bg-light text-dark border"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Admin Privileges Active</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email Address</th>
                    <th>Assigned Role</th>
                    <th>Status</th>
                    <th>Registered Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="kpi-icon-pill bg-light border text-dark fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                    <?= strtoupper(substr($u['name'], 0, 2)) ?>
                                </div>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-danger">Administrator</span>
                            <?php elseif ($u['role'] === 'manager'): ?>
                                <span class="badge bg-primary">Supply Chain Manager</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Operations Staff</span>
                            <?php endif; ?>
                        </td>
                        <td><?= get_status_badge($u['status']) ?></td>
                        <td><?= format_date($u['created_at']) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>users/edit.php?id=<?= $u['id'] ?>" class="btn btn-light border" title="Edit User">
                                    <i class="fa-solid fa-pen text-secondary"></i>
                                </a>
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="<?= BASE_URL ?>users/delete.php?id=<?= $u['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" 
                                       class="btn btn-light border text-danger" 
                                       onclick="return confirm('Are you sure you want to delete user <?= htmlspecialchars(addslashes($u['name'])) ?>?')"
                                       title="Delete Account">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
