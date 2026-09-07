<?php
/**
 * Supplier Performance Analysis and Management System
 * System Settings & Performance Weights Configuration
 */

$page_title = "System Settings";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin']);

$db = get_db();
$errors = [];

// Fetch current settings
$settings_raw = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $company_name   = trim($_POST['company_name'] ?? 'Supplier Performance Analysis System');
        $currency       = trim($_POST['currency'] ?? '$');
        $current_period = trim($_POST['current_period'] ?? '2026-Q1');
        
        $w_delivery     = floatval($_POST['weight_delivery'] ?? 0.30);
        $w_quality      = floatval($_POST['weight_quality'] ?? 0.30);
        $w_cost         = floatval($_POST['weight_cost'] ?? 0.20);
        $w_reliability  = floatval($_POST['weight_reliability'] ?? 0.20);

        $weight_sum = round($w_delivery + $w_quality + $w_cost + $w_reliability, 4);

        if ($weight_sum !== 1.0000 && $weight_sum !== 1.0) {
            $errors[] = "The sum of all 4 performance weights must equal exactly 1.00 (100%). Current sum: " . ($weight_sum * 100) . "%.";
        }

        if (empty($errors)) {
            try {
                $updates = [
                    'company_name'       => $company_name,
                    'currency'           => $currency,
                    'current_period'     => $current_period,
                    'weight_delivery'    => (string)$w_delivery,
                    'weight_quality'     => (string)$w_quality,
                    'weight_cost'        => (string)$w_cost,
                    'weight_reliability' => (string)$w_reliability
                ];

                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                foreach ($updates as $k => $v) {
                    $stmt->execute([$k, $v]);
                }

                log_activity($_SESSION['user_id'], 'Settings Updated', 'Settings', null, "Updated performance weights (D: $w_delivery, Q: $w_quality, C: $w_cost, R: $w_reliability)");
                set_flash('success', "System settings and scoring weight models updated successfully.");
                header("Location: " . BASE_URL . "settings/index.php");
                exit;

            } catch (Exception $e) {
                $errors[] = "Save failed: " . $e->getMessage();
            }
        }
    }
}

