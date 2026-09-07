<?php
/**
 * Supplier Performance Analysis and Management System
 * Edit Purchase Order Status & Terms
 */

$page_title = "Edit Purchase Order";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Purchase Order not found.');
    header("Location: " . BASE_URL . "purchase_orders/index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $expected_date = trim($_POST['expected_date'] ?? '');
        $status        = trim($_POST['status'] ?? 'Pending');

        if (empty($expected_date)) $errors[] = "Expected delivery date is required.";

        if (empty($errors)) {
            try {
                $up_stmt = $db->prepare("UPDATE purchase_orders SET expected_date = ?, status = ? WHERE id = ?");
                $up_stmt->execute([$expected_date, $status, $id]);

                log_activity($_SESSION['user_id'], 'PO Updated', 'Purchase Orders', $id, "Updated PO {$order['po_number']} status to $status");
                set_flash('success', "Purchase Order '{$order['po_number']}' updated successfully.");
                header("Location: " . BASE_URL . "purchase_orders/view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $errors[] = "Update failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Edit Purchase Order</h1>
        <p class="text-muted small mb-0">Update delivery schedule or approval status for <code class="text-primary font-monospace"><?= htmlspecialchars($order['po_number']) ?></code>.</p>
    </div>
    <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to PO
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

<div class="card-saas" style="max-width: 600px;">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen text-primary me-2"></i> Update Order Status</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">PO Number</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($order['po_number']) ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Expected Delivery Date *</label>
                <input type="date" name="expected_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['expected_date'] ?? $order['expected_date']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Purchase Order Status</label>
                <?php $st = $_POST['status'] ?? $order['status']; ?>
                <select name="status" class="form-select form-select-sm">
                    <option value="Pending" <?= $st === 'Pending' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="Approved" <?= $st === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Partially Delivered" <?= $st === 'Partially Delivered' ? 'selected' : '' ?>>Partially Delivered</option>
                    <option value="Delivered" <?= $st === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $st === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="text-end d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $id ?>" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
