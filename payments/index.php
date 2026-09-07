<?php
/**
 * Supplier Performance Analysis and Management System
 * Payments & Vendor Invoices Management
 */

$page_title = "Payments & Invoices";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$search = trim($_GET['search'] ?? '');
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$status = trim($_GET['status'] ?? 'All');
$method = trim($_GET['method'] ?? 'All');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(pay.payment_code LIKE ? OR pay.invoice_number LIKE ? OR po.po_number LIKE ? OR s.supplier_name LIKE ?)";
    $st = "%$search%";
    $params = array_merge($params, [$st, $st, $st, $st]);
}

if ($supplier_id > 0) {
    $where[] = "pay.supplier_id = ?";
    $params[] = $supplier_id;
}

if ($status !== 'All' && !empty($status)) {
    $where[] = "pay.payment_status = ?";
    $params[] = $status;
}

if ($method !== 'All' && !empty($method)) {
    $where[] = "pay.payment_method = ?";
    $params[] = $method;
}

$where_sql = implode(" AND ", $where);

// Fetch suppliers list
$suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Fetch payments list
$query = "
    SELECT pay.*, po.po_number, s.supplier_name, s.supplier_code, s.category as supplier_category
    FROM payments pay
    INNER JOIN purchase_orders po ON pay.purchase_order_id = po.id
    INNER JOIN suppliers s ON pay.supplier_id = s.id
    WHERE $where_sql
    ORDER BY pay.invoice_date DESC, pay.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$total_invoice_vol = array_sum(array_column($payments, 'invoice_amount'));
$total_paid_vol = array_sum(array_column($payments, 'paid_amount'));
$total_pending_vol = array_sum(array_column($payments, 'pending_amount'));
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Payments & Vendor Invoices</h1>
        <p class="text-muted small mb-0">Manage supplier billing schedules, disbursements, invoice clearance, and pending balances.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>payments/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-receipt"></i> Record Payment / Invoice
        </a>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Invoiced Amount</span>
            <h3 class="fw-bold text-dark my-1"><?= format_currency($total_invoice_vol) ?></h3>
            <span class="extra-small text-muted"><?= count($payments) ?> Billing Records</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Paid Volume</span>
            <h3 class="fw-bold text-dark my-1"><?= format_currency($total_paid_vol) ?></h3>
            <span class="extra-small text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Disbursed</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="text-uppercase extra-small fw-bold text-muted">Pending Balance</span>
            <h3 class="fw-bold text-dark my-1"><?= format_currency($total_pending_vol) ?></h3>
            <span class="extra-small text-warning-emphasis fw-semibold">Outstanding commitments</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #ef4444;">
            <span class="text-uppercase extra-small fw-bold text-muted">Overdue Invoices</span>
            <h3 class="fw-bold text-dark my-1"><?= count(array_filter($payments, fn($p) => $p['payment_status'] === 'Overdue')) ?></h3>
            <span class="extra-small text-danger fw-semibold">Requires payment clearance</span>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Invoice #, PO, supplier..." value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-6 col-md-3">
                <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">All Suppliers</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $supplier_id === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['supplier_code']) ?> - <?= htmlspecialchars($s['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Payment Statuses</option>
                    <option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Partially Paid" <?= $status === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="Overdue" <?= $status === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="method" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Methods</option>
                    <option value="NEFT/RTGS" <?= $method === 'NEFT/RTGS' ? 'selected' : '' ?>>NEFT / RTGS</option>
                    <option value="Bank Transfer" <?= $method === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="UPI" <?= $method === 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="Cheque" <?= $method === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>payments/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Vendor Billing & Payment Register (<?= count($payments) ?>)</h6>
        <span class="badge bg-light text-dark border">Total Volume: <?= format_currency($total_invoice_vol) ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>PO Number</th>
                    <th>Supplier Details</th>
                    <th>Invoice Date</th>
                    <th>Invoice Amount</th>
                    <th>Paid Amount</th>
                    <th>Pending Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">No vendor payment records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><strong class="font-monospace text-primary"><?= htmlspecialchars($p['invoice_number']) ?></strong></td>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $p['purchase_order_id'] ?>" class="font-monospace text-decoration-none">
                                    <?= htmlspecialchars($p['po_number']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($p['supplier_name']) ?></span>
                                <code class="extra-small text-muted d-block"><?= htmlspecialchars($p['supplier_code']) ?> &bull; <?= htmlspecialchars($p['supplier_category']) ?></code>
                            </td>
                            <td><?= format_date($p['invoice_date']) ?></td>
                            <td class="font-monospace fw-bold text-dark"><?= format_currency($p['invoice_amount']) ?></td>
                            <td class="font-monospace text-success fw-semibold"><?= format_currency($p['paid_amount']) ?></td>
                            <td class="font-monospace <?= (float)$p['pending_amount'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= format_currency($p['pending_amount']) ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['payment_method'] ?: 'NEFT/RTGS') ?></span></td>
                            <td><?= get_status_badge($p['payment_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
