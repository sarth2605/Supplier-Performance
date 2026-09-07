<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Supplier Ranking Leaderboard & Benchmarking
 */

$page_title = "Supplier Rankings";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

$period = trim($_GET['period'] ?? '2026-Q1');
$category = trim($_GET['category'] ?? 'All');

$where_clauses = ["1=1"];
$params = [];

if ($period !== 'All' && !empty($period)) {
    $where_clauses[] = "ps.period = ?";
    $params[] = $period;
}

if ($category !== 'All' && !empty($category)) {
    $where_clauses[] = "s.category = ?";
    $params[] = $category;
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch ranked suppliers
$query = "
    SELECT ps.*, s.supplier_name, s.supplier_code, s.category, s.city, s.state, s.status as supplier_status
    FROM performance_scores ps
    INNER JOIN suppliers s ON ps.supplier_id = s.id
    WHERE $where_sql
    ORDER BY ps.overall_score DESC, ps.quality_score DESC, ps.delivery_score DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$rankings = $stmt->fetchAll();

// Fetch periods
$periods = $db->query("SELECT DISTINCT period FROM performance_scores ORDER BY period DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($periods)) $periods = ['2026-Q1', '2026-03', '2026-02', '2026-01'];

// Prepare Leaderboard Bar Chart Data
$chart_names = [];
$chart_scores = [];
$chart_colors = [];
foreach (array_slice($rankings, 0, 10) as $r) {
    $chart_names[] = strlen($r['supplier_name']) > 16 ? substr($r['supplier_name'], 0, 15) . '…' : $r['supplier_name'];
    $chart_scores[] = (float)$r['overall_score'];
    $chart_colors[] = get_supplier_grade($r['overall_score'])['color'];
}

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
        <h1 class="h3 fw-bold text-dark mb-1">Cosmetics Supplier Ranking Leaderboard</h1>
        <p class="text-muted small mb-0">Ranked from highest to lowest overall weighted score across 5 core beauty supply chain dimensions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>performance/comparison.php" class="btn btn-outline-secondary btn-sm rounded-pill d-inline-flex align-items-center gap-2">
            <i class="fa-solid fa-scale-balanced"></i> Compare Vendors
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card-saas mb-4">
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-2 align-items-center">
            
            <div class="col-6 col-md-4">
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="All">All Periods</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $period === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
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

            <div class="col-12 col-md-4 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter Ranking</button>
                <a href="<?= BASE_URL ?>performance/ranking.php" class="btn btn-light btn-sm border" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </div>

        </form>
    </div>
</div>

<!-- Top Performer Showcase Banner -->
<?php if (!empty($rankings)): ?>
    <?php $winner = $rankings[0]; ?>
    <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-4 rounded-3 border-success border-opacity-25 shadow-sm" role="alert">
        <div class="fs-1 text-warning"><i class="fa-solid fa-trophy"></i></div>
        <div>
            <span class="text-uppercase extra-small fw-bold text-success-emphasis d-block">Rank #1 Category Leader</span>
            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($winner['supplier_name']) ?> <span class="badge bg-success font-monospace ms-2"><?= $winner['overall_score'] ?>% (Grade <?= $winner['grade'] ?>)</span></h5>
            <div class="small text-muted mt-1">Delivery: <?= $winner['delivery_score'] ?>% | Quality: <?= $winner['quality_score'] ?>% | Fulfillment: <?= $winner['fulfillment_score'] ?>% | Cost: <?= $winner['cost_score'] ?>% | Returns: <?= $winner['return_score'] ?>%</div>
        </div>
    </div>
<?php endif; ?>

<!-- Chart Row -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-column text-primary me-2"></i> Top 10 Ranked Beauty Suppliers</h6>
    </div>
    <div class="card-saas-body" style="height: 300px;">
        <canvas id="chart-ranking-bar"></canvas>
    </div>
</div>

<!-- Leaderboard Table -->
<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark">Ranking Leaderboard Table (<?= count($rankings) ?>)</h6>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">Rank</th>
                    <th>Supplier Details</th>
                    <th>Period</th>
                    <th>Delivery (30%)</th>
                    <th>Quality (30%)</th>
                    <th>Fulfillment (20%)</th>
                    <th>Cost (10%)</th>
                    <th>Returns (10%)</th>
                    <th>Overall Score</th>
                    <th>Grade</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rankings)): ?>
                    <tr><td colspan="11" class="text-center py-5 text-muted">No ranking data found for selected period.</td></tr>
                <?php else: ?>
                    <?php foreach ($rankings as $idx => $r): ?>
                        <tr>
                            <td class="fw-bold">
                                <?php if ($idx === 0): ?>
                                    <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-trophy"></i> #1</span>
                                <?php elseif ($idx === 1): ?>
                                    <span class="badge bg-secondary text-white px-2 py-1">#2</span>
                                <?php elseif ($idx === 2): ?>
                                    <span class="badge bg-orange text-white px-2 py-1">#3</span>
                                <?php else: ?>
                                    <span class="text-muted">#<?= $idx + 1 ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>performance/supplier.php?id=<?= $r['supplier_id'] ?>" class="text-decoration-none fw-bold text-dark d-block">
                                    <?= htmlspecialchars($r['supplier_name']) ?>
                                </a>
                                <span class="text-muted extra-small"><?= htmlspecialchars($r['supplier_code']) ?> &bull; <?= htmlspecialchars($r['category']) ?></span>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($r['period']) ?></span></td>
                            <td><?= $r['delivery_score'] ?>%</td>
                            <td><?= $r['quality_score'] ?>%</td>
                            <td><?= $r['fulfillment_score'] ?>%</td>
                            <td><?= $r['cost_score'] ?>%</td>
                            <td><?= $r['return_score'] ?>%</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="score-progress flex-grow-1" style="width: 70px;">
                                        <div class="score-progress-bar" style="width: <?= $r['overall_score'] ?>%; background-color: <?= get_supplier_grade($r['overall_score'])['color'] ?>;"></div>
                                    </div>
                                    <strong class="font-monospace fs-6" style="color: <?= get_supplier_grade($r['overall_score'])['color'] ?>;">
                                        <?= $r['overall_score'] ?>%
                                    </strong>
                                </div>
                            </td>
                            <td><?= get_grade_badge($r['grade']) ?></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>performance/supplier.php?id=<?= $r['supplier_id'] ?>" class="btn btn-sm btn-light border" title="View Diagnostic Hub">
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

<script>
document.addEventListener("DOMContentLoaded", () => {
    const ctx = document.getElementById("chart-ranking-bar").getContext("2d");
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: <?= json_encode($chart_names) ?>,
            datasets: [{
                label: "Overall Score (%)",
                data: <?= json_encode($chart_scores) ?>,
                backgroundColor: <?= json_encode($chart_colors) ?>,
                borderRadius: 6,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { min: 60, max: 100, ticks: { callback: (v) => `${v}%` } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
