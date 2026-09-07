<?php
/**
 * Supplier Performance Analysis and Management System
 * Edit Quality Inspection Record
 */

$page_title = "Edit Quality Inspection";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT qi.*, po.po_number, s.supplier_name
    FROM quality_inspections qi
    INNER JOIN deliveries d ON qi.delivery_id = d.id
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE qi.id = ?
");
$stmt->execute([$id]);
$inspection = $stmt->fetch();

if (!$inspection) {
    set_flash('danger', 'Quality inspection record not found.');
    header("Location: " . BASE_URL . "quality/index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $inspection_date = trim($_POST['inspection_date'] ?? '');
        $qty_received    = (int)($_POST['quantity_received'] ?? 0);
        $qty_accepted    = (int)($_POST['quantity_accepted'] ?? 0);
        $qty_defective   = (int)($_POST['quantity_defective'] ?? 0);
        $qty_rejected    = (int)($_POST['quantity_rejected'] ?? 0);
        $remarks         = trim($_POST['remarks'] ?? '');

        if (empty($inspection_date)) $errors[] = "Inspection date is required.";
        if ($qty_received <= 0) $errors[] = "Quantity received must be greater than zero.";
        if ($qty_defective > $qty_received) $errors[] = "Defective quantity cannot exceed received quantity.";

        if (empty($errors)) {
            $q_calc = calculate_quality_score($qty_defective, $qty_received);
            $defect_rate = $q_calc['defect_rate'];
            $quality_score = $q_calc['quality_score'];

            try {
                $up_stmt = $db->prepare("
                    UPDATE quality_inspections SET
                        inspection_date = ?, quantity_received = ?, quantity_accepted = ?,
                        quantity_defective = ?, quantity_rejected = ?, defect_rate = ?, quality_score = ?, remarks = ?
                    WHERE id = ?
                ");
                $up_stmt->execute([
                    $inspection_date, $qty_received, $qty_accepted,
                    $qty_defective, $qty_rejected, $defect_rate, $quality_score, $remarks, $id
                ]);

                log_activity($_SESSION['user_id'], 'Inspection Updated', 'Quality', $id, "Updated QA record #$id for PO {$inspection['po_number']}");
                set_flash('success', "Quality inspection updated successfully.");
                header("Location: " . BASE_URL . "quality/view.php?id=" . $id);
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
        <h1 class="h3 fw-bold text-dark mb-1">Edit Quality Inspection #<?= $id ?></h1>
        <p class="text-muted small mb-0">Modify test counts for <code class="text-primary font-monospace"><?= htmlspecialchars($inspection['po_number']) ?></code>.</p>
    </div>
    <a href="<?= BASE_URL ?>quality/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Certificate
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen text-primary me-2"></i> Update Inspection Counts</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Inspection Date *</label>
                    <input type="date" name="inspection_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['inspection_date'] ?? $inspection['inspection_date']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Quantity Received *</label>
                    <input type="number" min="1" step="1" name="quantity_received" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['quantity_received'] ?? $inspection['quantity_received']) ?>" required>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-semibold text-secondary">Accepted Qty *</label>
                    <input type="number" min="0" step="1" name="quantity_accepted" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['quantity_accepted'] ?? $inspection['quantity_accepted']) ?>" required>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-semibold text-secondary">Defective Qty *</label>
                    <input type="number" min="0" step="1" name="quantity_defective" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['quantity_defective'] ?? $inspection['quantity_defective']) ?>" required>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-semibold text-secondary">Rejected Qty *</label>
                    <input type="number" min="0" step="1" name="quantity_rejected" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['quantity_rejected'] ?? $inspection['quantity_rejected']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">QA Remarks</label>
                    <textarea name="remarks" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($_POST['remarks'] ?? $inspection['remarks']) ?></textarea>
                </div>
            </div>

            <div class="text-end d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>quality/view.php?id=<?= $id ?>" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save QA Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
