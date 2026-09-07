<?php
/**
 * Supplier Performance Analysis and Management System
 * Quality Inspection & Defect Audit Report
 */

$page_title = "Quality Inspection Report";
require_once __DIR__ . '/../config/config.php';
require_login();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Quality_Inspection_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['PO Number', 'Supplier Name', 'Inspection Date', 'Received Qty', 'Accepted Qty', 'Defective Qty', 'Defect Rate (%)', 'Quality Score (%)', 'Remarks']);

    $q = $db->query("
        SELECT qi.*, po.po_number, s.supplier_name
        FROM quality_inspections qi
        INNER JOIN deliveries d ON qi.delivery_id = d.id
        INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
        INNER JOIN suppliers s ON po.supplier_id = s.id
        ORDER BY qi.inspection_date DESC
    ");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['po_number'],
            $row['supplier_name'],
            $row['inspection_date'],
            $row['quantity_received'],
            $row['quantity_accepted'],
            $row['quantity_defective'],
            $row['defect_rate'],
            $row['quality_score'],
            $row['remarks']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$stmt = $db->query("
    SELECT qi.*, po.po_number, s.supplier_name, s.supplier_code
    FROM quality_inspections qi
    INNER JOIN deliveries d ON qi.delivery_id = d.id
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY qi.inspection_date DESC
");
$inspections = $stmt->fetchAll();

$avg_quality = !empty($inspections) ? round(array_sum(array_column($inspections, 'quality_score')) / count($inspections), 2) : 100;
$avg_defect = !empty($inspections) ? round(array_sum(array_column($inspections, 'defect_rate')) / count($inspections), 2) : 0;
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Quality Inspection & Defect Audit Report</h1>
        <p class="text-muted small mb-0">Component compliance verification, non-conformance metrics, and QA batch assessments.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>reports/quality_report.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- Mini KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="text-uppercase extra-small fw-bold text-muted">Audited Inspection Batches</span>
            <h4 class="fw-bold text-dark my-1"><?= count($inspections) ?></h4>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="text-uppercase extra-small fw-bold text-muted">Average Quality Score</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_quality ?>%</h4>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="text-uppercase extra-small fw-bold text-muted">Average Defect Rate</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_defect ?>%</h4>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Quality Audit Logs (<?= count($inspections) ?>)</h6>
        <span class="badge bg-light text-dark border">Generated: <?= date('d M Y') ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Inspection Date</th>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Qty Received</th>
                    <th>Accepted</th>
                    <th>Defective</th>
                    <th>Defect Rate %</th>
                    <th>Quality Score %</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inspections as $q): ?>
                    <tr>
                        <td><?= format_date($q['inspection_date']) ?></td>
                        <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($q['po_number']) ?></td>
                        <td><?= htmlspecialchars($q['supplier_name']) ?></td>
                        <td><?= number_format($q['quantity_received']) ?></td>
                        <td class="text-success fw-semibold"><?= number_format($q['quantity_accepted']) ?></td>
                        <td class="<?= $q['quantity_defective'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= number_format($q['quantity_defective']) ?></td>
                        <td>
                            <span class="badge <?= (float)$q['defect_rate'] > 3.0 ? 'bg-danger' : 'bg-success' ?>"><?= $q['defect_rate'] ?>%</span>
                        </td>
                        <td>
                            <strong class="font-monospace" style="color: <?= (float)$q['quality_score'] >= 98 ? '#10b981' : ((float)$q['quality_score'] >= 90 ? '#2563eb' : '#ef4444') ?>;">
                                <?= $q['quality_score'] ?>%
                            </strong>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($q['remarks'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
