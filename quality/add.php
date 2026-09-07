<?php
/**
 * Supplier Performance Analysis and Management System
 * Record Quality Inspection with Automated Defect Rate Engine
 */

$page_title = "Record Quality Inspection";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$selected_delivery_id = (int)($_GET['delivery_id'] ?? 0);
$errors = [];

// Fetch deliveries that do not have a quality inspection record yet
$uninspected_deliveries = $db->query("
    SELECT d.id, d.delivery_date, d.quantity_received,
           po.po_number, s.id as supplier_id, s.supplier_name, s.supplier_code
    FROM deliveries d
    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN quality_inspections qi ON d.id = qi.delivery_id
    WHERE qi.id IS NULL
    ORDER BY d.delivery_date DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $delivery_id      = (int)($_POST['delivery_id'] ?? 0);
        $inspection_date  = trim($_POST['inspection_date'] ?? date('Y-m-d'));
        $qty_received     = (int)($_POST['quantity_received'] ?? 0);
        $qty_accepted     = (int)($_POST['quantity_accepted'] ?? 0);
        $qty_defective    = (int)($_POST['quantity_defective'] ?? 0);
        $qty_rejected     = (int)($_POST['quantity_rejected'] ?? 0);
        $remarks          = trim($_POST['remarks'] ?? '');

        if ($delivery_id <= 0) $errors[] = "Please select a valid delivery shipment to inspect.";
        if (empty($inspection_date)) $errors[] = "Inspection date is required.";
        if ($qty_received <= 0) $errors[] = "Quantity received must be greater than zero.";
        if ($qty_defective < 0 || $qty_accepted < 0 || $qty_rejected < 0) {
            $errors[] = "Quantities cannot be negative numbers.";
        }
        if ($qty_defective > $qty_received) {
            $errors[] = "Defective quantity ($qty_defective) cannot exceed total received quantity ($qty_received).";
        }
        if (($qty_accepted + $qty_rejected) > $qty_received) {
            $errors[] = "Sum of accepted ($qty_accepted) and rejected ($qty_rejected) exceeds received quantity ($qty_received).";
        }

        if (empty($errors)) {
            // Apply exact formulas (Section 11 & 26):
            // Defect Rate = (Defective Quantity / Received Quantity) * 100
            // Quality Score = 100 - Defect Rate
            $q_calc = calculate_quality_score($qty_defective, $qty_received);
            $defect_rate = $q_calc['defect_rate'];
            $quality_score = $q_calc['quality_score'];

            try {
                $db->beginTransaction();

                $stmt = $db->prepare("
                    INSERT INTO quality_inspections (
                        delivery_id, inspection_date, quantity_received, quantity_accepted,
                        quantity_defective, quantity_rejected, defect_rate, quality_score, remarks, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $delivery_id, $inspection_date, $qty_received, $qty_accepted,
                    $qty_defective, $qty_rejected, $defect_rate, $quality_score, $remarks
                ]);
                $new_qi_id = $db->lastInsertId();

                // Fetch supplier id for score recalculation
                $sup_stmt = $db->prepare("
                    SELECT po.supplier_id, po.po_number, s.supplier_name
                    FROM deliveries d
                    INNER JOIN purchase_orders po ON d.purchase_order_id = po.id
                    INNER JOIN suppliers s ON po.supplier_id = s.id
                    WHERE d.id = ?
                ");
                $sup_stmt->execute([$delivery_id]);
                $sup_info = $sup_stmt->fetch();

                if ($sup_info) {
                    recalculate_supplier_period_score($sup_info['supplier_id'], date('Y') . '-Q' . ceil(date('n') / 3));
                }

                log_activity($_SESSION['user_id'], 'Inspection Completed', 'Quality', $new_qi_id, "Inspected Delivery #$delivery_id for {$sup_info['supplier_name']} (Defect Rate: $defect_rate%, Quality Score: $quality_score%)");

                $db->commit();

                set_flash('success', "Quality inspection saved! Defect Rate: {$defect_rate}%, Quality Score: {$quality_score}%.");
                header("Location: " . BASE_URL . "quality/view.php?id=" . $new_qi_id);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Failed to save quality inspection: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Record Quality Inspection</h1>
        <p class="text-muted small mb-0">Record component QA batch tests and calculate defect percentages.</p>
    </div>
    <a href="<?= BASE_URL ?>quality/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Inspections
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
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" id="quality-form">
    <?= csrf_input() ?>

    <div class="row g-4">
        
        <!-- Left: Form Inputs -->
        <div class="col-12 col-lg-7">
            <div class="card-saas">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-flask-vial text-primary me-2"></i> Inspection Batch Data</h6>
                </div>
                <div class="card-saas-body">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Target Delivery Receipt *</label>
                        <select name="delivery_id" id="delivery-select" class="form-select form-select-sm" required>
                            <option value="">-- Select Shipment Delivery --</option>
                            <?php foreach ($uninspected_deliveries as $d): ?>
                                <option value="<?= $d['id'] ?>" data-qty="<?= $d['quantity_received'] ?>" <?= ($selected_delivery_id === (int)$d['id'] || (isset($_POST['delivery_id']) && (int)$_POST['delivery_id'] === (int)$d['id'])) ? 'selected' : '' ?>>
                                    Receipt #<?= $d['id'] ?> &bull; <?= htmlspecialchars($d['po_number']) ?> &bull; <?= htmlspecialchars($d['supplier_name']) ?> (<?= number_format($d['quantity_received']) ?> units on <?= format_date($d['delivery_date']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Inspection Date *</label>
                            <input type="date" name="inspection_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Total Quantity Received *</label>
                            <input type="number" min="1" step="1" name="quantity_received" id="input-received" class="form-control form-control-sm font-monospace" value="1000" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-secondary">Accepted Qty *</label>
                            <input type="number" min="0" step="1" name="quantity_accepted" id="input-accepted" class="form-control form-control-sm" value="980" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-secondary">Defective Qty *</label>
                            <input type="number" min="0" step="1" name="quantity_defective" id="input-defective" class="form-control form-control-sm" value="20" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-secondary">Rejected Qty *</label>
                            <input type="number" min="0" step="1" name="quantity_rejected" id="input-rejected" class="form-control form-control-sm" value="20" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">QA Inspection Observations / Defect Description</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Detail any solder bridges, pin coplanarity, dimensional tolerances, or packaging defects..."></textarea>
                    </div>

                    <div class="text-end d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>quality/index.php" class="btn btn-light border btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save QA Inspection</button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Live Formula Calculation Engine Display -->
        <div class="col-12 col-lg-5">
            <div class="card-saas mb-4">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calculator text-primary me-2"></i> Quality Calculation Engine</h6>
                </div>
                <div class="card-saas-body">
                    
                    <div class="p-3 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(37, 99, 235, 0.08)); border: 1px solid rgba(16, 185, 129, 0.2);">
                        <div>
                            <span class="extra-small text-uppercase fw-bold text-muted d-block">Computed Quality Score</span>
                            <div class="h2 fw-bold text-success mb-0 font-monospace" id="preview-quality-score">98.00%</div>
                        </div>
                        <div>
                            <span class="badge bg-success fs-6 px-3 py-2" id="preview-defect-badge">Defect: 2.00%</span>
                        </div>
                    </div>

                    <div class="p-2 bg-light rounded-2 border extra-small text-muted mb-3 font-monospace" id="preview-formula-text">
                        Defect Rate = (20 / 1000) × 100 = <strong>2.00%</strong><br>
                        Quality Score = 100 - 2.00% = <strong>98.00%</strong>
                    </div>

                    <h6 class="fw-bold text-dark small mb-1"><i class="fa-solid fa-shield-check text-primary me-1"></i> Quality Assessment:</h6>
                    <p class="small text-muted p-2 bg-light rounded-2 border-start border-primary border-3" id="preview-assessment">
                        Defect rate is within standard acceptable quality levels (AQL 0.65 - 1.5).
                    </p>

                </div>
            </div>

            <!-- Reference Benchmark -->
            <div class="card-saas">
                <div class="card-saas-header">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-ruler-combined text-primary me-2"></i> QA Standard Thresholds</h6>
                </div>
                <div class="card-saas-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span>0.0% – 1.0% Defect Rate</span>
                            <span class="badge bg-success">Tier 1 Elite QA</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span>1.1% – 3.0% Defect Rate</span>
                            <span class="badge bg-primary">Standard Compliant</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span>&gt; 3.0% Defect Rate</span>
                            <span class="badge bg-danger">Breach / CAP Required</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

    </div>
</form>

<script>
function recalcQuality() {
    const rec = parseFloat(document.getElementById("input-received").value) || 0;
    const def = parseFloat(document.getElementById("input-defective").value) || 0;
    const accInput = document.getElementById("input-accepted");
    const rejInput = document.getElementById("input-rejected");

    const scoreDisplay = document.getElementById("preview-quality-score");
    const badgeDisplay = document.getElementById("preview-defect-badge");
    const formulaDisplay = document.getElementById("preview-formula-text");
    const assessmentDisplay = document.getElementById("preview-assessment");

    if (rec > 0) {
        const defectRate = Math.max(0, Math.min(100, (def / rec) * 100));
        const qualityScore = Math.max(0, Math.min(100, 100 - defectRate));

        scoreDisplay.textContent = qualityScore.toFixed(2) + "%";
        badgeDisplay.textContent = "Defect: " + defectRate.toFixed(2) + "%";

        if (defectRate > 3.0) {
            badgeDisplay.className = "badge bg-danger fs-6 px-3 py-2";
            scoreDisplay.className = "h2 fw-bold text-danger mb-0 font-monospace";
            assessmentDisplay.innerHTML = `<span class="text-danger fw-bold">HIGH DEFECT ALERT:</span> Defect rate exceeds 3.0% threshold. Immediate root cause analysis required.`;
        } else if (defectRate === 0) {
            badgeDisplay.className = "badge bg-success fs-6 px-3 py-2";
            scoreDisplay.className = "h2 fw-bold text-success mb-0 font-monospace";
            assessmentDisplay.textContent = "PERFECT QUALITY BATCH: Zero non-conformances identified.";
        } else {
            badgeDisplay.className = "badge bg-primary fs-6 px-3 py-2";
            scoreDisplay.className = "h2 fw-bold text-primary mb-0 font-monospace";
            assessmentDisplay.textContent = "Defect rate is within acceptable standard operating limits.";
        }

        formulaDisplay.innerHTML = `Defect Rate = (${def} / ${rec}) × 100 = <strong>${defectRate.toFixed(2)}%</strong><br>Quality Score = 100 - ${defectRate.toFixed(2)}% = <strong>${qualityScore.toFixed(2)}%</strong>`;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const delSelect = document.getElementById("delivery-select");
    if (delSelect) {
        delSelect.addEventListener("change", function() {
            const opt = this.options[this.selectedIndex];
            const qty = opt ? opt.getAttribute("data-qty") : null;
            if (qty) {
                document.getElementById("input-received").value = qty;
                document.getElementById("input-accepted").value = qty;
                document.getElementById("input-defective").value = 0;
                document.getElementById("input-rejected").value = 0;
                recalcQuality();
            }
        });
    }

    document.getElementById("input-received").addEventListener("input", recalcQuality);
    document.getElementById("input-defective").addEventListener("input", function() {
        const rec = parseFloat(document.getElementById("input-received").value) || 0;
        const def = parseFloat(this.value) || 0;
        document.getElementById("input-accepted").value = Math.max(0, rec - def);
        document.getElementById("input-rejected").value = def;
        recalcQuality();
    });

    recalcQuality();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
