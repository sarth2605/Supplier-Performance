<?php
/**
 * Supplier Performance Analysis and Management System
 * Shopkeeper Damaged Goods & Product Return Claim Filing
 */

$page_title = "File Damage & Return Claim";
require_once __DIR__ . '/../includes/header.php';
require_role(['shopkeeper', 'admin', 'manufacturer']);

$db = get_db();
$user_id = (int)$_SESSION['user_id'];

$error = '';
$success = '';

// Fetch products and purchase orders for this shopkeeper
$my_pos = $db->prepare("
    SELECT po.id, po.po_number, po.order_date, s.supplier_name, s.id as supplier_id 
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.user_id = ?
    ORDER BY po.id DESC
");
$my_pos->execute([$user_id]);
$orders = $my_pos->fetchAll();

// If no specific POs yet, provide all delivered POs
if (empty($orders)) {
    $orders = $db->query("
        SELECT po.id, po.po_number, po.order_date, s.supplier_name, s.id as supplier_id 
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.status = 'Delivered'
        ORDER BY po.id DESC
    ")->fetchAll();
}

$products = $db->query("SELECT id, product_code, product_name, wholesale_price, standard_price FROM products WHERE status = 'Active' ORDER BY product_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh.';
    } else {
        $po_id = (int)($_POST['purchase_order_id'] ?? 0);
        $product_id = (int)($_POST['product_id'] ?? 0);
        $return_qty = (int)($_POST['return_quantity'] ?? 0);
        $return_reason = trim($_POST['return_reason'] ?? 'Damaged');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($po_id <= 0 || $product_id <= 0 || $return_qty <= 0) {
            $error = 'Please fill in all mandatory return claim details.';
        } else {
            // Get product and po details
            $po_stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
            $po_stmt->execute([$po_id]);
            $po = $po_stmt->fetch();

            $p_stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $p_stmt->execute([$product_id]);
            $prod = $p_stmt->fetch();

            if ($po && $prod) {
                $supplier_id = (int)$po['supplier_id'];
                $unit_price = (float)$prod['wholesale_price'];
                $refund_amount = $unit_price * $return_qty;

                // Generate Return Code
                $last_ret = $db->query("SELECT id FROM returns ORDER BY id DESC LIMIT 1")->fetch();
                $next_id = ($last_ret['id'] ?? 4) + 1;
                $return_code = 'RET' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
                $return_date = date('Y-m-d');

                $ins_ret = $db->prepare("
                    INSERT INTO returns (return_code, user_id, purchase_order_id, supplier_id, product_id, return_date, return_quantity, return_reason, refund_amount, return_status, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', ?)
                ");
                $ins_ret->execute([$return_code, $user_id, $po_id, $supplier_id, $product_id, $return_date, $return_qty, $return_reason, $refund_amount, $remarks]);

                // Recalculate supplier's 5-factor score in real time!
                recalculate_supplier_period_score($supplier_id, '2026-Q1');

                log_activity($user_id, 'Return Claim Filed', 'Returns', $po_id, 'Filed Return Claim ' . $return_code . ' for ' . $return_qty . ' units of ' . $prod['product_name'] . ' (Refund: ₹' . number_format($refund_amount, 2) . ')');
                set_flash('success', 'Return Claim ' . $return_code . ' registered! Credit refund note of ₹' . number_format($refund_amount, 2) . ' issued.');
                header('Location: ' . BASE_URL . 'shopkeeper/dashboard.php');
                exit;
            } else {
                $error = 'Invalid order or product reference.';
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">File Damaged Goods / Return Claim</h1>
        <p class="text-muted small mb-0">Report damaged cosmetic pumps, broken powders, wrong shade shipments, or non-conforming batches.</p>
    </div>
    <a href="<?= BASE_URL ?>shopkeeper/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card-saas border-0 shadow-sm" style="border-top: 4px solid #ec4899 !important;">
            <div class="card-saas-header bg-white">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-rotate-left text-pink me-2"></i> Quality Return & Credit Refund Application</h6>
            </div>
            <div class="card-saas-body p-4">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show extra-small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
                    <?= csrf_input() ?>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Associated Purchase Order *</label>
                            <select name="purchase_order_id" class="form-select" required>
                                <?php foreach ($orders as $o): ?>
                                    <option value="<?= $o['id'] ?>">
                                        <?= htmlspecialchars($o['po_number']) ?> &bull; <?= htmlspecialchars($o['supplier_name']) ?> (<?= $o['order_date'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Affected Cosmetics Product *</label>
                            <select name="product_id" id="return-product-select" class="form-select" onchange="updateRefundCalculation()" required>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['wholesale_price'] ?>">
                                        <?= htmlspecialchars($p['product_code']) ?> - <?= htmlspecialchars($p['product_name']) ?> (₹<?= $p['wholesale_price'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Quantity to Return (Units) *</label>
                            <input type="number" name="return_quantity" id="return-qty-input" class="form-control" value="5" min="1" max="500" oninput="updateRefundCalculation()" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Primary Reason for Return *</label>
                            <select name="return_reason" class="form-select" required>
                                <option value="Damaged" selected>💥 Physical Damage / Broken Packaging</option>
                                <option value="Wrong Product">📦 Wrong Shade / Variant Dispatched</option>
                                <option value="Quality Issue">🔬 Formulation / Non-conformance Issue</option>
                                <option value="Expired">⌛ Near Expiry / Expired Batch</option>
                                <option value="Other">📝 Other Logistics Discrepancy</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Defect Observations / Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Describe the physical condition, pump defect, broken powder pan, or batch code..."></textarea>
                        </div>
                    </div>

                    <div class="p-3 bg-pink-subtle rounded-3 mt-4 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="extra-small text-uppercase fw-bold text-muted d-block">Estimated Refund / Credit Note</span>
                            <span class="extra-small text-secondary">Calculated at wholesale invoice unit price</span>
                        </div>
                        <div class="fs-4 fw-extrabold text-pink font-monospace" id="calc-refund-display">₹0.00</div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-pink w-100 py-2.5 rounded-3 fw-semibold text-white shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #ec4899;">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Submit Damage Claim & Issue Credit Note</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
function updateRefundCalculation() {
    const sel = document.getElementById('return-product-select');
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.getAttribute('data-price')) || 0;
    const qty = parseInt(document.getElementById('return-qty-input').value) || 0;
    const total = price * qty;
    document.getElementById('calc-refund-display').textContent = `₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}
document.addEventListener('DOMContentLoaded', updateRefundCalculation);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
