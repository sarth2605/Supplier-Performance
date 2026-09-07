<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Multi-Supplier Comparison Hub & Parameter Benchmarking
 */

$page_title = "Supplier Comparison";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Fetch all suppliers for checkboxes
$all_suppliers = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

$selected_ids = isset($_GET['ids']) && is_array($_GET['ids']) ? array_map('intval', $_GET['ids']) : [];

// Default to first 3 suppliers if none selected
if (empty($selected_ids) && count($all_suppliers) >= 2) {
    $selected_ids = array_slice(array_column($all_suppliers, 'id'), 0, 3);
}

$compared_suppliers = [];
if (!empty($selected_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $query = "
        SELECT s.*, 
               ps.delivery_score, ps.quality_score, ps.fulfillment_score, ps.cost_score, ps.return_score, ps.overall_score, ps.grade, ps.period
        FROM suppliers s
        LEFT JOIN (
            SELECT ps1.* FROM performance_scores ps1
            INNER JOIN (
                SELECT supplier_id, MAX(id) as max_id FROM performance_scores GROUP BY supplier_id
            ) ps2 ON ps1.id = ps2.max_id
        ) ps ON s.id = ps.supplier_id
        WHERE s.id IN ($placeholders)
        ORDER BY ps.overall_score DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute($selected_ids);
    $compared_suppliers = $stmt->fetchAll();
}

$best_overall = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'overall_score')) : 0;
$best_delivery = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'delivery_score')) : 0;
$best_quality = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'quality_score')) : 0;
$best_fulf = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'fulfillment_score')) : 0;
$best_cost = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'cost_score')) : 0;
$best_ret = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'return_score')) : 0;

$winner = !empty($compared_suppliers) ? $compared_suppliers[0] : null;

// Chart dataset construction
$chart_colors = [
    ['border' => '#2563eb', 'bg' => 'rgba(37, 99, 235, 0.2)'],
    ['border' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.2)'],
    ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.2)'],
    ['border' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.2)'],
    ['border' => '#ec4899', 'bg' => 'rgba(236, 72, 153, 0.2)']
];

$radar_datasets = [];
$bar_datasets = [];

foreach ($compared_suppliers as $idx => $cs) {
    $col = $chart_colors[$idx % count($chart_colors)];
    $metrics = [
        (float)($cs['delivery_score'] ?? 0),
        (float)($cs['quality_score'] ?? 0),
        (float)($cs['fulfillment_score'] ?? 0),
        (float)($cs['cost_score'] ?? 0),
        (float)($cs['return_score'] ?? 0)
    ];

    $radar_datasets[] = [
        'label' => $cs['supplier_name'],
        'data'  => $metrics,
        'borderColor' => $col['border'],
        'backgroundColor' => $col['bg'],
        'borderWidth' => 2.5,
        'pointBackgroundColor' => $col['border'],
        'pointRadius' => 4
    ];

    $bar_datasets[] = [
        'label' => $cs['supplier_name'],
        'data'  => array_merge($metrics, [(float)($cs['overall_score'] ?? 0)]),
        'backgroundColor' => $col['border'],
        'borderRadius' => 4
    ];
}
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Cosmetics Supplier Comparison Hub</h1>
        <p class="text-muted small mb-0">Select 2 to 5 beauty suppliers for head-to-head benchmarking across 5 core performance parameters.</p>
    </div>
</div>

<!-- Selector Form Card -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> Select Cosmetics Suppliers to Compare (Pick 2 to 5)</h6>
    </div>
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="row g-2">
                <?php foreach ($all_suppliers as $s): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="form-check p-2 border rounded-2 bg-light">
                            <input class="form-check-input ms-1 me-2" type="checkbox" name="ids[]" value="<?= $s['id'] ?>" id="sup-chk-<?= $s['id'] ?>" <?= in_array((int)$s['id'], $selected_ids, true) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold text-dark text-truncate d-block" for="sup-chk-<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['supplier_name']) ?> <span class="text-muted extra-small">(<?= htmlspecialchars($s['category']) ?>)</span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                    <i class="fa-solid fa-scale-balanced me-1"></i> Update Comparison Matrix
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (count($compared_suppliers) < 2): ?>
    <div class="alert alert-warning text-center py-4">
        <i class="fa-solid fa-triangle-exclamation fs-3 d-block mb-2"></i>
        Please select at least <strong>2 suppliers</strong> to generate the comparative analysis.
    </div>
