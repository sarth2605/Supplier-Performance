<?php
/**
 * Supplier Performance Analysis System
 * Multi-Supplier Head-to-Head Comparison Matrix
 */

$page_title = "Supplier Comparison";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Fetch all suppliers for selection
$all_suppliers = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

// Get selected supplier IDs (2 to 5 suppliers)
$selected_ids = isset($_GET['ids']) && is_array($_GET['ids']) ? array_map('intval', $_GET['ids']) : [];

// Default selection if none selected: first 3 suppliers
if (empty($selected_ids) && count($all_suppliers) >= 2) {
    $selected_ids = array_slice(array_column($all_suppliers, 'id'), 0, 3);
}

// Fetch performance data for selected suppliers
$compared_suppliers = [];
if (!empty($selected_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $query = "
        SELECT s.*, 
               p.quality_score, p.delivery_score, p.cost_score, p.reliability_score, p.service_score,
               p.defect_rate, p.on_time_delivery, p.overall_score, p.rating, p.remarks
        FROM suppliers s
        LEFT JOIN (
            SELECT p1.* FROM performance p1
            INNER JOIN (
                SELECT supplier_id, MAX(evaluation_date) as max_date FROM performance GROUP BY supplier_id
            ) p2 ON p1.supplier_id = p2.supplier_id AND p1.evaluation_date = p2.max_date
        ) p ON s.id = p.supplier_id
        WHERE s.id IN ($placeholders)
        ORDER BY p.overall_score DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute($selected_ids);
    $compared_suppliers = $stmt->fetchAll();
}

// Best Performer calculations
$best_overall = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'overall_score')) : 0;
$best_quality = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'quality_score')) : 0;
$best_delivery = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'delivery_score')) : 0;
$best_cost = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'cost_score')) : 0;
$best_reliability = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'reliability_score')) : 0;
$best_service = !empty($compared_suppliers) ? max(array_column($compared_suppliers, 'service_score')) : 0;
$lowest_defect = !empty($compared_suppliers) ? min(array_column($compared_suppliers, 'defect_rate')) : 0;

$winner = !empty($compared_suppliers) ? $compared_suppliers[0] : null;

// Prepare Chart.js datasets
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
    $c = $chart_colors[$idx % count($chart_colors)];
    $scores = [
        (float)($cs['quality_score'] ?? 0),
        (float)($cs['delivery_score'] ?? 0),
        (float)($cs['cost_score'] ?? 0),
        (float)($cs['reliability_score'] ?? 0),
        (float)($cs['service_score'] ?? 0)
    ];

    $radar_datasets[] = [
        'label'           => $cs['supplier_name'],
        'data'            => $scores,
        'borderColor'     => $c['border'],
        'backgroundColor' => $c['bg'],
        'borderWidth'     => 2.5,
        'pointBackgroundColor' => $c['border'],
        'pointRadius'     => 4
    ];

    $bar_datasets[] = [
        'label'           => $cs['supplier_name'],
        'data'            => array_merge($scores, [(float)($cs['overall_score'] ?? 0)]),
        'backgroundColor' => $c['border'],
        'borderRadius'    => 4
    ];
}
?>

<!-- Header Toolbar -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Multi-Supplier Comparison Matrix</h1>
        <p class="text-muted small mb-0">Select 2 to 5 vendors for head-to-head parameter benchmarking, comparative radar charts, and decision support.</p>
    </div>
</div>

<!-- Supplier Selection Form Card -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-primary me-2"></i> Choose Suppliers to Compare (Pick 2 to 5)</h6>
    </div>
    <div class="card-saas-body">
        <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="row g-2">
                <?php foreach ($all_suppliers as $s): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="form-check p-2 border rounded-2 bg-light">
                            <input class="form-check-input ms-1 me-2" type="checkbox" name="ids[]" value="<?= $s['id'] ?>" id="sup-check-<?= $s['id'] ?>" <?= in_array((int)$s['id'], $selected_ids, true) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold text-dark text-truncate d-block" for="sup-check-<?= $s['id'] ?>">
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
        Please select at least <strong>2 suppliers</strong> to generate the comparison matrix.
    </div>
<?php else: ?>

