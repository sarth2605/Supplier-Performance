<?php
/**
 * Supplier Performance Analysis System
 * Deep Performance Analysis & Analytics Hub
 */

$page_title = "Performance Analysis";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$selected_id = (int)($_GET['supplier_id'] ?? 0);

// Fetch all active suppliers for selector
$suppliers = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

if ($selected_id === 0 && !empty($suppliers)) {
    $selected_id = (int)$suppliers[0]['id'];
}

// Fetch selected supplier info
$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$selected_id]);
$current_supplier = $stmt->fetch();

// Fetch latest evaluation
$eval_stmt = $db->prepare("SELECT * FROM performance WHERE supplier_id = ? ORDER BY evaluation_date DESC, id DESC LIMIT 1");
$eval_stmt->execute([$selected_id]);
$eval = $eval_stmt->fetch();

// Fetch evaluation history for line chart
$history_stmt = $db->prepare("SELECT * FROM performance WHERE supplier_id = ? ORDER BY evaluation_date ASC");
$history_stmt->execute([$selected_id]);
$eval_history = $history_stmt->fetchAll();

// Recommendation synthesis
$rec_data = generate_recommendations(
    $eval['quality_score'] ?? 85,
    $eval['delivery_score'] ?? 85,
    $eval['cost_score'] ?? 85,
    $eval['reliability_score'] ?? 85,
    $eval['service_score'] ?? 85,
    $eval['defect_rate'] ?? 0,
    $eval['on_time_delivery'] ?? 100,
    $eval['overall_score'] ?? 85
);

$tier = get_rating_tier($eval['overall_score'] ?? 0);

// Chart data
$radar_data = [
    (float)($eval['quality_score'] ?? 80),
    (float)($eval['delivery_score'] ?? 80),
    (float)($eval['cost_score'] ?? 80),
    (float)($eval['reliability_score'] ?? 80),
    (float)($eval['service_score'] ?? 80)
];

$line_labels = [];
$line_data = [];
foreach ($eval_history as $eh) {
    $line_labels[] = format_date($eh['evaluation_date']);
    $line_data[] = (float)$eh['overall_score'];
}
?>

