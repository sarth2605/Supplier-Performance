<?php
/**
 * Supplier Performance Analysis and Management System
 * Returns & Damaged Goods Management Module
 */

$page_title = "Product Returns & Rejections";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$search = trim($_GET['search'] ?? '');
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$reason = trim($_GET['reason'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(r.return_code LIKE ? OR po.po_number LIKE ? OR p.product_name LIKE ? OR s.supplier_name LIKE ?)";
    $st = "%$search%";
    $params = array_merge($params, [$st, $st, $st, $st]);
}

if ($supplier_id > 0) {
    $where[] = "r.supplier_id = ?";
    $params[] = $supplier_id;
}

if ($reason !== 'All' && !empty($reason)) {
    $where[] = "r.return_reason = ?";
    $params[] = $reason;
}

if ($status !== 'All' && !empty($status)) {
    $where[] = "r.return_status = ?";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where);

// Fetch suppliers list
$suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Fetch returns list
$query = "
    SELECT r.*, po.po_number, p.product_name, p.product_code, p.category as product_category,
           s.supplier_name, s.supplier_code
    FROM returns r
    INNER JOIN purchase_orders po ON r.purchase_order_id = po.id
    INNER JOIN products p ON r.product_id = p.id
    INNER JOIN suppliers s ON r.supplier_id = s.id
    WHERE $where_sql
    ORDER BY r.return_date DESC, r.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$returns = $stmt->fetchAll();

$total_refund_amount = array_sum(array_column($returns, 'refund_amount'));
$total_return_units = array_sum(array_column($returns, 'return_quantity'));
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Product Returns & Rejections</h1>
        <p class="text-muted small mb-0">Track damaged cosmetics, defective batches, wrong shipments, and vendor credit notes.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>returns/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-rotate-left"></i> Record Return Claim
        </a>
    </div>
</div>

<!-- Mini KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #ef4444;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Return Claims</span>
            <h3 class="fw-bold text-dark my-1"><?= count($returns) ?></h3>
            <span class="extra-small text-muted">Logged returns</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="text-uppercase extra-small fw-bold text-muted">Returned Units</span>
            <h3 class="fw-bold text-dark my-1"><?= number_format($total_return_units) ?></h3>
            <span class="extra-small text-muted">Total units returned</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Refund Value</span>
            <h3 class="fw-bold text-dark my-1"><?= format_currency($total_refund_amount) ?></h3>
            <span class="extra-small text-muted">Credit claims claimed</span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="text-uppercase extra-small fw-bold text-muted">Approved Status</span>
            <h3 class="fw-bold text-dark my-1"><?= count(array_filter($returns, fn($r) => $r['return_status'] === 'Approved')) ?></h3>
            <span class="extra-small text-muted">Claims resolved</span>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Return #, PO, product..." value="<?= htmlspecialchars($search) ?>">
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
                <select name="reason" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Reasons</option>
                    <option value="Damaged" <?= $reason === 'Damaged' ? 'selected' : '' ?>>Damaged Goods</option>
                    <option value="Wrong Product" <?= $reason === 'Wrong Product' ? 'selected' : '' ?>>Wrong Product</option>
                    <option value="Quality Issue" <?= $reason === 'Quality Issue' ? 'selected' : '' ?>>Quality Issue</option>
                    <option value="Expired" <?= $reason === 'Expired' ? 'selected' : '' ?>>Expired / Shelf-life</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Statuses</option>
                    <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>returns/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Return Claims Ledger (<?= count($returns) ?>)</h6>
        <span class="badge bg-light text-dark border">Total Refund Value: <?= format_currency($total_refund_amount) ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Return Code</th>
                    <th>PO Number</th>
                    <th>Supplier Partner</th>
                    <th>Returned Product</th>
                    <th>Return Date</th>
                    <th>Quantity</th>
                    <th>Return Reason</th>
                    <th>Refund Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">No product return records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($returns as $r): ?>
                        <tr>
                            <td><strong class="font-monospace text-primary"><?= htmlspecialchars($r['return_code']) ?></strong></td>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $r['purchase_order_id'] ?>" class="font-monospace text-decoration-none">
                                    <?= htmlspecialchars($r['po_number']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($r['supplier_name']) ?></span>
                                <code class="extra-small text-muted d-block"><?= htmlspecialchars($r['supplier_code']) ?></code>
                            </td>
                            <td>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($r['product_name']) ?></span>
                                <span class="badge bg-light text-dark border extra-small d-inline-block"><?= htmlspecialchars($r['product_category']) ?></span>
                            </td>
                            <td><?= format_date($r['return_date']) ?></td>
                            <td class="text-danger fw-bold"><?= number_format($r['return_quantity']) ?> units</td>
                            <td>
                                <span class="badge <?= $r['return_reason'] === 'Damaged' ? 'bg-danger' : ($r['return_reason'] === 'Quality Issue' ? 'bg-warning text-dark' : 'bg-info') ?>">
                                    <?= htmlspecialchars($r['return_reason']) ?>
                                </span>
                            </td>
                            <td class="font-monospace fw-bold text-dark"><?= format_currency($r['refund_amount']) ?></td>
                            <td><?= get_status_badge($r['return_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
