<?php
/**
 * Supplier Performance Analysis and Management System
 * Domain: Beauty, Cosmetics, Skincare & Personal Care Supply Chain
 * Single Supplier Deep Performance Diagnostic Hub
 */

$page_title = "Supplier Performance Diagnostic";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$supplier_id = (int)($_GET['id'] ?? 0);

// Fetch all suppliers for switcher dropdown
$suppliers = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers ORDER BY supplier_name ASC")->fetchAll();

if ($supplier_id <= 0 && !empty($suppliers)) {
    $supplier_id = (int)$suppliers[0]['id'];
}

// Fetch selected supplier
$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash('danger', 'Cosmetics supplier not found.');
    header("Location: " . BASE_URL . "performance/index.php");
    exit;
}

// Fetch latest performance score
$score_stmt = $db->prepare("SELECT * FROM performance_scores WHERE supplier_id = ? ORDER BY id DESC LIMIT 1");
$score_stmt->execute([$supplier_id]);
$score = $score_stmt->fetch();

// If no score exists yet, automatically recalculate on the fly!
if (!$score) {
    recalculate_supplier_period_score($supplier_id, '2026-Q1');
    $score_stmt->execute([$supplier_id]);
    $score = $score_stmt->fetch();
}

// Fetch historical period scores for trend trajectory
$history_stmt = $db->prepare("SELECT * FROM performance_scores WHERE supplier_id = ? ORDER BY id ASC");
$history_stmt->execute([$supplier_id]);
$history = $history_stmt->fetchAll();