<!-- Top Winner Commendation Banner -->
<?php if ($winner): ?>
<div class="alert alert-success d-flex align-items-center gap-3 shadow-sm border-success border-opacity-25 mb-4" role="alert">
    <div class="fs-2 text-warning"><i class="fa-solid fa-trophy"></i></div>
    <div>
        <h6 class="fw-bold mb-0 text-success-emphasis">Top Performing Supplier: <?= htmlspecialchars($winner['supplier_name']) ?> (<?= $winner['overall_score'] ?>%)</h6>
        <p class="small text-muted mb-0">Highest ranked vendor across combined Quality, Delivery, Cost, Reliability, and Service performance factors.</p>
    </div>
</div>
<?php endif; ?>

<!-- Charts Row: Comparison Radar & Grouped Bar Chart -->
<div class="row g-3 mb-4">
    
    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-circle-nodes text-primary me-2"></i> Multi-Vendor Radar Comparison</h6>
            </div>
            <div class="card-saas-body" style="height: 320px;">
                <canvas id="chart-comp-radar"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-column text-primary me-2"></i> Parameter Breakdown by Vendor</h6>
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table text-primary me-2"></i> Comparative Evaluation Matrix</h6>
        <span class="badge bg-success-subtle text-success border border-success-subtle extra-small">Green = Highest Score in Category</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-custom table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width: 220px;">Evaluation Criteria</th>
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
                    <td class="fw-bold text-dark">Overall Performance Score</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['overall_score'] === (float)$best_overall ? 'highlight-winner' : '' ?>">
                            <span class="h5 fw-bold font-monospace d-block mb-1"><?= $cs['overall_score'] ?>%</span>
                            <?= get_rating_badge($cs['rating']) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Quality -->
                <tr>
                    <td class="fw-semibold">Quality Score (Weight: 30%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['quality_score'] === (float)$best_quality ? 'highlight-winner' : '' ?>">
                            <strong><?= $cs['quality_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Delivery -->
                <tr>
                    <td class="fw-semibold">Delivery Score (Weight: 25%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['delivery_score'] === (float)$best_delivery ? 'highlight-winner' : '' ?>">
                            <strong><?= $cs['delivery_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Cost -->
                <tr>
                    <td class="fw-semibold">Cost Score (Weight: 20%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['cost_score'] === (float)$best_cost ? 'highlight-winner' : '' ?>">
                            <strong><?= $cs['cost_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Reliability -->
                <tr>
                    <td class="fw-semibold">Reliability Score (Weight: 15%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['reliability_score'] === (float)$best_reliability ? 'highlight-winner' : '' ?>">
                            <strong><?= $cs['reliability_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Service -->
                <tr>
                    <td class="fw-semibold">Service Score (Weight: 10%)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['service_score'] === (float)$best_service ? 'highlight-winner' : '' ?>">
                            <strong><?= $cs['service_score'] ?>%</strong>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Defect Rate -->
                <tr>
                    <td class="fw-semibold">Defect Rate % (Lower is Better)</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center <?= (float)$cs['defect_rate'] === (float)$lowest_defect ? 'highlight-winner' : ((float)$cs['defect_rate'] > 3.0 ? 'highlight-danger' : '') ?>">
                            <?= $cs['defect_rate'] ?>%
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- On Time Delivery -->
                <tr>
                    <td class="fw-semibold">On-Time Delivery %</td>
                    <?php foreach ($compared_suppliers as $cs): ?>
                        <td class="text-center">
                            <?= $cs['on_time_delivery'] ?>%
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Status -->
                <tr>
                    <td class="fw-semibold">Status</td>
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
    // 1. Radar Chart
    const radarCtx = document.getElementById("chart-comp-radar").getContext("2d");
    new Chart(radarCtx, {
        type: "radar",
        data: {
            labels: ["Quality", "Delivery", "Cost", "Reliability", "Service"],
            datasets: <?= json_encode($radar_datasets) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 11 } } }
            },
            scales: {
                r: { min: 0, max: 100, ticks: { display: false } }
            }
        }
    });

    // 2. Bar Chart
    const barCtx = document.getElementById("chart-comp-bar").getContext("2d");
    new Chart(barCtx, {
        type: "bar",
        data: {
            labels: ["Quality", "Delivery", "Cost", "Reliability", "Service", "Overall Score"],
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
                y: { min: 0, max: 100, ticks: { callback: (v) => `${v}%` } }
            }
        }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