<?php else: ?>

<!-- Winner Commendation Banner -->
<?php if ($winner): ?>
<div class="alert alert-success d-flex align-items-center gap-3 shadow-sm border-success border-opacity-25 mb-4" role="alert">
    <div class="fs-2 text-warning"><i class="fa-solid fa-trophy"></i></div>
    <div>
        <h6 class="fw-bold mb-0 text-success-emphasis">Best Performing Partner: <?= htmlspecialchars($winner['supplier_name']) ?> (<?= $winner['overall_score'] ?>% &bull; Grade <?= $winner['grade'] ?>)</h6>
        <p class="small text-muted mb-0">Highest rated vendor across combined Delivery, Quality, Fulfillment, Cost, and Low Return metrics.</p>
    </div>
</div>
<?php endif; ?>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-circle-nodes text-primary me-2"></i> 5-Factor Comparative Radar</h6>
            </div>
            <div class="card-saas-body" style="height: 320px;">
                <canvas id="chart-comp-radar"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-column text-primary me-2"></i> Metric Comparison by Vendor</h6>
            </div>
            <div class="card-saas-body" style="height: 320px;">
                <canvas id="chart-comp-bar"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Comparative Table Matrix -->
<div class="card-saas mb-4">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table text-primary me-2"></i> Comparative Benchmark Matrix</h6>
        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">Green Highlight = Best Score in Dimension</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width: 240px;">Performance Criterion</th>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <th class="text-center" style="min-width: 180px;">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($cs['supplier_name']) ?></div>
                            <div class="extra-small text-muted font-monospace"><?= htmlspecialchars($cs['supplier_code']) ?> &bull; <?= htmlspecialchars($cs['category']) ?></div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                
                <!-- Overall Score -->
                <tr class="table-light">
                    <td class="fw-bold text-dark">Overall Weighted Score</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['overall_score'] === (float)$best_overall ? 'bg-success bg-opacity-10' : '' ?>">
                            <span class="h5 fw-bold font-monospace d-block mb-1"><?= $cs['overall_score'] ?>%</span>
                            <?= get_grade_badge($cs['grade']) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Delivery -->
                <tr>
                    <td class="fw-semibold">1. Delivery Score (30%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['delivery_score'] === (float)$best_delivery ? 'bg-success bg-opacity-10' : '' ?>">
                            <strong><?= $cs['delivery_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Quality -->
                <tr>
                    <td class="fw-semibold">2. Quality Score (30%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['quality_score'] === (float)$best_quality ? 'bg-success bg-opacity-10' : '' ?>">
                            <strong><?= $cs['quality_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Fulfillment -->
                <tr>
                    <td class="fw-semibold">3. Fulfillment Rate (20%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['fulfillment_score'] === (float)$best_fulf ? 'bg-success bg-opacity-10' : '' ?>">
                            <strong><?= $cs['fulfillment_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Cost -->
                <tr>
                    <td class="fw-semibold">4. Cost Score (10%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['cost_score'] === (float)$best_cost ? 'bg-success bg-opacity-10' : '' ?>">
                            <strong><?= $cs['cost_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Returns -->
                <tr>
                    <td class="fw-semibold">5. Return Score (10%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['return_score'] === (float)$best_ret ? 'bg-success bg-opacity-10' : '' ?>">
                            <strong><?= $cs['return_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Status -->
                <tr>
                    <td class="fw-semibold">Operational Status</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center">
                            <?= get_status_badge($cs['status']) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Radar
    const radarCtx = document.getElementById("chart-comp-radar").getContext("2d");
    new Chart(radarCtx, {
        type: "radar",
        data: {
            labels: ["Delivery (30%)", "Quality (30%)", "Fulfillment (20%)", "Cost (10%)", "Returns (10%)"],
            datasets: <?= json_encode($radar_datasets) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 11 } } }
            },
            scales: {
                r: { min: 60, max: 100, ticks: { display: false } }
            }
        }
    });

    // 2. Bar
    const barCtx = document.getElementById("chart-comp-bar").getContext("2d");
    new Chart(barCtx, {
        type: "bar",
        data: {
            labels: ["Delivery", "Quality", "Fulfillment", "Cost", "Returns", "Overall"],
            datasets: <?= json_encode($bar_datasets) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 11 } } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { min: 60, max: 100, ticks: { callback: (v) => `${v}%` } }
            }
        }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
