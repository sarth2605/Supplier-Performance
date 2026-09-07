<?php
/**
 * Supplier Performance Analysis and Management System
 * Record Delivery & Automatic Delay Calculation
 */

$page_title = "Receive Shipment Delivery";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$selected_po_id = (int)($_GET['po_id'] ?? 0);
$errors = [];

// Fetch open purchase orders
$pos_stmt = $db->query("
    SELECT po.id, po.po_number, po.expected_date, po.total_amount, po.status,
           s.supplier_name, s.supplier_code,
           COALESCE(SUM(oi.quantity), 0) as total_qty_ordered
    FROM purchase_orders po
    INNER JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN order_items oi ON po.id = oi.purchase_order_id
    WHERE po.status IN ('Approved', 'Pending', 'Partially Delivered')
    GROUP BY po.id
    ORDER BY po.order_date DESC
");
$open_pos = $pos_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $po_id         = (int)($_POST['purchase_order_id'] ?? 0);
        $delivery_date = trim($_POST['delivery_date'] ?? date('Y-m-d'));
        $qty_received  = (int)($_POST['quantity_received'] ?? 0);
        $remarks       = trim($_POST['remarks'] ?? '');

        if ($po_id <= 0) $errors[] = "Please select a valid Purchase Order.";
        if (empty($delivery_date)) $errors[] = "Delivery date is required.";
        if ($qty_received <= 0) $errors[] = "Quantity received must be greater than zero.";

        if (empty($errors)) {
            // Fetch PO expected date
            $po_stmt = $db->prepare("SELECT po_number, expected_date, supplier_id FROM purchase_orders WHERE id = ?");
            $po_stmt->execute([$po_id]);
            $po = $po_stmt->fetch();

            if (!$po) {
                $errors[] = "Purchase Order not found.";
            } else {
                // Calculate Delay Days according to formula (Section 10 & 25):
                // delay_days = actual delivery date - expected delivery date
                // If delivery occurs before or on expected date: delay_days = 0
                $exp_time = strtotime($po['expected_date']);
                $act_time = strtotime($delivery_date);
                $diff_seconds = $act_time - $exp_time;
                $diff_days = (int)floor($diff_seconds / (60 * 60 * 24));

                if ($diff_days > 0) {
                    $delay_days = $diff_days;
                    $delivery_status = 'Delayed';
                } elseif ($diff_days < 0) {
                    $delay_days = 0;
                    $delivery_status = 'Early';
                } else {
                    $delay_days = 0;
                    $delivery_status = 'On Time';
                }

                try {
                    $db->beginTransaction();

                    $ins_stmt = $db->prepare("
                        INSERT INTO deliveries (purchase_order_id, delivery_date, quantity_received, delivery_status, delay_days, remarks, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $ins_stmt->execute([$po_id, $delivery_date, $qty_received, $delivery_status, $delay_days, $remarks]);
                    $new_delivery_id = $db->lastInsertId();

                    // Update PO status to Delivered
                    $db->prepare("UPDATE purchase_orders SET status = 'Delivered' WHERE id = ?")->execute([$po_id]);

                    // Automatically recalculate supplier score
                    recalculate_supplier_period_score($po['supplier_id'], date('Y') . '-Q' . ceil(date('n') / 3));

                    log_activity($_SESSION['user_id'], 'Delivery Recorded', 'Deliveries', $new_delivery_id, "Received $qty_received units for {$po['po_number']} ($delivery_status, $delay_days delay days).");

                    $db->commit();

                    set_flash('success', "Delivery for PO '{$po['po_number']}' recorded successfully. Status: $delivery_status (" . ($delay_days > 0 ? "+$delay_days days delay" : "On schedule") . ")");
                    header("Location: " . BASE_URL . "quality/add.php?delivery_id=" . $new_delivery_id);
                    exit;

                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Failed to record delivery: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Receive Shipment Delivery</h1>
        <p class="text-muted small mb-0">Record delivery arrival and automatically compute delay days against contract schedule.</p>
    </div>
    <a href="<?= BASE_URL ?>deliveries/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Deliveries
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

<div class="card-saas" style="max-width: 800px;">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i> Delivery Receipt Entry</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST" id="delivery-form">
            <?= csrf_input() ?>

            <div class="row g-3 mb-3">
                
                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">Target Purchase Order *</label>
                    <select name="purchase_order_id" id="po-select" class="form-select form-select-sm" required>
                        <option value="">-- Choose Purchase Order --</option>
                        <?php foreach ($open_pos as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-expected="<?= $p['expected_date'] ?>"
                                    data-qty="<?= $p['total_qty_ordered'] ?>"
                                    data-supplier="<?= htmlspecialchars($p['supplier_name']) ?>"
                                    <?= ($selected_po_id === (int)$p['id'] || (isset($_POST['purchase_order_id']) && (int)$_POST['purchase_order_id'] === (int)$p['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['po_number']) ?> &bull; <?= htmlspecialchars($p['supplier_name']) ?> (Expected: <?= format_date($p['expected_date']) ?>, Ordered Qty: <?= number_format($p['total_qty_ordered']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Expected Delivery Date (From PO)</label>
                    <input type="text" id="display-expected-date" class="form-control form-control-sm bg-light font-monospace" value="—" readonly>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Actual Delivery Arrival Date *</label>
                    <input type="date" name="delivery_date" id="actual-delivery-date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Live Delay Computation Banner -->
                <div class="col-12">
                    <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between" id="delay-preview-box" style="background: #f8fafc;">
                        <div>
                            <span class="extra-small text-uppercase fw-bold text-muted d-block">Automatic Delay Computation</span>
                            <div class="fw-bold fs-6" id="delay-status-text">Select PO to calculate</div>
                        </div>
                        <div>
                            <span class="badge bg-secondary fs-6 px-3 py-2" id="delay-badge">Pending</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Quantity Received (Units) *</label>
                    <input type="number" min="1" step="1" name="quantity_received" id="qty-received-input" class="form-control form-control-sm" value="100" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Carrier / Delivery Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Received via BlueDart AWB# 889123; seal intact.">
                </div>

            </div>

            <div class="mt-4 pt-3 border-top text-end d-flex justify-content-end gap-2">
                <a href="<?= BASE_URL ?>deliveries/index.php" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Record Delivery & Proceed to Quality Check</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateDelayCalculation() {
    const poSelect = document.getElementById("po-select");
    const expDisplay = document.getElementById("display-expected-date");
    const actInput = document.getElementById("actual-delivery-date");
    const qtyInput = document.getElementById("qty-received-input");
    const box = document.getElementById("delay-preview-box");
    const text = document.getElementById("delay-status-text");
    const badge = document.getElementById("delay-badge");

    const opt = poSelect.options[poSelect.selectedIndex];
    const expDate = opt ? opt.getAttribute("data-expected") : null;
    const defaultQty = opt ? opt.getAttribute("data-qty") : null;

    if (expDate) {
        expDisplay.value = expDate;
        if (defaultQty && !qtyInput.value) {
            qtyInput.value = defaultQty;
        }

        const exp = new Date(expDate);
        const act = new Date(actInput.value);
        const diffTime = act - exp;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            text.innerHTML = `<span class="text-danger">Shipment Delayed by ${diffDays} day(s) against target schedule.</span>`;
            badge.className = "badge bg-danger fs-6 px-3 py-2";
            badge.textContent = `Delayed (+${diffDays}d)`;
            box.style.background = "rgba(239, 68, 68, 0.08)";
            box.style.borderColor = "rgba(239, 68, 68, 0.3)";
        } else if (diffDays < 0) {
            text.innerHTML = `<span class="text-success">Shipment Arrived ${Math.abs(diffDays)} day(s) ahead of schedule!</span>`;
            badge.className = "badge bg-info fs-6 px-3 py-2";
            badge.textContent = `Early (${Math.abs(diffDays)}d early)`;
            box.style.background = "rgba(6, 182, 212, 0.08)";
            box.style.borderColor = "rgba(6, 182, 212, 0.3)";
        } else {
            text.innerHTML = `<span class="text-success">Punctual On-Time Delivery! Exact match with target schedule.</span>`;
            badge.className = "badge bg-success fs-6 px-3 py-2";
            badge.textContent = "On Time";
            box.style.background = "rgba(16, 185, 129, 0.08)";
            box.style.borderColor = "rgba(16, 185, 129, 0.3)";
        }
    } else {
        expDisplay.value = "—";
        text.textContent = "Please select a Purchase Order to compute delay.";
        badge.className = "badge bg-secondary fs-6 px-3 py-2";
        badge.textContent = "Pending";
        box.style.background = "#f8fafc";
        box.style.borderColor = "#e2e8f0";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("po-select").addEventListener("change", updateDelayCalculation);
    document.getElementById("actual-delivery-date").addEventListener("input", updateDelayCalculation);
    updateDelayCalculation();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
