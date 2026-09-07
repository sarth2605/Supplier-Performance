<?php
/**
 * Supplier Performance Analysis and Management System
 * Purchase Orders Directory
 */

$page_title = "Purchase Orders";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$search = trim($_GET['search'] ?? '');
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$status = trim($_GET['status'] ?? 'All');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

$where_clauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(po.po_number LIKE ? OR s.supplier_name LIKE ? OR s.supplier_code LIKE ?)";
    $st = "%$search%";
    $params = array_merge($params, [$st, $st, $st]);
}

if ($supplier_id > 0) {
    $where_clauses[] = "po.supplier_id = ?";
    $params[] = $supplier_id;
}

if ($status !== 'All' && !empty($status)) {
    $where_clauses[] = "po.status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $where_clauses[] = "po.order_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "po.order_date <= ?";
    $params[] = $date_to;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch suppliers list for filter dropdown
$suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Fetch POs
$query = "
    SELECT po.*, s.supplier_name, s.supplier_code, s.category as supplier_category,
           COUNT(oi.id) as item_count,
           d.delivery_status, d.delay_days
    FROM purchase_orders po
    INNER JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN order_items oi ON po.id = oi.purchase_order_id
    LEFT JOIN deliveries d ON po.id = d.purchase_order_id
    WHERE $where_sql
    GROUP BY po.id
    ORDER BY po.order_date DESC, po.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$total_po_value = array_sum(array_column($orders, 'total_amount'));
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Purchase Orders</h1>
        <p class="text-muted small mb-0">Create, manage, and track vendor procurement contracts and delivery milestones.</p>
    </div>
    <?php if ($_SESSION['user_role'] !== 'staff'): ?>
    <div>
        <a href="<?= BASE_URL ?>purchase_orders/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> Create Purchase Order
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Filters Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-3">
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

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Statuses</option>
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Partially Delivered" <?= $status === 'Partially Delivered' ? 'selected' : '' ?>>Partially Delivered</option>
                    <option value="Delivered" <?= $status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" placeholder="From Date" value="<?= htmlspecialchars($date_from) ?>">
            </div>

            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>purchase_orders/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Purchase Orders List (<?= count($orders) ?>)</h6>
        <span class="badge bg-light text-dark border">Total Volume: <?= format_currency($total_po_value) ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier Details</th>
                    <th>Order Date</th>
                    <th>Expected Date</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Delivery Status</th>
                    <th>PO Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-file-invoice fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                            <p class="mb-0 fw-semibold">No purchase orders found matching criteria.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $o['id'] ?>" class="text-decoration-none fw-bold font-monospace text-primary">
                                    <?= htmlspecialchars($o['po_number']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $o['supplier_id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($o['supplier_name']) ?>
                                </a>
                                <span class="text-muted extra-small"><?= htmlspecialchars($o['supplier_code']) ?> &bull; <?= htmlspecialchars($o['supplier_category']) ?></span>
                            </td>
                            <td><?= format_date($o['order_date']) ?></td>
                            <td><?= format_date($o['expected_date']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $o['item_count'] ?> items</span></td>
                            <td class="font-monospace fw-bold text-dark"><?= format_currency($o['total_amount']) ?></td>
                            <td>
                                <?php if (!empty($o['delivery_status'])): ?>
                                    <span class="badge <?= $o['delivery_status'] === 'Delayed' ? 'bg-danger' : 'bg-success' ?>">
                                        <?= htmlspecialchars($o['delivery_status']) ?> <?= $o['delay_days'] > 0 ? "({$o['delay_days']}d)" : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Awaiting Delivery</span>
                                <?php endif; ?>
                            </td>
                            <td><?= get_status_badge($o['status']) ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $o['id'] ?>" class="btn btn-light border" title="View PO Invoice">
                                        <i class="fa-solid fa-eye text-primary"></i>
                                    </a>
                                    <?php if ($o['status'] !== 'Delivered' && $o['status'] !== 'Cancelled'): ?>
                                        <a href="<?= BASE_URL ?>deliveries/add.php?po_id=<?= $o['id'] ?>" class="btn btn-light border" title="Record Delivery">
                                            <i class="fa-solid fa-truck-ramp-box text-success"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['user_role'] !== 'staff'): ?>
                                    <a href="<?= BASE_URL ?>purchase_orders/edit.php?id=<?= $o['id'] ?>" class="btn btn-light border" title="Edit Order">
                                        <i class="fa-solid fa-pen text-secondary"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>purchase_orders/delete.php?id=<?= $o['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" 
                                       class="btn btn-light border text-danger" 
                                       onclick="return confirm('Are you sure you want to cancel/delete purchase order <?= htmlspecialchars(addslashes($o['po_number'])) ?>?')"
                                       title="Delete Order">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
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
