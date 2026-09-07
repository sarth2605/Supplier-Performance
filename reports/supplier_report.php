<?php
/**
 * Supplier Performance Analysis and Management System
 * Supplier Master & Performance Report
 */

$page_title = "Supplier Master Report";
require_once __DIR__ . '/../config/config.php';
require_login();

$db = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Supplier_Master_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Supplier Code', 'Supplier Name', 'Category', 'Contact Person', 'Phone', 'Email', 'City', 'State', 'Status', 'Overall Score', 'Grade']);

    $q = $db->query("
        SELECT s.*, ps.overall_score, ps.grade
        FROM suppliers s
        LEFT JOIN (
            SELECT ps1.* FROM performance_scores ps1
            INNER JOIN (SELECT supplier_id, MAX(id) as max_id FROM performance_scores GROUP BY supplier_id) ps2 ON ps1.id = ps2.max_id
        ) ps ON s.id = ps.supplier_id
        ORDER BY s.supplier_name ASC
    ");
    while ($row = $q->fetch()) {
        fputcsv($output, [
            $row['supplier_code'],
            $row['supplier_name'],
            $row['category'],
            $row['contact_person'],
            $row['phone'],
            $row['email'],
            $row['city'],
            $row['state'],
            $row['status'],
            $row['overall_score'] ?? 'N/A',
            $row['grade'] ?? 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$category = trim($_GET['category'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');

$where = ["1=1"];
$params = [];

if ($category !== 'All' && !empty($category)) {
    $where[] = "s.category = ?";
    $params[] = $category;
}
if ($status !== 'All' && !empty($status)) {
    $where[] = "s.status = ?";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT s.*, ps.overall_score, ps.delivery_score, ps.quality_score, ps.cost_score, ps.reliability_score, ps.grade,
           COUNT(DISTINCT po.id) as total_pos,
           COALESCE(SUM(po.total_amount), 0) as total_spend
    FROM suppliers s
    LEFT JOIN (
        SELECT ps1.* FROM performance_scores ps1
        INNER JOIN (SELECT supplier_id, MAX(id) as max_id FROM performance_scores GROUP BY supplier_id) ps2 ON ps1.id = ps2.max_id
    ) ps ON s.id = ps.supplier_id
    LEFT JOIN purchase_orders po ON s.id = po.supplier_id AND po.status != 'Cancelled'
    WHERE $where_sql
    GROUP BY s.id
    ORDER BY s.supplier_name ASC
");
$stmt->execute($params);
$suppliers = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Supplier Master & Performance Report</h1>
        <p class="text-muted small mb-0">Complete vendor intelligence register with overall scorecards and lifetime spend volume.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>reports/supplier_report.php?export=csv" class="btn btn-outline-success btn-sm rounded-pill">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card-saas mb-4 no-print">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            <div class="col-6 col-md-4">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Categories</option>
                    <option value="Electronics" <?= $category === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                    <option value="Raw Materials" <?= $category === 'Raw Materials' ? 'selected' : '' ?>>Raw Materials</option>
                    <option value="Packaging" <?= $category === 'Packaging' ? 'selected' : '' ?>>Packaging</option>
                    <option value="Hardware" <?= $category === 'Hardware' ? 'selected' : '' ?>>Hardware</option>
                    <option value="Components" <?= $category === 'Components' ? 'selected' : '' ?>>Components</option>
                    <option value="Services" <?= $category === 'Services' ? 'selected' : '' ?>>Services</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Statuses</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>reports/supplier_report.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Supplier Records (<?= count($suppliers) ?>)</h6>
        <span class="badge bg-light text-dark border">Generated: <?= date('d M Y') ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Supplier Code & Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Total Spend</th>
                    <th>Delivery (30%)</th>
                    <th>Quality (30%)</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($s['supplier_name']) ?></span>
                            <code class="extra-small text-muted d-block"><?= htmlspecialchars($s['supplier_code']) ?></code>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['category']) ?></span></td>
                        <td><?= htmlspecialchars($s['city']) ?>, <?= htmlspecialchars($s['state']) ?></td>
                        <td class="font-monospace fw-semibold"><?= format_currency($s['total_spend']) ?></td>
                        <td><?= $s['delivery_score'] ?? '—' ?>%</td>
                        <td><?= $s['quality_score'] ?? '—' ?>%</td>
                        <td>
                            <?php if (isset($s['overall_score'])): ?>
                                <strong class="font-monospace fs-6" style="color: <?= get_supplier_grade($s['overall_score'])['color'] ?>;">
                                    <?= $s['overall_score'] ?>%
                                </strong>
                            <?php else: ?>
                                <span class="text-muted">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?= isset($s['grade']) ? get_grade_badge($s['grade']) : '—' ?></td>
                        <td><?= get_status_badge($s['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
