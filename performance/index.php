<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Performance Overview & Automated Batch Calculation Engine
 */

$page_title = "Performance Analysis Overview";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Handle Period Batch Recalculation Request
if (isset($_POST['recalculate_all'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $target_period = trim($_POST['target_period'] ?? '2026-Q1');
        $all_suppliers_query = $db->query("SELECT id FROM suppliers WHERE status != 'Inactive'");
        $count = 0;
        while ($sup = $all_suppliers_query->fetch()) {
            recalculate_supplier_period_score((int)$sup['id'], $target_period);
            $count++;
        }
        log_activity($_SESSION['user_id'], 'Batch Performance Recomputed', 'Performance', null, "Recomputed performance scores for $count beauty suppliers for period $target_period.");
        set_flash('success', "Batch performance recomputation complete! Updated 5-factor scores for $count suppliers in period '$target_period'.");
        header("Location: " . BASE_URL . "performance/index.php?period=" . urlencode($target_period));
        exit;
    }
}

// Filter params
$selected_period = trim($_GET['period'] ?? '2026-Q1');
$category = trim($_GET['category'] ?? 'All');
$grade = trim($_GET['grade'] ?? 'All');

$where_clauses = ["1=1"];
$params = [];

if ($selected_period !== 'All' && !empty($selected_period)) {
    $where_clauses[] = "ps.period = ?";
    $params[] = $selected_period;
}

if ($category !== 'All' && !empty($category)) {
    $where_clauses[] = "s.category = ?";
    $params[] = $category;
}

if ($grade !== 'All' && !empty($grade)) {
    $where_clauses[] = "ps.grade = ?";
    $params[] = $grade;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch distinct periods
$periods = $db->query("SELECT DISTINCT period FROM performance_scores ORDER BY period DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($periods)) $periods = ['2026-Q1', '2026-03', '2026-02', '2026-01'];

// Fetch performance records
$query = "
    SELECT ps.*, s.supplier_name, s.supplier_code, s.category, s.city, s.status as supplier_status
    FROM performance_scores ps
    INNER JOIN suppliers s ON ps.supplier_id = s.id
    WHERE $where_sql
    ORDER BY ps.overall_score DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$scores = $stmt->fetchAll();

// Aggregated Network Averages
$avg_overall = !empty($scores) ? round(array_sum(array_column($scores, 'overall_score')) / count($scores), 2) : 0;
$avg_delivery = !empty($scores) ? round(array_sum(array_column($scores, 'delivery_score')) / count($scores), 2) : 0;
$avg_quality = !empty($scores) ? round(array_sum(array_column($scores, 'quality_score')) / count($scores), 2) : 0;
$avg_fulf = !empty($scores) ? round(array_sum(array_column($scores, 'fulfillment_score')) / count($scores), 2) : 0;
$avg_cost = !empty($scores) ? round(array_sum(array_column($scores, 'cost_score')) / count($scores), 2) : 0;
$avg_ret = !empty($scores) ? round(array_sum(array_column($scores, 'return_score')) / count($scores), 2) : 0;

$categories = [
    'Face Products',
    'Eye Products',
    'Lip Products',
    'Skincare Products',
    'Nail Products',
    'Hair Beauty Products',
    'Body Care Products',
    'Beauty Tools & Accessories'
];
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cosmetics Supplier Performance Hub</h1>
        <p class="text-muted small mb-0">Multi-criteria scoring model across Delivery (30%), Quality (30%), Fulfillment (20%), Cost (10%), and Returns (10%).</p>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" class="d-inline">
            <?= csrf_input() ?>
            <input type="hidden" name="target_period" value="<?= htmlspecialchars($selected_period) ?>">
            <button type="submit" name="recalculate_all" value="1" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="fa-solid fa-arrows-rotate"></i> Recompute <?= htmlspecialchars($selected_period) ?> Scores
            </button>
        </form>
    </div>
</div>

<!-- 5 Parameter Averages KPI Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="text-uppercase extra-small fw-bold text-muted">1. Delivery (30%)</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_delivery ?>%</h4>
            <div class="score-progress mt-2"><div class="score-progress-bar bg-primary" style="width: <?= $avg_delivery ?>%;"></div></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="text-uppercase extra-small fw-bold text-muted">2. Quality (30%)</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_quality ?>%</h4>
            <div class="score-progress mt-2"><div class="score-progress-bar bg-success" style="width: <?= $avg_quality ?>%;"></div></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <span class="text-uppercase extra-small fw-bold text-muted">3. Fulfillment (20%)</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_fulf ?>%</h4>
            <div class="score-progress mt-2"><div class="score-progress-bar bg-purple" style="width: <?= $avg_fulf ?>%;"></div></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="text-uppercase extra-small fw-bold text-muted">4. Cost (10%)</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_cost ?>%</h4>
            <div class="score-progress mt-2"><div class="score-progress-bar bg-warning" style="width: <?= $avg_cost ?>%;"></div></div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="kpi-card" style="--kpi-color: #ec4899;">
            <span class="text-uppercase extra-small fw-bold text-muted">5. Returns (10%)</span>
            <h4 class="fw-bold text-dark my-1"><?= $avg_ret ?>%</h4>
            <div class="score-progress mt-2"><div class="score-progress-bar bg-pink" style="width: <?= $avg_ret ?>%;"></div></div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-6 col-md-3">
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Evaluation Periods</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $selected_period === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-4">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All 8 Beauty Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <select name="grade" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Grades (A+, A, B, C, D)</option>
                    <option value="A+" <?= $grade === 'A+' ? 'selected' : '' ?>>Grade A+ (90–100% Excellent)</option>
                    <option value="A" <?= $grade === 'A' ? 'selected' : '' ?>>Grade A (80–89% Very Good)</option>
                    <option value="B" <?= $grade === 'B' ? 'selected' : '' ?>>Grade B (70–79% Good)</option>
                    <option value="C" <?= $grade === 'C' ? 'selected' : '' ?>>Grade C (60–69% Average)</option>
                    <option value="D" <?= $grade === 'D' ? 'selected' : '' ?>>Grade D (&lt;60% Poor)</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>performance/index.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Performance Master Table -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">Supplier 5-Factor Performance Scorecard (<?= count($scores) ?>)</h6>
        <span class="badge bg-light text-dark border">Average Network Rating: <?= $avg_overall ?>%</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Supplier Details</th>
                    <th>Period</th>
                    <th>Delivery (30%)</th>
                    <th>Quality (30%)</th>
                    <th>Fulfillment (20%)</th>
                    <th>Cost (10%)</th>
                    <th>Returns (10%)</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                    <th class="text-end">Scorecard</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scores)): ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted">No performance calculations recorded for selected criteria. Click "Recompute Scores" above to generate.</td></tr>
                <?php else: ?>
                    <?php foreach ($scores as $s): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>performance/supplier.php?id=<?= $s['supplier_id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($s['supplier_name']) ?>
                                </a>
                                <span class="text-muted extra-small"><?= htmlspecialchars($s['supplier_code']) ?> &bull; <?= htmlspecialchars($s['category']) ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($s['period']) ?></span></td>
                            <td><strong><?= $s['delivery_score'] ?>%</strong></td>
                            <td><strong><?= $s['quality_score'] ?>%</strong></td>
                            <td><strong><?= $s['fulfillment_score'] ?>%</strong></td>
                            <td><strong><?= $s['cost_score'] ?>%</strong></td>
                            <td><strong><?= $s['return_score'] ?>%</strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="score-progress flex-grow-1" style="width: 70px;">
                                        <div class="score-progress-bar" style="width: <?= $s['overall_score'] ?>%; background-color: <?= get_supplier_grade($s['overall_score'])['color'] ?>;"></div>
                                    </div>
                                    <strong class="font-monospace fs-6" style="color: <?= get_supplier_grade($s['overall_score'])['color'] ?>;">
                                        <?= $s['overall_score'] ?>%
                                    </strong>
                                </div>
                            </td>
                            <td><?= get_grade_badge($s['grade']) ?></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>performance/supplier.php?id=<?= $s['supplier_id'] ?>" class="btn btn-sm btn-light border" title="View 360 Analysis">
                                    <i class="fa-solid fa-chart-pie text-primary"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
