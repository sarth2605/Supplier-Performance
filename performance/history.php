<?php
/**
 * Supplier Performance Analysis System
 * Historical Evaluation Audit Trail & Timeline
 */

$page_title = "Evaluation Audit History";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Filter parameters
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$rating = trim($_GET['rating'] ?? 'All');
$year = trim($_GET['year'] ?? 'All');

$where_clauses = ["1=1"];
$params = [];

if ($supplier_id > 0) {
    $where_clauses[] = "p.supplier_id = ?";
    $params[] = $supplier_id;
}

if ($rating !== 'All' && !empty($rating)) {
    $where_clauses[] = "p.rating = ?";
    $params[] = $rating;
}

if ($year !== 'All' && !empty($year)) {
    $where_clauses[] = "YEAR(p.evaluation_date) = ?";
    $params[] = $year;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch evaluations with supplier details
$query = "
    SELECT p.*, s.supplier_name, s.supplier_code, s.category, u.name as evaluator_name
    FROM performance p
    INNER JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN users u ON p.evaluator_id = u.id
    WHERE $where_sql
    ORDER BY p.evaluation_date DESC, p.id DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$history_records = $stmt->fetchAll();

// Fetch suppliers list for filter dropdown
$suppliers_list = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Evaluation Audit History</h1>
        <p class="text-muted small mb-0">Chronological history log of all completed vendor appraisals and audit observations.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>performance/add.php" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-plus"></i> New Evaluation
        </a>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-12 col-md-5">
                <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">All Suppliers</option>
                    <?php foreach ($suppliers_list as $sup): ?>
                        <option value="<?= $sup['id'] ?>" <?= $supplier_id === (int)$sup['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sup['supplier_code']) ?> - <?= htmlspecialchars($sup['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Ratings</option>
                    <option value="Excellent" <?= $rating === 'Excellent' ? 'selected' : '' ?>>Excellent (90-100)</option>
                    <option value="Good" <?= $rating === 'Good' ? 'selected' : '' ?>>Good (80-89)</option>
                    <option value="Average" <?= $rating === 'Average' ? 'selected' : '' ?>>Average (70-79)</option>
                    <option value="Poor" <?= $rating === 'Poor' ? 'selected' : '' ?>>Poor (60-69)</option>
                    <option value="Critical" <?= $rating === 'Critical' ? 'selected' : '' ?>>Critical (<60)</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Years</option>
                    <option value="2026" <?= $year === '2026' ? 'selected' : '' ?>>2026</option>
                    <option value="2025" <?= $year === '2025' ? 'selected' : '' ?>>2025</option>
                    <option value="2024" <?= $year === '2024' ? 'selected' : '' ?>>2024</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>performance/history.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- History Log Table -->
<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark">Historical Evaluations (<?= count($history_records) ?>)</h6>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Supplier Details</th>
                    <th>Q (30%)</th>
                    <th>D (25%)</th>
                    <th>C (20%)</th>
                    <th>R (15%)</th>
                    <th>S (10%)</th>
                    <th>Defect %</th>
                    <th>Overall Score</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history_records)): ?>
                    <tr><td colspan="11" class="text-center py-4 text-muted">No historical evaluation records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($history_records as $hr): ?>
                        <tr>
                            <td><?= format_date($hr['evaluation_date']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $hr['supplier_id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($hr['supplier_name']) ?>
                                </a>
                                <code class="extra-small text-muted"><?= htmlspecialchars($hr['supplier_code']) ?></code>
                            </td>
                            <td><?= $hr['quality_score'] ?>%</td>
                            <td><?= $hr['delivery_score'] ?>%</td>
                            <td><?= $hr['cost_score'] ?>%</td>
                            <td><?= $hr['reliability_score'] ?>%</td>
                            <td><?= $hr['service_score'] ?>%</td>
                            <td><?= $hr['defect_rate'] ?>%</td>
                            <td>
                                <strong class="font-monospace" style="color: <?= get_rating_tier($hr['overall_score'])['color'] ?>;">
                                    <?= $hr['overall_score'] ?>%
                                </strong>
                            </td>
                            <td><?= get_rating_badge($hr['rating']) ?></td>
                            <td class="extra-small text-secondary"><?= htmlspecialchars($hr['remarks'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