// Fetch summary metrics
$po_summary = $db->prepare("
    SELECT 
        COUNT(id) as total_pos,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_pos,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_pos,
        COALESCE(SUM(total_amount), 0) as total_spent
    FROM purchase_orders WHERE supplier_id = ?
");
$po_summary->execute([$supplier_id]);
$po_stats = $po_summary->fetch();

$deliv_summary = $db->prepare("
    SELECT 
        COUNT(d.id) as total_deliv,
        SUM(CASE WHEN d.delivery_status = 'Delayed' THEN 1 ELSE 0 END) as delayed_deliv,
        AVG(d.delay_days) as avg_delay,
        SUM(d.quantity_received) as total_qty_received
    FROM deliveries d
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    WHERE po.supplier_id = ?
");
$deliv_summary->execute([$supplier_id]);
$del_stats = $deliv_summary->fetch();

$qa_summary = $db->prepare("
    SELECT 
        COALESCE(SUM(qi.quantity_received), 0) as total_qa_received,
        COALESCE(SUM(qi.quantity_defective), 0) as total_qa_defective,
        AVG(qi.defect_rate) as avg_defect_rate,
        AVG(qi.quality_score) as avg_quality_score
    FROM quality_inspections qi
    INNER JOIN deliveries d ON qi.delivery_id = d.id
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    WHERE po.supplier_id = ?
");
$qa_summary->execute([$supplier_id]);
$qa_stats = $qa_summary->fetch();

$ret_summary = $db->prepare("
    SELECT 
        COUNT(id) as total_returns,
        COALESCE(SUM(return_quantity), 0) as total_ret_qty,
        COALESCE(SUM(refund_amount), 0) as total_refund
    FROM returns WHERE supplier_id = ?
");
$ret_summary->execute([$supplier_id]);
$ret_stats = $ret_summary->fetch();

$grade_info = get_supplier_grade($score['overall_score'] ?? 0);

// Radar Chart data
$radar_values = [
    (float)($score['delivery_score'] ?? 100),
    (float)($score['quality_score'] ?? 100),
    (float)($score['fulfillment_score'] ?? 100),
    (float)($score['cost_score'] ?? 95),
    (float)($score['return_score'] ?? 100)
];

// History chart data
$hist_periods = [];
$hist_overall = [];
$hist_delivery = [];
$hist_quality = [];
foreach ($history as $h) {
    $hist_periods[] = $h['period'];
    $hist_overall[] = (float)$h['overall_score'];
    $hist_delivery[] = (float)$h['delivery_score'];
    $hist_quality[] = (float)$h['quality_score'];
}
?>

<!-- Header with Supplier Switcher -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($supplier['supplier_name']) ?></h1>
        <p class="text-muted small mb-0"><code class="text-primary font-monospace fw-bold"><?= htmlspecialchars($supplier['supplier_code']) ?></code> &bull; <?= htmlspecialchars($supplier['category']) ?> &bull; <?= htmlspecialchars($supplier['city']) ?>, <?= htmlspecialchars($supplier['state']) ?></p>
    </div>
    
    <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="d-flex align-items-center gap-2">
        <label class="small fw-semibold text-secondary text-nowrap mb-0">Switch Supplier:</label>
        <select name="id" class="form-select form-select-sm" style="min-width: 240px;" onchange="this.form.submit()">
            <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $supplier_id === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['supplier_code']) ?> - <?= htmlspecialchars($s['supplier_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Top Diagnostic Row: Circular Gauge & 5 Factors -->
<div class="row g-3 mb-4">
    
    <!-- 1. Circular Score Gauge Card -->
    <div class="col-12 col-md-4 col-xl-3">
        <div class="card-saas h-100 text-center p-4 d-flex flex-column align-items-center justify-content-center">
            <span class="text-uppercase extra-small fw-bold text-muted mb-2">Overall Score (Period: <?= htmlspecialchars($score['period']) ?>)</span>
            <div class="circular-score-badge my-2" style="border-color: <?= $grade_info['color'] ?>; color: <?= $grade_info['color'] ?>; background: <?= $grade_info['bg'] ?>;">
                <?= $score['overall_score'] ?>%
            </div>
            <div class="mt-2">
                <?= get_grade_badge($score['grade']) ?>
                <span class="fw-bold ms-1" style="color: <?= $grade_info['color'] ?>;"><?= $grade_info['title'] ?></span>
            </div>
            <p class="extra-small text-muted mt-2 mb-0"><?= $grade_info['desc'] ?></p>
        </div>
    </div>

    <!-- 2. The 5 Weighted Criteria Cards -->
    <div class="col-12 col-md-8 col-xl-9">
        <div class="row g-3 h-100">
            <div class="col-6 col-sm">
                <div class="kpi-card h-100" style="--kpi-color: #2563eb;">
                    <span class="text-uppercase extra-small fw-bold text-muted">1. Delivery (30%)</span>
                    <h3 class="fw-bold my-1 text-dark"><?= $score['delivery_score'] ?>%</h3>
                    <div class="score-progress mt-2"><div class="score-progress-bar bg-primary" style="width: <?= $score['delivery_score'] ?>%;"></div></div>
                    <span class="extra-small text-muted mt-1 d-block"><?= (int)$del_stats['delayed_deliv'] ?> Delayed shipments</span>
                </div>
            </div>

            <div class="col-6 col-sm">
                <div class="kpi-card h-100" style="--kpi-color: #10b981;">
                    <span class="text-uppercase extra-small fw-bold text-muted">2. Quality (30%)</span>
                    <h3 class="fw-bold my-1 text-dark"><?= $score['quality_score'] ?>%</h3>
                    <div class="score-progress mt-2"><div class="score-progress-bar bg-success" style="width: <?= $score['quality_score'] ?>%;"></div></div>
                    <span class="extra-small text-muted mt-1 d-block">Defect Rate: <?= round(floatval($qa_stats['avg_defect_rate'] ?? 0), 2) ?>%</span>
                </div>
            </div>

            <div class="col-6 col-sm">
                <div class="kpi-card h-100" style="--kpi-color: #8b5cf6;">
                    <span class="text-uppercase extra-small fw-bold text-muted">3. Fulfillment (20%)</span>
                    <h3 class="fw-bold my-1 text-dark"><?= $score['fulfillment_score'] ?>%</h3>
                    <div class="score-progress mt-2"><div class="score-progress-bar bg-purple" style="width: <?= $score['fulfillment_score'] ?>%;"></div></div>
                    <span class="extra-small text-muted mt-1 d-block">Delivered / Ordered Qty</span>
                </div>
            </div>

            <div class="col-6 col-sm">
                <div class="kpi-card h-100" style="--kpi-color: #f59e0b;">
                    <span class="text-uppercase extra-small fw-bold text-muted">4. Cost (10%)</span>
                    <h3 class="fw-bold my-1 text-dark"><?= $score['cost_score'] ?>%</h3>
                    <div class="score-progress mt-2"><div class="score-progress-bar bg-warning" style="width: <?= $score['cost_score'] ?>%;"></div></div>
                    <span class="extra-small text-muted mt-1 d-block">Standard Price Ratio</span>
                </div>
            </div>

            <div class="col-12 col-sm">
                <div class="kpi-card h-100" style="--kpi-color: #ec4899;">
                    <span class="text-uppercase extra-small fw-bold text-muted">5. Returns (10%)</span>
                    <h3 class="fw-bold my-1 text-dark"><?= $score['return_score'] ?>%</h3>
                    <div class="score-progress mt-2"><div class="score-progress-bar bg-pink" style="width: <?= $score['return_score'] ?>%;"></div></div>
                    <span class="extra-small text-muted mt-1 d-block"><?= (int)$ret_stats['total_ret_qty'] ?> Units Returned</span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Charts Section: Radar & Period History Line Graph -->
<div class="row g-3 mb-4">
    
    <div class="col-12 col-lg-5">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-spider text-primary me-2"></i> 5-Factor Performance Radar</h6>
            </div>
            <div class="card-saas-body" style="height: 300px;">
                <canvas id="chart-supplier-radar"></canvas>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Period Score Progression</h6>
            </div>
            <div class="card-saas-body" style="height: 300px;">
                <canvas id="chart-supplier-history"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Automated Decision Support Strategy Box -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-brain text-primary me-2"></i> Executive Decision Support & Action Plan</h6>
    </div>
    <div class="card-saas-body">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="p-3 bg-light rounded-3 border-start border-success border-4 h-100">
                    <h6 class="fw-bold text-success small mb-1"><i class="fa-solid fa-circle-check me-1"></i> Strongest Dimension</h6>
                    <p class="small text-muted mb-0">
                        <?php
                        $max_score = max($score['delivery_score'], $score['quality_score'], $score['fulfillment_score'], $score['cost_score'], $score['return_score']);
                        if ($max_score === $score['quality_score']) echo "Flawless batch quality compliance and low non-conformance rate.";
                        elseif ($max_score === $score['delivery_score']) echo "Exceptional shipment punctuality and strict logistics milestone adherence.";
                        elseif ($max_score === $score['fulfillment_score']) echo "High full-order dispatch fill-rate.";
                        elseif ($max_score === $score['cost_score']) echo "Highly competitive unit pricing against standard benchmarks.";
                        else echo "Negligible return and transit damage rate.";
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 bg-light rounded-3 border-start border-warning border-4 h-100">
                    <h6 class="fw-bold text-warning-emphasis small mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Area for Improvement</h6>
                    <p class="small text-muted mb-0">
                        <?php
                        $min_score = min($score['delivery_score'], $score['quality_score'], $score['fulfillment_score'], $score['cost_score'], $score['return_score']);
                        if ($min_score === $score['delivery_score']) echo "Logistics transit times and carrier delay occurrences require tighter monitoring.";
                        elseif ($min_score === $score['quality_score']) echo "Incoming inspection defect rates should be flagged for vendor audit.";
                        elseif ($min_score === $score['fulfillment_score']) echo "Address order short-shipments and backorder delays.";
                        elseif ($min_score === $score['cost_score']) echo "Unit contract pricing is higher than industry standard benchmark costs.";
                        else echo "Packaging durability during transit should be reinforced.";
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 bg-light rounded-3 border-start border-primary border-4 h-100">
                    <h6 class="fw-bold text-primary small mb-1"><i class="fa-solid fa-compass me-1"></i> Procurement Recommendation</h6>
                    <p class="small text-dark fw-semibold mb-0"><?= $grade_info['desc'] ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Radar Chart
    const radarCtx = document.getElementById("chart-supplier-radar").getContext("2d");
    new Chart(radarCtx, {
        type: "radar",
        data: {
            labels: ["Delivery (30%)", "Quality (30%)", "Fulfillment (20%)", "Cost (10%)", "Returns (10%)"],
            datasets: [{
                label: "<?= htmlspecialchars($supplier['supplier_name']) ?>",
                data: <?= json_encode($radar_values) ?>,
                backgroundColor: "rgba(37, 99, 235, 0.25)",
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
                    min: 60,
                    max: 100,
                    ticks: { stepSize: 10, display: false },
                    pointLabels: { font: { size: 11, weight: "600" } }
                }
            }
        }
    });

    // 2. Progression Line Chart
    const lineCtx = document.getElementById("chart-supplier-history").getContext("2d");
    new Chart(lineCtx, {
        type: "line",
        data: {
            labels: <?= json_encode($hist_periods) ?>,
            datasets: [
                {
                    label: "Overall Score (%)",
                    data: <?= json_encode($hist_overall) ?>,
                    borderColor: "#10b981",
                    backgroundColor: "rgba(16, 185, 129, 0.12)",
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointRadius: 5
                },
                {
                    label: "Quality (%)",
                    data: <?= json_encode($hist_quality) ?>,
                    borderColor: "#2563eb",
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2
                },
                {
                    label: "Delivery (%)",
                    data: <?= json_encode($hist_delivery) ?>,
                    borderColor: "#f59e0b",
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2
                }
            ]
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
