<?php
/**
 * Supplier Performance Analysis and Management System
 * Delivery Punctuality & Delay Analysis Report
 */

$page_title = "Delivery Punctuality Report";
require_once __DIR__ . '/../config/config.php';
require_login();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Delivery_Punctuality_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['PO Number', 'Supplier Name', 'Expected Date', 'Delivery Date', 'Quantity Received', 'Delivery Status', 'Delay Days', 'Remarks']);

    $q = $db->query("
        SELECT d.*, po.po_number, po.expected_date, s.supplier_name
        FROM deliveries d
        INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
        INNER JOIN suppliers s ON po.supplier_id = s.id
        ORDER BY d.delivery_date DESC
    ");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['po_number'],
            $row['supplier_name'],
            $row['expected_date'],
            $row['delivery_date'],
            $row['quantity_received'],
            $row['delivery_status'],
            $row['delay_days'],
            $row['remarks']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$status = trim($_GET['status'] ?? 'All');
$where = ["1=1"];
$params = [];

if ($status !== 'All' && !empty($status)) {
    $where[] = "d.delivery_status = ?";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT d.*, po.po_number, po.expected_date, s.supplier_name, s.supplier_code
    FROM deliveries d
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE $where_sql
    ORDER BY d.delivery_date DESC
");
$stmt->execute($params);
$deliveries = $stmt->fetchAll();

$total_cnt = count($deliveries);
$on_time_cnt = count(array_filter($deliveries, fn($d) => $d['delivery_status'] === 'On Time' || $d['delivery_status'] === 'Early'));
$delayed_cnt = count(array_filter($deliveries, fn($d) => $d['delivery_status'] === 'Delayed'));
$punctuality_rate = $total_cnt > 0 ? round(($on_time_cnt / $total_cnt) * 100, 1) : 100;
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Delivery Punctuality & Delay Report</h1>
        <p class="text-muted small mb-0">Auditing logistics fulfillment milestones and contractor transit performance.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>reports/delivery_report.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill">
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
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Shipments</span>
            <h4 class="fw-bold text-dark my-1"><?= $total_cnt ?></h4>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="text-uppercase extra-small fw-bold text-muted">On-Time Fulfillment</span>
            <h4 class="fw-bold text-dark my-1"><?= $punctuality_rate ?>%</h4>
        </div>
    </div>
    <div class="col-4">
        <div class="kpi-card" style="--kpi-color: #ef4444;">
            <span class="text-uppercase extra-small fw-bold text-muted">Delayed Deliveries</span>
            <h4 class="fw-bold text-dark my-1"><?= $delayed_cnt ?></h4>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Logistics Records (<?= count($deliveries) ?>)</h6>
        <span class="badge bg-light text-dark border">Generated: <?= date('d M Y') ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier Partner</th>
                    <th>Expected Date</th>
                    <th>Actual Delivery Date</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Delay Days</th>
                    <th>Carrier Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deliveries as $d): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($d['po_number']) ?></td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($d['supplier_name']) ?></td>
                        <td><?= format_date($d['expected_date']) ?></td>
                        <td><strong><?= format_date($d['delivery_date']) ?></strong></td>
                        <td><?= number_format($d['quantity_received']) ?> units</td>
                        <td>
                            <span class="badge <?= $d['delivery_status'] === 'Delayed' ? 'bg-danger' : 'bg-success' ?>">
                                <?= htmlspecialchars($d['delivery_status']) ?>
                            </span>
                        </td>
                        <td><?= $d['delay_days'] > 0 ? "<span class='text-danger fw-bold'>+{$d['delay_days']}d</span>" : "<span class='text-success'>0d</span>" ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($d['remarks'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