<!-- Header Toolbar -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Deep Performance Analysis</h1>
        <p class="text-muted small mb-0">Inspect multi-axis radar charts, historical score trends, and automated diagnostic summaries.</p>
    </div>
    
    <!-- Supplier Selector Dropdown -->
    <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="d-flex align-items-center gap-2">
        <label class="small fw-bold text-secondary text-nowrap mb-0">Select Supplier:</label>
        <select name="supplier_id" class="form-select form-select-sm" style="min-width: 240px;" onchange="this.form.submit()">
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $selected_id === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['supplier_code']) ?> - <?= htmlspecialchars($s['supplier_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!$current_supplier): ?>
    <div class="alert alert-warning">No supplier records found in database.</div>
<?php else: ?>

<!-- Supplier Overview Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body p-4">
        <div class="row align-items-center g-3">
            
            <div class="col-12 col-md-3 text-center border-end-md">
                <span class="text-uppercase extra-small fw-bold text-muted d-block mb-2">Overall Score</span>
                <div class="circular-score-badge mx-auto my-2" style="border-color: <?= $tier['color'] ?>; color: <?= $tier['color'] ?>; background: <?= $tier['bg'] ?>;">
                    <?= $eval ? $eval['overall_score'] . '%' : '—' ?>
                </div>
                <div class="mt-2">
                    <?= !empty($eval['rating']) ? get_rating_badge($eval['rating']) : '<span class="badge bg-secondary">Unrated</span>' ?>
                </div>
            </div>

            <div class="col-12 col-md-9">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($current_supplier['supplier_name']) ?></h2>
                        <span class="text-muted small"><?= htmlspecialchars($current_supplier['company_name']) ?> &bull; <code class="text-primary"><?= htmlspecialchars($current_supplier['supplier_code']) ?></code> &bull; <?= htmlspecialchars($current_supplier['category']) ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $selected_id ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                        <i class="fa-solid fa-eye me-1"></i> 360 Profile
                    </a>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded-2">
                            <span class="extra-small text-muted d-block">Quality (30%)</span>
                            <span class="fw-bold text-dark"><?= $eval ? $eval['quality_score'] . '%' : 'N/A' ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded-2">
                            <span class="extra-small text-muted d-block">Delivery (25%)</span>
                            <span class="fw-bold text-dark"><?= $eval ? $eval['delivery_score'] . '%' : 'N/A' ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded-2">
                            <span class="extra-small text-muted d-block">Cost (20%)</span>
                            <span class="fw-bold text-dark"><?= $eval ? $eval['cost_score'] . '%' : 'N/A' ?></span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-light rounded-2">
                            <span class="extra-small text-muted d-block">Reliability (15%)</span>
                            <span class="fw-bold text-dark"><?= $eval ? $eval['reliability_score'] . '%' : 'N/A' ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Charts Section: Radar Chart & Historical Trend -->
<div class="row g-3 mb-4">
    
    <div class="col-12 col-lg-5">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-spider text-primary me-2"></i> Multi-Parameter Radar</h6>
            </div>
            <div class="card-saas-body" style="height: 300px;">
                <canvas id="chart-analysis-radar"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Performance Progress Trajectory</h6>
            </div>
            <div class="card-saas-body" style="height: 300px;">
                <canvas id="chart-analysis-trajectory"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Automated AI & Rule-based Diagnostic Analysis -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-brain text-primary me-2"></i> Automated Performance Diagnosis</h6>
    </div>
    <div class="card-saas-body">
        <div class="p-3 bg-light rounded-3 border-start border-primary border-4 mb-3">
            <h6 class="fw-bold text-primary small mb-1"><i class="fa-solid fa-compass me-1"></i> Management Action Recommendation</h6>
            <p class="small text-dark mb-0 fw-semibold"><?= htmlspecialchars($rec_data['recommendation']) ?></p>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="p-3 bg-light rounded-3 border-start border-success border-4 h-100">
                    <h6 class="fw-bold text-success small mb-1"><i class="fa-solid fa-circle-check me-1"></i> Operational Strengths</h6>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($rec_data['strengths']) ?></p>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="p-3 bg-light rounded-3 border-start border-warning border-4 h-100">
                    <h6 class="fw-bold text-warning-emphasis small mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Areas for Improvement</h6>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($rec_data['weaknesses']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Radar
    const radarCtx = document.getElementById("chart-analysis-radar").getContext("2d");
    new Chart(radarCtx, {
        type: "radar",
        data: {
            labels: ["Quality (30%)", "Delivery (25%)", "Cost (20%)", "Reliability (15%)", "Service (10%)"],
            datasets: [{
                label: "Score (%)",
                data: <?= json_encode($radar_data) ?>,
                backgroundColor: "rgba(37, 99, 235, 0.22)",
                borderColor: "#2563eb",
                borderWidth: 2.5,
                pointBackgroundColor: "#2563eb",
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: { display: false, stepSize: 20 },
                    pointLabels: { font: { size: 11, weight: "600" } }
                }
            }
        }
    });

    // 2. Trajectory Line Chart
    const lineCtx = document.getElementById("chart-analysis-trajectory").getContext("2d");
    new Chart(lineCtx, {
        type: "line",
        data: {
            labels: <?= json_encode($line_labels) ?>,
            datasets: [{
                label: "Overall Score (%)",
                data: <?= json_encode($line_data) ?>,
                borderColor: "#10b981",
                backgroundColor: "rgba(16, 185, 129, 0.12)",
                fill: true,
                tension: 0.35,
                borderWidth: 3,
                pointBackgroundColor: "#10b981",
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: {
                    min: 40,
                    max: 100,
                    grid: { color: "#f1f5f9" },
                    ticks: { callback: (v) => `${v}%` }
                }
            }
        }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
