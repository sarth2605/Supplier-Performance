<?php
/**
 * Supplier Performance Analysis and Management System
 * Deliveries Management & Logistics Tracking
 */

$page_title = "Deliveries & Shipments";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'All');
$supplier_id = (int)($_GET['supplier_id'] ?? 0);

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(po.po_number LIKE ? OR s.supplier_name LIKE ? OR s.supplier_code LIKE ?)";
    $st = "%$search%";
    $params = array_merge($params, [$st, $st, $st]);
}

if ($status !== 'All' && !empty($status)) {
    $where_clauses[] = "d.delivery_status = ?";
    $params[] = $status;
}

if ($supplier_id > 0) {
    $where_clauses[] = "po.supplier_id = ?";
    $params[] = $supplier_id;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch suppliers for filter
$suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Fetch deliveries
$query = "
    SELECT d.*, po.po_number, po.order_date, po.expected_date,
           s.id as supplier_id, s.supplier_name, s.supplier_code, s.category as supplier_category,
           qi.id as inspection_id, qi.quality_score, qi.defect_rate
    FROM deliveries d
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN quality_inspections qi ON d.id = qi.delivery_id
    WHERE $where_sql
    ORDER BY d.delivery_date DESC, d.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$deliveries = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Deliveries & Shipments</h1>
        <p class="text-muted small mb-0">Monitor shipment punctuality, logistics delays, and delivery receipt inspections.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>deliveries/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-truck-ramp-box"></i> Receive New Delivery
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search PO #, supplier..." value="<?= htmlspecialchars($search) ?>">
                </div>
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

            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Delivery Statuses</option>
                    <option value="On Time" <?= $status === 'On Time' ? 'selected' : '' ?>>On Time</option>
                    <option value="Delayed" <?= $status === 'Delayed' ? 'selected' : '' ?>>Delayed</option>
                    <option value="Early" <?= $status === 'Early' ? 'selected' : '' ?>>Early</option>
                    <option value="Partial" <?= $status === 'Partial' ? 'selected' : '' ?>>Partial</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>deliveries/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Deliveries Table -->
<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark">Shipment Receipts (<?= count($deliveries) ?>)</h6>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Expected Date</th>
                    <th>Actual Delivery Date</th>
                    <th>Quantity Received</th>
                    <th>Delivery Status</th>
                    <th>Delay Days</th>
                    <th>Quality Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deliveries)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">No delivery shipment records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($deliveries as $d): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $d['purchase_order_id'] ?>" class="text-decoration-none fw-bold font-monospace text-primary">
                                    <?= htmlspecialchars($d['po_number']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $d['supplier_id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($d['supplier_name']) ?>
                                </a>
                                <span class="text-muted extra-small"><?= htmlspecialchars($d['supplier_code']) ?></span>
                            </td>
                            <td><?= format_date($d['expected_date']) ?></td>
                            <td><strong><?= format_date($d['delivery_date']) ?></strong></td>
                            <td class="fw-semibold"><?= number_format($d['quantity_received']) ?></td>
                            <td>
                                <span class="badge <?= $d['delivery_status'] === 'Delayed' ? 'bg-danger' : ($d['delivery_status'] === 'Early' ? 'bg-info' : 'bg-success') ?>">
                                    <?= htmlspecialchars($d['delivery_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($d['delay_days'] > 0): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">+<?= $d['delay_days'] ?> Days Late</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">0 Days (On Schedule)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($d['inspection_id']): ?>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i> QA: <?= $d['quality_score'] ?>%
                                    </span>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>quality/add.php?delivery_id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-warning extra-small py-0 px-2 rounded-pill">
                                        <i class="fa-solid fa-flask me-1"></i> Inspect Now
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>deliveries/view.php?id=<?= $d['id'] ?>" class="btn btn-light border" title="View Delivery Receipt">
                                        <i class="fa-solid fa-eye text-primary"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>deliveries/edit.php?id=<?= $d['id'] ?>" class="btn btn-light border" title="Edit Delivery">
                                        <i class="fa-solid fa-pen text-secondary"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
