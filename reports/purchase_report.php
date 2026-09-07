<?php
/**
 * Supplier Performance Analysis and Management System
 * Purchase Order Spend & Procurement Volume Report
 */

$page_title = "Purchase Order Spend Report";
require_once __DIR__ . '/../config/config.php';
require_login();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Purchase_Order_Spend_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['PO Number', 'Supplier Name', 'Order Date', 'Expected Date', 'Total Amount ($)', 'Status']);

    $q = $db->query("
        SELECT po.*, s.supplier_name
        FROM purchase_orders po
        INNER JOIN suppliers s ON po.supplier_id = s.id
        ORDER BY po.order_date DESC
    ");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['po_number'],
            $row['supplier_name'],
            $row['order_date'],
            $row['expected_date'],
            $row['total_amount'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$stmt = $db->query("
    SELECT po.*, s.supplier_name, s.supplier_code, s.category,
           COUNT(oi.id) as item_count
    FROM purchase_orders po
    INNER JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN order_items oi ON po.id = oi.purchase_order_id
    GROUP BY po.id
    ORDER BY po.order_date DESC
");
$orders = $stmt->fetchAll();
$total_volume = array_sum(array_column($orders, 'total_amount'));
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Purchase Order Spend & Volume Report</h1>
        <p class="text-muted small mb-0">Procurement financial commitments, line item quantities, and order fulfillment states.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>reports/purchase_report.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Procurement Commitments (<?= count($orders) ?>)</h6>
        <span class="badge bg-light text-dark border">Total Spend Volume: <?= format_currency($total_volume) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier Details</th>
                    <th>Order Date</th>
                    <th>Expected Date</th>
                    <th>Line Items</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($o['po_number']) ?></td>
                        <td>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($o['supplier_name']) ?></span>
                            <code class="extra-small text-muted d-block"><?= htmlspecialchars($o['supplier_code']) ?> &bull; <?= htmlspecialchars($o['category']) ?></code>
                        </td>
                        <td><?= format_date($o['order_date']) ?></td>
                        <td><?= format_date($o['expected_date']) ?></td>
                        <td><?= $o['item_count'] ?> items</td>
                        <td class="font-monospace fw-bold text-dark"><?= format_currency($o['total_amount']) ?></td>
                        <td><?= get_status_badge($o['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
