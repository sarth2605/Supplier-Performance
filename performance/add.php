<?php
/**
 * Supplier Performance Analysis System
 * Record Performance Evaluation & Live Formula Calculator
 */

$page_title = "Record Performance Evaluation";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$selected_supplier_id = (int)($_GET['supplier_id'] ?? 0);
$weights = get_system_weights();
$errors = [];

// Fetch suppliers for dropdown
$suppliers_stmt = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers WHERE status != 'Inactive' ORDER BY supplier_name ASC");
$suppliers = $suppliers_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $eval_date   = trim($_POST['evaluation_date'] ?? date('Y-m-d'));
        $quality     = floatval($_POST['quality_score'] ?? 0);
        $delivery    = floatval($_POST['delivery_score'] ?? 0);
        $cost        = floatval($_POST['cost_score'] ?? 0);
        $reliability = floatval($_POST['reliability_score'] ?? 0);
        $service     = floatval($_POST['service_score'] ?? 0);
        $defect_rate = floatval($_POST['defect_rate'] ?? 0);
        $on_time     = floatval($_POST['on_time_delivery'] ?? 100);
        $remarks     = trim($_POST['remarks'] ?? '');

        if ($supplier_id <= 0) $errors[] = "Please select a supplier to evaluate.";
        if ($quality < 0 || $quality > 100) $errors[] = "Quality Score must be between 0 and 100.";
        if ($delivery < 0 || $delivery > 100) $errors[] = "Delivery Score must be between 0 and 100.";
        if ($cost < 0 || $cost > 100) $errors[] = "Cost Score must be between 0 and 100.";
        if ($reliability < 0 || $reliability > 100) $errors[] = "Reliability Score must be between 0 and 100.";
        if ($service < 0 || $service > 100) $errors[] = "Service Score must be between 0 and 100.";

        if (empty($errors)) {
            // Calculate overall score on the server
            $overall_score = calculate_overall_score($quality, $delivery, $cost, $reliability, $service, $weights);
            $tier_info = get_rating_tier($overall_score);

            try {
                $stmt = $db->prepare("
                    INSERT INTO performance (
                        supplier_id, evaluation_date, evaluator_id, quality_score, delivery_score,
                        cost_score, reliability_score, service_score, defect_rate, on_time_delivery,
                        overall_score, rating, remarks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $supplier_id,
                    $eval_date,
                    $_SESSION['user_id'],
                    $quality,
                    $delivery,
                    $cost,
                    $reliability,
                    $service,
                    $defect_rate,
                    $on_time,
                    $overall_score,
                    $tier_info['tier'],
                    $remarks
                ]);

                // Fetch supplier details for alert
                $sup_stmt = $db->prepare("SELECT supplier_name, supplier_code FROM suppliers WHERE id = ?");
                $sup_stmt->execute([$supplier_id]);
                $sup = $sup_stmt->fetch();
                $sup_name = $sup['supplier_name'] ?? 'Supplier';

                // Automated Risk Notifications
                if ($overall_score < 70) {
                    create_notification($_SESSION['user_id'], 'Low Score Alert: ' . $sup_name, "$sup_name scored $overall_score% ({$tier_info['tier']}). Corrective Action Plan required.", 'Critical');
                }
                if ($defect_rate > 3.0) {
                    create_notification($_SESSION['user_id'], 'High Defect Rate: ' . $sup_name, "$sup_name recorded a $defect_rate% defect rate, exceeding the 3.0% threshold.", 'Warning');
                }

                log_activity($_SESSION['user_id'], 'Performance Evaluated', "Recorded evaluation for $sup_name. Score: $overall_score% ({$tier_info['tier']})");

                set_flash('success', "Evaluation for '$sup_name' saved! Overall Score: {$overall_score}% ({$tier_info['tier']})");
                header("Location: " . BASE_URL . "suppliers/view.php?id=" . $supplier_id);
                exit;

            } catch (Exception $e) {
                $errors[] = "Database insertion failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Performance Evaluation & Calculator</h1>
        <p class="text-muted small mb-0">Record multi-attribute evaluations using the weighted formula engine with live score preview.</p>
    </div>
    <a href="<?= BASE_URL ?>performance/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Evaluations
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Errors:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" id="eval-form">
    <?= csrf_input() ?>

    <div class="row g-4">
        
        <!-- Left: Form Controls & Sliders -->
        <div class="col-12 col-lg-7">
            <div class="card-saas">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-sliders text-primary me-2"></i> Evaluation Parameters (0–100)</h6>
                </div>
                <div class="card-saas-body">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label small fw-semibold text-secondary">Target Supplier *</label>
                            <select name="supplier_id" class="form-select form-select-sm" required>
                                <option value="">-- Choose Supplier --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($selected_supplier_id === (int)$s['id'] || (isset($_POST['supplier_id']) && (int)$_POST['supplier_id'] === (int)$s['id'])) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['supplier_code']) ?> - <?= htmlspecialchars($s['supplier_name']) ?> (<?= htmlspecialchars($s['category']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Evaluation Date *</label>
                            <input type="date" name="evaluation_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- 1. Quality Slider -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">1. Quality Score (Weight: <?= round($weights['quality']*100) ?>%)</label>
                            <span class="badge bg-primary fs-6 font-monospace" id="val-quality">90%</span>
                        </div>
                        <input type="range" class="form-range" name="quality_score" id="input-quality" min="0" max="100" value="90">
                    </div>

                    <!-- 2. Delivery Slider -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">2. Delivery Score (Weight: <?= round($weights['delivery']*100) ?>%)</label>
                            <span class="badge bg-primary fs-6 font-monospace" id="val-delivery">85%</span>
                        </div>
                        <input type="range" class="form-range" name="delivery_score" id="input-delivery" min="0" max="100" value="85">
                    </div>

                    <!-- 3. Cost Slider -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">3. Cost Score (Weight: <?= round($weights['cost']*100) ?>%)</label>
                            <span class="badge bg-primary fs-6 font-monospace" id="val-cost">88%</span>
                        </div>
                        <input type="range" class="form-range" name="cost_score" id="input-cost" min="0" max="100" value="88">
                    </div>

                    <!-- 4. Reliability Slider -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">4. Reliability Score (Weight: <?= round($weights['reliability']*100) ?>%)</label>
                            <span class="badge bg-primary fs-6 font-monospace" id="val-reliability">92%</span>
                        </div>
                        <input type="range" class="form-range" name="reliability_score" id="input-reliability" min="0" max="100" value="92">
                    </div>

                    <!-- 5. Service Slider -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-dark mb-0">5. Service Score (Weight: <?= round($weights['service']*100) ?>%)</label>
                            <span class="badge bg-primary fs-6 font-monospace" id="val-service">90%</span>
                        </div>
                        <input type="range" class="form-range" name="service_score" id="input-service" min="0" max="100" value="90">
                    </div>

                    <!-- Metrics & Remarks -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Defect Rate % (0–100%)</label>
                            <input type="number" step="0.1" min="0" max="100" name="defect_rate" id="input-defect" class="form-control form-control-sm" value="0.8">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">On-Time Delivery Rate %</label>
                            <input type="number" step="0.1" min="0" max="100" name="on_time_delivery" id="input-ontime" class="form-control form-control-sm" value="98.0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Evaluation Remarks / Audit Observations</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Enter specific inspection notes, ISO compliance observations, batch details..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="reset" class="btn btn-light border flex-grow-1">Reset</button>
                        <button type="submit" class="btn btn-primary flex-grow-1 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Submit & Save Evaluation</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Live Score Preview Engine & Benchmark Scale -->
        <div class="col-12 col-lg-5">
            
            <!-- Real-Time Score Engine -->
            <div class="card-saas mb-4">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calculator text-primary me-2"></i> Real-Time Score Engine</h6>
                </div>
                <div class="card-saas-body">
                    
                    <div class="p-3 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(139, 92, 246, 0.08)); border: 1px solid rgba(37, 99, 235, 0.2);">
                        <div>
                            <span class="extra-small text-uppercase fw-bold text-muted d-block">Overall Score</span>
                            <div class="h2 fw-bold mb-0 font-monospace" id="preview-overall-score">88.65%</div>
                        </div>
                        <div>
                            <span class="badge badge-tier-good fs-6 px-3 py-2" id="preview-rating-badge">Good</span>
                        </div>
                    </div>

                    <div class="p-2 bg-light rounded-2 border extra-small text-muted mb-3 font-monospace" id="preview-formula-text">
                        (90 × 30%) + (85 × 25%) + (88 × 20%) + (92 × 15%) + (90 × 10%) = <strong>88.65%</strong>
                    </div>

                    <h6 class="fw-bold text-dark small mb-1"><i class="fa-solid fa-brain text-primary me-1"></i> Live Recommendation:</h6>
                    <p class="small text-muted p-2 bg-light rounded-2 border-start border-primary border-3" id="preview-recommendation">
                        Loading strategic analysis...
                    </p>

                </div>
            </div>

            <!-- Rating Benchmark Scale Reference -->
            <div class="card-saas">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-table-cells text-primary me-2"></i> Rating Benchmark System</h6>
                </div>
                <div class="card-saas-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="badge badge-tier-excellent">90 – 100%</span>
                            <span class="fw-semibold text-dark">Excellent (Category Leader)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="badge badge-tier-good">80 – 89%</span>
                            <span class="fw-semibold text-dark">Good (Reliable Standard)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="badge badge-tier-average">70 – 79%</span>
                            <span class="fw-semibold text-dark">Average (Needs Attention)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="badge badge-tier-poor">60 – 69%</span>
                            <span class="fw-semibold text-dark">Poor (CAP Mandate)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="badge badge-tier-critical">&lt; 60%</span>
                            <span class="fw-semibold text-danger">Critical (Decommission Risk)</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

    </div>
</form>

<script src="<?= BASE_URL ?>assets/js/performance.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    setupPerformanceCalculator(<?= json_encode($weights) ?>);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
