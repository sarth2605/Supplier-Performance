<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Performance Trends & Longitudinal Analytics
 */

$page_title = "Performance Trends";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$supplier_id = (int)($_GET['supplier_id'] ?? 0);

// Fetch suppliers for filter
$suppliers = $db->query("SELECT id, supplier_name, supplier_code FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Dynamic query for trends
if ($supplier_id > 0) {
    $trend_query = "
        SELECT ps.period, 
               ps.overall_score as avg_overall,
               ps.delivery_score as avg_delivery,
               ps.quality_score as avg_quality,
               ps.fulfillment_score as avg_fulfillment,
               ps.cost_score as avg_cost,
               ps.return_score as avg_return
        FROM performance_scores ps
        WHERE ps.supplier_id = ?
        ORDER BY ps.id ASC
    ";
    $stmt = $db->prepare($trend_query);
    $stmt->execute([$supplier_id]);
    $trend_rows = $stmt->fetchAll();
} else {
    $trend_query = "
        SELECT ps.period, 
               AVG(ps.overall_score) as avg_overall,
               AVG(ps.delivery_score) as avg_delivery,
               AVG(ps.quality_score) as avg_quality,
               AVG(ps.fulfillment_score) as avg_fulfillment,
               AVG(ps.cost_score) as avg_cost,
               AVG(ps.return_score) as avg_return
        FROM performance_scores ps
        GROUP BY ps.period
        ORDER BY MIN(ps.id) ASC
    ";
    $trend_rows = $db->query($trend_query)->fetchAll();
}

$periods = array_column($trend_rows, 'period');
$overall_data = array_map(fn($v) => round((float)$v, 2), array_column($trend_rows, 'avg_overall'));
$delivery_data = array_map(fn($v) => round((float)$v, 2), array_column($trend_rows, 'avg_delivery'));
$quality_data = array_map(fn($v) => round((float)$v, 2), array_column($trend_rows, 'avg_quality'));
$fulf_data = array_map(fn($v) => round((float)$v, 2), array_column($trend_rows, 'avg_fulfillment'));
$cost_data = array_map(fn($v) => round((float)$v, 2), array_column($trend_rows, 'avg_cost'));
$ret_data = array_map(fn($v) => round((float)$v, 2), array_column($trend_rows, 'avg_return'));
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cosmetics Performance Trends</h1>
        <p class="text-muted small mb-0">Track longitudinal multi-factor progression across monthly, quarterly, and annual beauty supplier audit cycles.</p>
    </div>
    
    <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-secondary text-nowrap mb-0">Scope:</label>
        <select name="supplier_id" class="form-select form-select-sm" style="min-width: 220px;" onchange="this.form.submit()">
            <option value="0">Network Average (All Beauty Suppliers)</option>
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $supplier_id === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['supplier_code']) ?> - <?= htmlspecialchars($s['supplier_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Primary Trend Line Chart Card -->
<div class="card-saas mb-4">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Overall Score & Key Factor Progression</h6>
        <span class="badge bg-light text-dark border"><?= count($periods) ?> Evaluated Cycles</span>
    </div>
    <div class="card-saas-body" style="height: 360px;">
        <canvas id="chart-performance-trends"></canvas>
    </div>
</div>

<!-- Two Column Breakdown Charts -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-truck-fast text-primary me-2"></i> Delivery (30%) vs Quality (30%)</h6>
            </div>
            <div class="card-saas-body" style="height: 280px;">
                <canvas id="chart-deliv-quality-trend"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-packing text-primary me-2"></i> Fulfillment (20%) vs Return Score (10%)</h6>
            </div>
            <div class="card-saas-body" style="height: 280px;">
                <canvas id="chart-cost-rel-trend"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Period Tabular Data -->
<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark">Period 5-Factor Aggregate Score Values</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Evaluation Period</th>
                    <th>Delivery (30%)</th>
                    <th>Quality (30%)</th>
                    <th>Fulfillment (20%)</th>
                    <th>Cost (10%)</th>
                    <th>Returns (10%)</th>
                    <th>Overall Weighted Score</th>
                    <th>Grade Classification</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trend_rows as $tr): ?>
                    <tr>
                        <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($tr['period']) ?></td>
                        <td><?= round((float)$tr['avg_delivery'], 2) ?>%</td>
                        <td><?= round((float)$tr['avg_quality'], 2) ?>%</td>
                        <td><?= round((float)$tr['avg_fulfillment'], 2) ?>%</td>
                        <td><?= round((float)$tr['avg_cost'], 2) ?>%</td>
                        <td><?= round((float)$tr['avg_return'], 2) ?>%</td>
                        <td>
                            <strong class="font-monospace fs-6" style="color: <?= get_supplier_grade($tr['avg_overall'])['color'] ?>;">
                                <?= round((float)$tr['avg_overall'], 2) ?>%
                            </strong>
                        </td>
                        <td><?= get_grade_badge(get_supplier_grade($tr['avg_overall'])['grade']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const labels = <?= json_encode($periods) ?>;

    // 1. Overall Trend Chart
    const ctx1 = document.getElementById("chart-performance-trends").getContext("2d");
    new Chart(ctx1, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Overall Score (%)",
                    data: <?= json_encode($overall_data) ?>,
                    borderColor: "#10b981",
                    backgroundColor: "rgba(16, 185, 129, 0.1)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3.5,
                    pointRadius: 6,
                    pointBackgroundColor: "#10b981"
                },
                {
                    label: "Quality (30%)",
                    data: <?= json_encode($quality_data) ?>,
                    borderColor: "#2563eb",
                    borderDash: [4, 4],
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 4
                },
                {
                    label: "Delivery (30%)",
                    data: <?= json_encode($delivery_data) ?>,
                    borderColor: "#f59e0b",
                    borderDash: [4, 4],
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 12 } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { min: 60, max: 100, ticks: { callback: (v) => `${v}%` } }
            }
        }
    });

    // 2. Delivery vs Quality
    const ctx2 = document.getElementById("chart-deliv-quality-trend").getContext("2d");
    new Chart(ctx2, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Delivery Score (%)",
                    data: <?= json_encode($delivery_data) ?>,
                    borderColor: "#f59e0b",
                    tension: 0.35,
                    borderWidth: 2.5
                },
                {
                    label: "Quality Score (%)",
                    data: <?= json_encode($quality_data) ?>,
                    borderColor: "#2563eb",
                    tension: 0.35,
                    borderWidth: 2.5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: "bottom" } },
            scales: { y: { min: 60, max: 100, ticks: { callback: (v) => `${v}%` } } }
        }
    });

    // 3. Fulfillment vs Return Score
    const ctx3 = document.getElementById("chart-cost-rel-trend").getContext("2d");
    new Chart(ctx3, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Fulfillment Score (%)",
                    data: <?= json_encode($fulf_data) ?>,
                    borderColor: "#8b5cf6",
                    tension: 0.35,
                    borderWidth: 2.5
                },
                {
                    label: "Return Score (%)",
                    data: <?= json_encode($ret_data) ?>,
                    borderColor: "#ec4899",
                    tension: 0.35,
                    borderWidth: 2.5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: "bottom" } },
            scales: { y: { min: 60, max: 100, ticks: { callback: (v) => `${v}%` } } }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