$c_name = $settings_raw['company_name'] ?? 'Supplier Performance Analysis System';
$curr   = $settings_raw['currency'] ?? '$';
$c_per  = $settings_raw['current_period'] ?? '2026-Q1';
$w_d    = floatval($settings_raw['weight_delivery'] ?? 0.30);
$w_q    = floatval($settings_raw['weight_quality'] ?? 0.30);
$w_c    = floatval($settings_raw['weight_cost'] ?? 0.20);
$w_r    = floatval($settings_raw['weight_reliability'] ?? 0.20);
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">System Settings & Algorithm Weights</h1>
        <p class="text-muted small mb-0">Tune multi-criteria weight distribution and corporate supply chain scoring parameters.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Configuration Error:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" id="settings-form">
    <?= csrf_input() ?>

    <div class="row g-4">
        
        <!-- Left: Performance Scoring Weight Tuner -->
        <div class="col-12 col-lg-6">
            <div class="card-saas h-100">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-sliders text-primary me-2"></i> Performance Scoring Weights (Must Sum to 100%)</h6>
                </div>
                <div class="card-saas-body">
                    
                    <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-center border">
                        <div>
                            <span class="extra-small text-uppercase fw-bold text-muted d-block">Cumulative Weight Total</span>
                            <div class="h4 fw-bold font-monospace mb-0" id="total-weight-display">100%</div>
                        </div>
                        <div>
                            <span class="badge bg-success fs-6 px-3 py-1" id="weight-valid-badge">Valid (1.00)</span>
                        </div>
                    </div>

                    <!-- 1. Delivery Weight -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-semibold text-secondary">Delivery Punctuality Weight</label>
                            <span class="small fw-bold font-monospace text-primary" id="val-d"><?= ($w_d * 100) ?>%</span>
                        </div>
                        <input type="range" class="form-range weight-slider" id="slider-d" name="weight_delivery" min="0.05" max="0.60" step="0.05" value="<?= $w_d ?>">
                    </div>

                    <!-- 2. Quality Weight -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-semibold text-secondary">Quality & Defect Resistance Weight</label>
                            <span class="small fw-bold font-monospace text-success" id="val-q"><?= ($w_q * 100) ?>%</span>
                        </div>
                        <input type="range" class="form-range weight-slider" id="slider-q" name="weight_quality" min="0.05" max="0.60" step="0.05" value="<?= $w_q ?>">
                    </div>

                    <!-- 3. Cost Weight -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-semibold text-secondary">Cost Competitiveness Weight</label>
                            <span class="small fw-bold font-monospace text-warning" id="val-c"><?= ($w_c * 100) ?>%</span>
                        </div>
                        <input type="range" class="form-range weight-slider" id="slider-c" name="weight_cost" min="0.05" max="0.60" step="0.05" value="<?= $w_c ?>">
                    </div>

                    <!-- 4. Reliability Weight -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-semibold text-secondary">Fulfillment Reliability Weight</label>
                            <span class="small fw-bold font-monospace text-purple" id="val-r"><?= ($w_r * 100) ?>%</span>
                        </div>
                        <input type="range" class="form-range weight-slider" id="slider-r" name="weight_reliability" min="0.05" max="0.60" step="0.05" value="<?= $w_r ?>">
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: General Platform Settings -->
        <div class="col-12 col-lg-6">
            <div class="card-saas h-100">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-gear text-primary me-2"></i> General System Configuration</h6>
                </div>
                <div class="card-saas-body">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Application / Company Title</label>
                        <input type="text" name="company_name" class="form-control form-control-sm" value="<?= htmlspecialchars($c_name) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Currency Symbol</label>
                            <input type="text" name="currency" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($curr) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Active Evaluation Period</label>
                            <input type="text" name="current_period" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($c_per) ?>" required>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark small mb-2 mt-4"><i class="fa-solid fa-graduation-cap text-primary me-1"></i> Academic Grading Reference</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered extra-small mb-0">
                            <thead class="table-light">
                                <tr><th>Score Range</th><th>Grade</th><th>Classification</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>90% – 100%</td><td><span class="badge bg-success">A+</span></td><td>Excellent Preferred Partner</td></tr>
                                <tr><td>80% – 89%</td><td><span class="badge bg-primary">A</span></td><td>Good Qualified Supplier</td></tr>
                                <tr><td>70% – 79%</td><td><span class="badge bg-warning text-dark">B</span></td><td>Average Performance</td></tr>
                                <tr><td>60% – 69%</td><td><span class="badge bg-orange text-white">C</span></td><td>Needs Improvement / CAP</td></tr>
                                <tr><td>&lt; 60%</td><td><span class="badge bg-danger">D</span></td><td>Poor / Vendor Disqualification</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="save-settings-btn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Configuration
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</form>

<script>
function updateSliders() {
    const d = parseFloat(document.getElementById("slider-d").value);
    const q = parseFloat(document.getElementById("slider-q").value);
    const c = parseFloat(document.getElementById("slider-c").value);
    const r = parseFloat(document.getElementById("slider-r").value);

    document.getElementById("val-d").textContent = Math.round(d * 100) + "%";
    document.getElementById("val-q").textContent = Math.round(q * 100) + "%";
    document.getElementById("val-c").textContent = Math.round(c * 100) + "%";
    document.getElementById("val-r").textContent = Math.round(r * 100) + "%";

    const total = Math.round((d + q + c + r) * 100);
    const display = document.getElementById("total-weight-display");
    const badge = document.getElementById("weight-valid-badge");
    const saveBtn = document.getElementById("save-settings-btn");

    display.textContent = total + "%";

    if (total === 100) {
        display.className = "h4 fw-bold font-monospace text-success mb-0";
        badge.className = "badge bg-success fs-6 px-3 py-1";
        badge.textContent = "Valid (1.00)";
        saveBtn.disabled = false;
    } else {
        display.className = "h4 fw-bold font-monospace text-danger mb-0";
        badge.className = "badge bg-danger fs-6 px-3 py-1";
        badge.textContent = `Sum is ${total}% (Need 100%)`;
        saveBtn.disabled = true;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".weight-slider").forEach(sl => {
        sl.addEventListener("input", updateSliders);
    });
    updateSliders();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
