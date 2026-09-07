<?php
/**
 * Supplier Performance Analysis and Management System
 * Edit Delivery Record
 */

$page_title = "Edit Delivery Record";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT d.*, po.po_number, po.expected_date, s.supplier_name
    FROM deliveries d
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$delivery = $stmt->fetch();

if (!$delivery) {
    set_flash('danger', 'Delivery record not found.');
    header("Location: " . BASE_URL . "deliveries/index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $delivery_date = trim($_POST['delivery_date'] ?? '');
        $qty_received  = (int)($_POST['quantity_received'] ?? 0);
        $remarks       = trim($_POST['remarks'] ?? '');

        if (empty($delivery_date)) $errors[] = "Delivery date is required.";
        if ($qty_received <= 0) $errors[] = "Quantity received must be greater than zero.";

        if (empty($errors)) {
            // Recompute delay days
            $exp_time = strtotime($delivery['expected_date']);
            $act_time = strtotime($delivery_date);
            $diff_seconds = $act_time - $exp_time;
            $diff_days = (int)floor($diff_seconds / (60 * 60 * 24));

            if ($diff_days > 0) {
                $delay_days = $diff_days;
                $delivery_status = 'Delayed';
            } elseif ($diff_days < 0) {
                $delay_days = 0;
                $delivery_status = 'Early';
            } else {
                $delay_days = 0;
                $delivery_status = 'On Time';
            }

            try {
                $up_stmt = $db->prepare("
                    UPDATE deliveries SET
                        delivery_date = ?, quantity_received = ?, delivery_status = ?, delay_days = ?, remarks = ?
                    WHERE id = ?
                ");
                $up_stmt->execute([$delivery_date, $qty_received, $delivery_status, $delay_days, $remarks, $id]);

                log_activity($_SESSION['user_id'], 'Delivery Updated', 'Deliveries', $id, "Updated delivery for PO {$delivery['po_number']}");
                set_flash('success', "Delivery record updated successfully.");
                header("Location: " . BASE_URL . "deliveries/view.php?id=" . $id);
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
        <h1 class="h3 fw-bold text-dark mb-1">Edit Delivery Record</h1>
        <p class="text-muted small mb-0">Modify arrival date or received quantity for <code class="text-primary font-monospace"><?= htmlspecialchars($delivery['po_number']) ?></code>.</p>
    </div>
    <a href="<?= BASE_URL ?>deliveries/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Delivery
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen text-primary me-2"></i> Update Delivery Details</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Purchase Order</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($delivery['po_number']) ?> (<?= htmlspecialchars($delivery['supplier_name']) ?>)" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Expected Target Date</label>
                <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= format_date($delivery['expected_date']) ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Actual Delivery Date *</label>
                <input type="date" name="delivery_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['delivery_date'] ?? $delivery['delivery_date']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Quantity Received (Units) *</label>
                <input type="number" min="1" step="1" name="quantity_received" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['quantity_received'] ?? $delivery['quantity_received']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Remarks</label>
                <input type="text" name="remarks" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['remarks'] ?? $delivery['remarks']) ?>">
            </div>

            <div class="text-end d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= BASE_URL ?>deliveries/view.php?id=<?= $id ?>" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
