<?php
/**
 * Supplier Performance Analysis and Management System
 * Record Product Return Claim
 */

$page_title = "Record Return Claim";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager', 'staff']);

$db = get_db();
$errors = [];

// Fetch POs with supplier details
$pos = $db->query("
    SELECT po.id, po.po_number, po.supplier_id, s.supplier_name, s.supplier_code
    FROM purchase_orders po
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.status = 'Delivered'
    ORDER BY po.order_date DESC
")->fetchAll();

// Fetch products
$products = $db->query("SELECT id, product_code, product_name, category, standard_price FROM products WHERE status = 'Active' ORDER BY product_name ASC")->fetchAll();

$default_return_code = 'RET' . str_pad(rand(5, 999), 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $return_code  = trim($_POST['return_code'] ?? '');
        $po_id        = (int)($_POST['purchase_order_id'] ?? 0);
        $product_id   = (int)($_POST['product_id'] ?? 0);
        $return_date  = trim($_POST['return_date'] ?? date('Y-m-d'));
        $return_qty   = (int)($_POST['return_quantity'] ?? 0);
        $return_reason= trim($_POST['return_reason'] ?? 'Quality Issue');
        $refund_amount= floatval($_POST['refund_amount'] ?? 0);
        $return_status= trim($_POST['return_status'] ?? 'Approved');
        $remarks      = trim($_POST['remarks'] ?? '');

        if (empty($return_code)) $errors[] = "Return Code is required.";
        if ($po_id <= 0) $errors[] = "Please select a valid Purchase Order.";
        if ($product_id <= 0) $errors[] = "Please select a product.";
        if ($return_qty <= 0) $errors[] = "Return quantity must be greater than zero.";

        if (empty($errors)) {
            // Get supplier_id from PO
            $sup_stmt = $db->prepare("SELECT supplier_id FROM purchase_orders WHERE id = ?");
            $sup_stmt->execute([$po_id]);
            $supplier_id = (int)$sup_stmt->fetchColumn();

            try {
                $stmt = $db->prepare("
                    INSERT INTO returns (
                        return_code, purchase_order_id, supplier_id, product_id,
                        return_date, return_quantity, return_reason, refund_amount, return_status, remarks, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $return_code, $po_id, $supplier_id, $product_id,
                    $return_date, $return_qty, $return_reason, $refund_amount, $return_status, $remarks
                ]);
                $new_ret_id = $db->lastInsertId();

                // Recalculate supplier score
                recalculate_supplier_period_score($supplier_id, date('Y') . '-Q' . ceil(date('n') / 3));

                log_activity($_SESSION['user_id'], 'Return Recorded', 'Returns', $new_ret_id, "Logged Return $return_code for $return_qty units ($return_reason, Refund: " . format_currency($refund_amount) . ")");
                set_flash('success', "Product return claim '$return_code' saved and supplier score recomputed.");
                header("Location: " . BASE_URL . "returns/index.php");
                exit;

            } catch (Exception $e) {
                $errors[] = "Failed to record return: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Record Return Claim</h1>
        <p class="text-muted small mb-0">Log cosmetics batch damage, wrong items, or quality rejection claims.</p>
    </div>
    <a href="<?= BASE_URL ?>returns/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Returns
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-rotate-left text-primary me-2"></i> Return Claim Entry</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Return Claim ID *</label>
                    <input type="text" name="return_code" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['return_code'] ?? $default_return_code) ?>" required>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label small fw-semibold text-secondary">Purchase Order *</label>
                    <select name="purchase_order_id" class="form-select form-select-sm" required>
                        <option value="">-- Choose Delivered PO --</option>
                        <?php foreach ($pos as $po): ?>
                            <option value="<?= $po['id'] ?>">
                                <?= htmlspecialchars($po['po_number']) ?> &bull; <?= htmlspecialchars($po['supplier_name']) ?> (<?= htmlspecialchars($po['supplier_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Returned Product Item *</label>
                    <select name="product_id" id="product-select" class="form-select form-select-sm" required>
                        <option value="">-- Choose Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= $p['standard_price'] ?>">
                                <?= htmlspecialchars($p['product_code']) ?> - <?= htmlspecialchars($p['product_name']) ?> (<?= format_currency($p['standard_price']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Return Date *</label>
                    <input type="date" name="return_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Return Quantity *</label>
                    <input type="number" min="1" step="1" name="return_quantity" id="return-qty" class="form-control form-control-sm font-monospace" value="10" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Return Reason *</label>
                    <select name="return_reason" class="form-select form-select-sm" required>
                        <option value="Damaged">Damaged Goods / Broken Seals</option>
                        <option value="Quality Issue" selected>Quality Issue / Non-conformance</option>
                        <option value="Wrong Product">Wrong Product / Wrong Shade</option>
                        <option value="Expired">Expired / Near Expiry</option>
                        <option value="Other">Other Reasons</option>
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Estimated Refund / Credit (₹) *</label>
                    <input type="number" min="0" step="0.01" name="refund_amount" id="refund-amt" class="form-control form-control-sm font-monospace fw-bold" value="4500.00" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Claim Status *</label>
                    <select name="return_status" class="form-select form-select-sm" required>
                        <option value="Approved" selected>Approved (Credit Note)</option>
                        <option value="Pending">Pending Vendor Review</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Quality / Transit Rejection Remarks</label>
                <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Specify damaged packaging, wrong shade codes, or leakages..."></textarea>
            </div>

            <div class="text-end d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>returns/index.php" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Return Claim</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const pSelect = document.getElementById("product-select");
    const qtyInput = document.getElementById("return-qty");
    const refundInput = document.getElementById("refund-amt");

    function updateRefund() {
        const opt = pSelect.options[pSelect.selectedIndex];
        const price = opt ? parseFloat(opt.getAttribute("data-price")) : 0;
        const qty = parseFloat(qtyInput.value) || 0;
        if (price > 0 && qty > 0) {
            refundInput.value = (price * qty).toFixed(2);
        }
    }

    pSelect.addEventListener("change", updateRefund);
    qtyInput.addEventListener("input", updateRefund);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
