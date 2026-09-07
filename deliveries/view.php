<?php
/**
 * Supplier Performance Analysis and Management System
 * View Delivery Receipt & Quality Inspection Status
 */

$page_title = "Delivery Receipt";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

// Fetch delivery details
$stmt = $db->prepare("
    SELECT d.*, po.po_number, po.order_date, po.expected_date, po.total_amount,
           s.id as supplier_id, s.supplier_name, s.supplier_code, s.category as supplier_category, s.phone, s.email,
           qi.id as inspection_id, qi.inspection_date, qi.quantity_accepted, qi.quantity_defective, qi.quantity_rejected,
           qi.defect_rate, qi.quality_score, qi.remarks as qi_remarks
    FROM deliveries d
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN quality_inspections qi ON d.id = qi.delivery_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$delivery = $stmt->fetch();

if (!$delivery) {
    set_flash('danger', 'Delivery record not found.');
    header("Location: " . BASE_URL . "deliveries/index.php");
    exit;
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Delivery Receipt #<?= $id ?></h1>
        <p class="text-muted small mb-0">For Purchase Order <code class="text-primary font-monospace fw-bold"><?= htmlspecialchars($delivery['po_number']) ?></code> &bull; <?= htmlspecialchars($delivery['supplier_name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>deliveries/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Deliveries
        </a>
        <?php if (!$delivery['inspection_id']): ?>
            <a href="<?= BASE_URL ?>quality/add.php?delivery_id=<?= $id ?>" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-flask"></i> Perform Quality Inspection
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    
    <!-- Left: Delivery Information -->
    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-truck-fast text-primary me-2"></i> Shipment Arrival Details</h6>
            </div>
            <div class="card-saas-body">
                <table class="table table-custom mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-muted" style="width: 180px;">Purchase Order</td>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $delivery['purchase_order_id'] ?>" class="text-decoration-none fw-bold font-monospace">
                                    <?= htmlspecialchars($delivery['po_number']) ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Supplier</td>
                            <td>
                                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $delivery['supplier_id'] ?>" class="text-decoration-none fw-bold text-dark">
                                    <?= htmlspecialchars($delivery['supplier_name']) ?>
                                </a>
                                <code class="text-muted extra-small">(<?= htmlspecialchars($delivery['supplier_code']) ?>)</code>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Expected Schedule</td>
                            <td><?= format_date($delivery['expected_date']) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Actual Arrival</td>
                            <td class="fw-bold text-dark"><?= format_date($delivery['delivery_date']) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Delivery Status</td>
                            <td>
                                <span class="badge <?= $delivery['delivery_status'] === 'Delayed' ? 'bg-danger' : 'bg-success' ?> fs-6 px-3 py-1">
                                    <?= htmlspecialchars($delivery['delivery_status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Delay Days</td>
                            <td>
                                <?php if ($delivery['delay_days'] > 0): ?>
                                    <span class="text-danger fw-bold">+<?= $delivery['delay_days'] ?> Days Late</span>
                                <?php else: ?>
                                    <span class="text-success fw-bold">0 Days (Punctual)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Quantity Received</td>
                            <td class="fw-bold"><?= number_format($delivery['quantity_received']) ?> units</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Logistics Remarks</td>
                            <td class="small text-secondary"><?= htmlspecialchars($delivery['remarks'] ?: 'No notes') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Linked Quality Inspection -->
    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-shield-halved text-primary me-2"></i> Quality Inspection Result</h6>
            </div>
            <div class="card-saas-body">
                <?php if (!$delivery['inspection_id']): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-flask-vial fs-1 text-warning opacity-75 mb-3 d-block"></i>
                        <h6 class="fw-bold text-dark">Quality Inspection Pending</h6>
                        <p class="text-muted small mb-3">This shipment receipt has not been inspected by the QA team yet.</p>
                        <a href="<?= BASE_URL ?>quality/add.php?delivery_id=<?= $id ?>" class="btn btn-primary btn-sm rounded-pill shadow-sm">
                            <i class="fa-solid fa-plus me-1"></i> Perform Inspection
                        </a>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-light rounded-3 mb-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="extra-small text-uppercase fw-bold text-muted d-block">Quality Score</span>
                            <div class="h3 fw-bold text-success mb-0 font-monospace"><?= $delivery['quality_score'] ?>%</div>
                        </div>
                        <div>
                            <span class="badge <?= (float)$delivery['defect_rate'] > 3.0 ? 'bg-danger' : 'bg-success' ?> fs-6 px-3 py-2">
                                Defect Rate: <?= $delivery['defect_rate'] ?>%
                            </span>
                        </div>
                    </div>

                    <table class="table table-custom mb-0">
                        <tbody>
                            <tr>
                                <td class="fw-semibold text-muted" style="width: 180px;">Inspection Date</td>
                                <td><?= format_date($delivery['inspection_date']) ?></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-muted">Quantity Accepted</td>
                                <td class="text-success fw-bold"><?= number_format($delivery['quantity_accepted']) ?> units</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-muted">Quantity Defective</td>
                                <td class="text-danger fw-bold"><?= number_format($delivery['quantity_defective']) ?> units</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-muted">Quantity Rejected</td>
                                <td class="text-danger"><?= number_format($delivery['quantity_rejected']) ?> units</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold text-muted">QA Observations</td>
                                <td class="small text-secondary"><?= htmlspecialchars($delivery['qi_remarks'] ?: 'None') ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
