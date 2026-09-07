<?php
/**
 * Supplier Performance Analysis and Management System
 * Product Returns & Damaged Goods Audit Report
 */

$page_title = "Product Returns Report";
require_once __DIR__ . '/../config/config.php';
require_login();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Product_Returns_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Return Code', 'PO Number', 'Supplier Code', 'Supplier Name', 'Product Code', 'Product Name', 'Category', 'Return Date', 'Quantity', 'Return Reason', 'Refund Amount (INR)', 'Status', 'Remarks']);

    $q = $db->query("
        SELECT r.*, po.po_number, p.product_name, p.product_code, p.category as product_category,
               s.supplier_name, s.supplier_code
        FROM returns r
        INNER JOIN purchase_orders po ON r.purchase_order_id = po.id
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN suppliers s ON r.supplier_id = s.id
        ORDER BY r.return_date DESC
    ");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['return_code'],
            $row['po_number'],
            $row['supplier_code'],
            $row['supplier_name'],
            $row['product_code'],
            $row['product_name'],
            $row['product_category'],
            $row['return_date'],
            $row['return_quantity'],
            $row['return_reason'],
            $row['refund_amount'],
            $row['return_status'],
            $row['remarks']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$stmt = $db->query("
    SELECT r.*, po.po_number, p.product_name, p.product_code, p.category as product_category,
           s.supplier_name, s.supplier_code
    FROM returns r
    INNER JOIN purchase_orders po ON r.purchase_order_id = po.id
    INNER JOIN products p ON r.product_id = p.id
    INNER JOIN suppliers s ON r.supplier_id = s.id
    ORDER BY r.return_date DESC
");
$returns = $stmt->fetchAll();

$total_refund = array_sum(array_column($returns, 'refund_amount'));
$total_qty = array_sum(array_column($returns, 'return_quantity'));
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Product Returns & Rejections Report</h1>
        <p class="text-muted small mb-0">Audit non-conforming cosmetic batches, damaged packaging, wrong shades, and credit claims.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>reports/return_report.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- KPI Mini Summary -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #ef4444;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Return Claims</span>
            <h4 class="fw-bold text-dark my-1"><?= count($returns) ?></h4>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Returned Quantity</span>
            <h4 class="fw-bold text-dark my-1"><?= number_format($total_qty) ?> units</h4>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Refund Claimed</span>
            <h4 class="fw-bold text-dark my-1"><?= format_currency($total_refund) ?></h4>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Returns Ledger Records (<?= count($returns) ?>)</h6>
        <span class="badge bg-light text-dark border">Generated: <?= date('d M Y') ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Return Code</th>
                    <th>PO Number</th>
                    <th>Supplier Details</th>
                    <th>Product Item</th>
                    <th>Return Date</th>
                    <th>Quantity</th>
                    <th>Reason</th>
                    <th>Refund (₹)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $r): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($r['return_code']) ?></td>
                        <td class="font-monospace"><?= htmlspecialchars($r['po_number']) ?></td>
                        <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($r['supplier_name']) ?></span>
                            <code class="extra-small text-muted d-block"><?= htmlspecialchars($r['supplier_code']) ?></code>
                        </td>
                        <td>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($r['product_name']) ?></span>
                            <span class="badge bg-light text-dark border extra-small d-block mt-1"><?= htmlspecialchars($r['product_category']) ?></span>
                        </td>
                        <td><?= format_date($r['return_date']) ?></td>
                        <td class="text-danger fw-bold"><?= number_format($r['return_quantity']) ?></td>
                        <td>
                            <span class="badge <?= $r['return_reason'] === 'Damaged' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                <?= htmlspecialchars($r['return_reason']) ?>
                            </span>
                        </td>
                        <td class="font-monospace fw-bold"><?= format_currency($r['refund_amount']) ?></td>
                        <td><?= get_status_badge($r['return_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
