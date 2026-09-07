<?php
/**
 * Supplier Performance Analysis and Management System
 * Record Supplier Invoice & Disbursement
 */

$page_title = "Record Payment / Invoice";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$errors = [];

// Fetch POs
$pos = $db->query("
    SELECT po.id, po.po_number, po.total_amount, s.id as supplier_id, s.supplier_name, s.supplier_code
    FROM purchase_orders po
    INNER JOIN suppliers s ON po.supplier_id = s.id
    ORDER BY po.order_date DESC
")->fetchAll();

$default_pay_code = 'PAY' . str_pad(rand(11, 999), 3, '0', STR_PAD_LEFT);
$default_inv_num = 'INV-BT-2026-' . rand(100, 999);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $payment_code   = trim($_POST['payment_code'] ?? '');
        $po_id          = (int)($_POST['purchase_order_id'] ?? 0);
        $invoice_number = trim($_POST['invoice_number'] ?? '');
        $invoice_date   = trim($_POST['invoice_date'] ?? date('Y-m-d'));
        $invoice_amount = floatval($_POST['invoice_amount'] ?? 0);
        $paid_amount    = floatval($_POST['paid_amount'] ?? 0);
        $payment_date   = !empty($_POST['payment_date']) ? trim($_POST['payment_date']) : null;
        $payment_method = trim($_POST['payment_method'] ?? 'NEFT/RTGS');

        $pending_amount = max(0.0, $invoice_amount - $paid_amount);

        // Derive payment status automatically
        if ($paid_amount >= $invoice_amount && $invoice_amount > 0) {
            $payment_status = 'Paid';
        } elseif ($paid_amount > 0 && $paid_amount < $invoice_amount) {
            $payment_status = 'Partially Paid';
        } else {
            $payment_status = 'Pending';
        }

        if (empty($payment_code)) $errors[] = "Payment Code is required.";
        if ($po_id <= 0) $errors[] = "Please select a Purchase Order.";
        if (empty($invoice_number)) $errors[] = "Invoice Number is required.";
        if ($invoice_amount <= 0) $errors[] = "Invoice amount must be greater than zero.";

        if (empty($errors)) {
            // Fetch supplier id from PO
            $sup_stmt = $db->prepare("SELECT supplier_id FROM purchase_orders WHERE id = ?");
            $sup_stmt->execute([$po_id]);
            $supplier_id = (int)$sup_stmt->fetchColumn();

            try {
                $stmt = $db->prepare("
                    INSERT INTO payments (
                        payment_code, supplier_id, purchase_order_id, invoice_number,
                        invoice_date, invoice_amount, paid_amount, pending_amount,
                        payment_date, payment_method, payment_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $payment_code, $supplier_id, $po_id, $invoice_number,
                    $invoice_date, $invoice_amount, $paid_amount, $pending_amount,
                    $payment_date, $payment_method, $payment_status
                ]);
                $new_pay_id = $db->lastInsertId();

                log_activity($_SESSION['user_id'], 'Payment Recorded', 'Payments', $new_pay_id, "Recorded invoice $invoice_number for " . format_currency($invoice_amount) . " (Status: $payment_status)");
                set_flash('success', "Invoice & Payment record '$invoice_number' created successfully.");
                header("Location: " . BASE_URL . "payments/index.php");
                exit;

            } catch (Exception $e) {
                $errors[] = "Failed to record payment: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Record Supplier Invoice & Payment</h1>
        <p class="text-muted small mb-0">Record vendor billing schedules, bank transfers, RTGS disbursements, and pending dues.</p>
    </div>
    <a href="<?= BASE_URL ?>payments/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Invoices
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> Vendor Billing Entry</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Payment Record Code *</label>
                    <input type="text" name="payment_code" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['payment_code'] ?? $default_pay_code) ?>" required>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label small fw-semibold text-secondary">Purchase Order *</label>
                    <select name="purchase_order_id" id="po-select" class="form-select form-select-sm" required>
                        <option value="">-- Choose Purchase Order --</option>
                        <?php foreach ($pos as $po): ?>
                            <option value="<?= $po['id'] ?>" data-amount="<?= $po['total_amount'] ?>">
                                <?= htmlspecialchars($po['po_number']) ?> &bull; <?= htmlspecialchars($po['supplier_name']) ?> (Order Total: <?= format_currency($po['total_amount']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Vendor Invoice Number *</label>
                    <input type="text" name="invoice_number" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['invoice_number'] ?? $default_inv_num) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Invoice Date *</label>
                    <input type="date" name="invoice_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Invoice Total Amount (₹) *</label>
                    <input type="number" min="0" step="0.01" name="invoice_amount" id="inv-amount" class="form-control form-control-sm font-monospace fw-bold" value="150000.00" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Paid Amount (₹) *</label>
                    <input type="number" min="0" step="0.01" name="paid_amount" id="paid-amount" class="form-control form-control-sm font-monospace text-success fw-bold" value="150000.00" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Pending Balance (₹)</label>
                    <input type="text" id="pending-display" class="form-control form-control-sm font-monospace bg-light" value="₹0.00" readonly>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Disbursement / Payment Date</label>
                    <input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Payment Method</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="NEFT/RTGS" selected>NEFT / RTGS Online Transfer</option>
                        <option value="Bank Transfer">Direct Bank Transfer / Wire</option>
                        <option value="UPI">Corporate UPI Transfer</option>
                        <option value="Credit Card">Corporate Credit Card</option>
                        <option value="Cheque">Bank Account Payee Cheque</option>
                    </select>
                </div>
            </div>

            <div class="text-end d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= BASE_URL ?>payments/index.php" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Invoice & Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const poSelect = document.getElementById("po-select");
    const invInput = document.getElementById("inv-amount");
    const paidInput = document.getElementById("paid-amount");
    const pendingDisp = document.getElementById("pending-display");

    function recalcPending() {
        const inv = parseFloat(invInput.value) || 0;
        const paid = parseFloat(paidInput.value) || 0;
        const pend = Math.max(0, inv - paid);
        pendingDisp.value = "₹" + pend.toFixed(2);
    }

    poSelect.addEventListener("change", function() {
        const opt = this.options[this.selectedIndex];
        const amt = opt ? opt.getAttribute("data-amount") : null;
        if (amt) {
            invInput.value = parseFloat(amt).toFixed(2);
            paidInput.value = parseFloat(amt).toFixed(2);
            recalcPending();
        }
    });

    invInput.addEventListener("input", recalcPending);
    paidInput.addEventListener("input", recalcPending);
    recalcPending();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
