<?php
/**
 * Supplier Performance Analysis and Management System
 * Quality Inspections Directory & QA Log
 */

$page_title = "Quality Inspections";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$search = trim($_GET['search'] ?? '');
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$filter_defect = trim($_GET['defect_filter'] ?? 'All');

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

if ($filter_defect === 'high') {
    $where_clauses[] = "qi.defect_rate > 3.0";
} elseif ($filter_defect === 'zero') {
    $where_clauses[] = "qi.defect_rate = 0";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch suppliers list
$suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Fetch quality inspections
$query = "
    SELECT qi.*, d.delivery_date, d.purchase_order_id,
           po.po_number, s.id as supplier_id, s.supplier_name, s.supplier_code, s.category as supplier_category
    FROM quality_inspections qi
    INNER JOIN deliveries d ON qi.delivery_id = d.id
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE $where_sql
    ORDER BY qi.inspection_date DESC, qi.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$inspections = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Quality Inspections</h1>
        <p class="text-muted small mb-0">Record and review incoming shipment QA tests, defect rate computations, and component compliance.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>quality/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-flask"></i> New Quality Inspection
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
                <select name="defect_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Defect Thresholds</option>
                    <option value="zero" <?= $filter_defect === 'zero' ? 'selected' : '' ?>>Zero Defects (100% Quality)</option>
                    <option value="high" <?= $filter_defect === 'high' ? 'selected' : '' ?>>High Defect (> 3.0% Flagged)</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>quality/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Inspections Table -->
<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark">Quality Inspection Reports (<?= count($inspections) ?>)</h6>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Inspection Date</th>
                    <th>PO & Supplier Details</th>
                    <th>Qty Received</th>
                    <th>Accepted</th>
                    <th>Defective</th>
                    <th>Defect Rate %</th>
                    <th>Quality Score</th>
                    <th>Remarks</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inspections)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">No quality inspection records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($inspections as $q): ?>
                        <tr>
                            <td><?= format_date($q['inspection_date']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $q['purchase_order_id'] ?>" class="text-decoration-none fw-bold font-monospace text-primary">
                                    <?= htmlspecialchars($q['po_number']) ?>
                                </a>
                                <div class="text-dark small fw-semibold"><?= htmlspecialchars($q['supplier_name']) ?></div>
                            </td>
                            <td><?= number_format($q['quantity_received']) ?></td>
                            <td class="text-success fw-semibold"><?= number_format($q['quantity_accepted']) ?></td>
                            <td class="<?= $q['quantity_defective'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= number_format($q['quantity_defective']) ?>
                            </td>
                            <td>
                                <span class="badge <?= (float)$q['defect_rate'] > 3.0 ? 'bg-danger' : ((float)$q['defect_rate'] > 0 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                    <?= $q['defect_rate'] ?>%
                                </span>
                            </td>
                            <td>
                                <strong class="font-monospace fs-6" style="color: <?= (float)$q['quality_score'] >= 98 ? '#10b981' : ((float)$q['quality_score'] >= 90 ? '#2563eb' : '#ef4444') ?>;">
                                    <?= $q['quality_score'] ?>%
                                </strong>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($q['remarks'] ?: '—') ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>quality/view.php?id=<?= $q['id'] ?>" class="btn btn-light border" title="View Inspection Certificate">
                                        <i class="fa-solid fa-eye text-primary"></i>
                                    </a>
                                    <?php if ($_SESSION['user_role'] !== 'staff'): ?>
                                    <a href="<?= BASE_URL ?>quality/edit.php?id=<?= $q['id'] ?>" class="btn btn-light border" title="Edit Inspection">
                                        <i class="fa-solid fa-pen text-secondary"></i>
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
