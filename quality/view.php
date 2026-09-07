<?php
/**
 * Supplier Performance Analysis and Management System
 * Quality Inspection Certificate & Report View
 */

$page_title = "Quality Inspection Certificate";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT qi.*, d.delivery_date, d.delivery_status, d.delay_days,
           po.po_number, po.order_date, po.expected_date,
           s.id as supplier_id, s.supplier_name, s.supplier_code, s.category as supplier_category
    FROM quality_inspections qi
    INNER JOIN deliveries d ON qi.delivery_id = d.id
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE qi.id = ?
");
$stmt->execute([$id]);
$inspection = $stmt->fetch();

if (!$inspection) {
    set_flash('danger', 'Quality inspection not found.');
    header("Location: " . BASE_URL . "quality/index.php");
    exit;
}
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">QA Inspection Certificate #<?= $id ?></h1>
        <p class="text-muted small mb-0">Component quality audit for PO <code class="text-primary font-monospace fw-bold"><?= htmlspecialchars($inspection['po_number']) ?></code> &bull; <?= htmlspecialchars($inspection['supplier_name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>quality/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Inspections
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print Certificate
        </button>
    </div>
</div>

<!-- Certificate Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body p-4">
        
        <!-- Score Highlights Banner -->
        <div class="p-4 rounded-3 mb-4 text-center" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(16, 185, 129, 0.08)); border: 1px solid rgba(37, 99, 235, 0.2);">
            <span class="text-uppercase extra-small fw-bold text-muted d-block mb-1">Quality Compliance Score</span>
            <div class="display-5 fw-bold font-monospace mb-2" style="color: <?= (float)$inspection['quality_score'] >= 98 ? '#10b981' : ((float)$inspection['quality_score'] >= 90 ? '#2563eb' : '#ef4444') ?>;">
                <?= $inspection['quality_score'] ?>%
            </div>
            <div class="d-flex justify-content-center gap-3">
                <span class="badge <?= (float)$inspection['defect_rate'] > 3.0 ? 'bg-danger' : 'bg-success' ?> fs-6 px-3 py-1">
                    Defect Rate: <?= $inspection['defect_rate'] ?>%
                </span>
                <span class="badge bg-light text-dark border fs-6 px-3 py-1">
                    Inspection Date: <?= format_date($inspection['inspection_date']) ?>
                </span>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-12 col-md-6">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Shipment & Supplier Meta</h6>
                <table class="table table-custom mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-muted" style="width: 160px;">Supplier</td>
                            <td class="fw-bold text-dark">
                                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $inspection['supplier_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($inspection['supplier_name']) ?>
                                </a>
                                <code class="extra-small text-muted ms-1">(<?= htmlspecialchars($inspection['supplier_code']) ?>)</code>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Purchase Order</td>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $inspection['purchase_order_id'] ?? 1 ?>" class="text-decoration-none fw-bold font-monospace">
                                    <?= htmlspecialchars($inspection['po_number']) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Delivery Arrival</td>
                            <td><?= format_date($inspection['delivery_date']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-12 col-md-6">
                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> Quality Metrics Breakdown</h6>
                <table class="table table-custom mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-muted" style="width: 160px;">Quantity Received</td>
                            <td class="fw-bold"><?= number_format($inspection['quantity_received']) ?> units</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Quantity Accepted</td>
                            <td class="text-success fw-bold"><?= number_format($inspection['quantity_accepted']) ?> units</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Quantity Defective</td>
                            <td class="text-danger fw-bold"><?= number_format($inspection['quantity_defective']) ?> units</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Quantity Rejected</td>
                            <td class="text-danger"><?= number_format($inspection['quantity_rejected']) ?> units</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <div class="mt-4 pt-3 border-top">
            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-comment-dots text-primary me-2"></i> QA Inspector Observations</h6>
            <div class="p-3 bg-light rounded-3 small text-secondary">
                <?= nl2br(htmlspecialchars($inspection['remarks'] ?: 'No specific defect notes recorded.')) ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
