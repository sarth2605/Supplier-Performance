<?php
/**
 * Supplier Performance Analysis and Management System
 * Create Purchase Order with Multi-Item Table
 */

$page_title = "Create Purchase Order";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$errors = [];

// Fetch active suppliers & products
$suppliers = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers WHERE status = 'Active' ORDER BY supplier_name ASC")->fetchAll();
$products = $db->query("SELECT id, product_code, product_name, category, standard_price, unit FROM products WHERE status = 'Active' ORDER BY product_name ASC")->fetchAll();

$po_number_default = 'PO-' . date('Y') . '-' . str_pad(rand(19, 999), 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $po_number    = trim($_POST['po_number'] ?? '');
        $supplier_id  = (int)($_POST['supplier_id'] ?? 0);
        $order_date   = trim($_POST['order_date'] ?? date('Y-m-d'));
        $expected_date= trim($_POST['expected_date'] ?? '');
        $status       = trim($_POST['status'] ?? 'Pending');
        $item_products= $_POST['product_id'] ?? [];
        $item_quantities = $_POST['quantity'] ?? [];
        $item_prices  = $_POST['unit_price'] ?? [];

        if (empty($po_number)) $errors[] = "PO Number is required.";
        if ($supplier_id <= 0) $errors[] = "Please select a supplier.";
        if (empty($expected_date)) $errors[] = "Expected delivery date is required.";
        if ($expected_date < $order_date) $errors[] = "Expected delivery date cannot be before order date.";

        // Check duplicate PO number
        if (empty($errors)) {
            $dup_stmt = $db->prepare("SELECT COUNT(*) FROM purchase_orders WHERE po_number = ?");
            $dup_stmt->execute([$po_number]);
            if ($dup_stmt->fetchColumn() > 0) {
                $errors[] = "PO Number '$po_number' already exists. Please choose a unique PO number.";
            }
        }

        // Validate line items
        $valid_items = [];
        $grand_total = 0.00;

        if (empty($item_products) || !is_array($item_products)) {
            $errors[] = "Please add at least one product item to the purchase order.";
        } else {
            for ($i = 0; $i < count($item_products); $i++) {
                $p_id = (int)($item_products[$i] ?? 0);
                $qty = (int)($item_quantities[$i] ?? 0);
                $price = floatval($item_prices[$i] ?? 0);

                if ($p_id > 0 && $qty > 0 && $price >= 0) {
                    $line_total = $qty * $price;
                    $grand_total += $line_total;
                    $valid_items[] = [
                        'product_id' => $p_id,
                        'quantity'   => $qty,
                        'unit_price' => $price,
                        'total_price'=> $line_total
                    ];
                }
            }
            if (empty($valid_items)) {
                $errors[] = "All order items must have a valid product selected and quantity > 0.";
            }
        }

        // Execute Database Transaction
        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $po_stmt = $db->prepare("
                    INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_date, total_amount, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $po_stmt->execute([$po_number, $supplier_id, $order_date, $expected_date, $grand_total, $status]);
                $new_po_id = $db->lastInsertId();

                // Insert line items
                $item_stmt = $db->prepare("
                    INSERT INTO order_items (purchase_order_id, product_id, quantity, unit_price, total_price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($valid_items as $item) {
                    $item_stmt->execute([$new_po_id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total_price']]);
                }

                log_activity($_SESSION['user_id'], 'PO Created', 'Purchase Orders', $new_po_id, "Created purchase order $po_number for amount $" . number_format($grand_total, 2));

                $db->commit();

                set_flash('success', "Purchase Order '$po_number' generated successfully with " . count($valid_items) . " line items.");
                header("Location: " . BASE_URL . "purchase_orders/view.php?id=" . $new_po_id);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Failed to create Purchase Order: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Create Purchase Order</h1>
        <p class="text-muted small mb-0">Generate a formal purchase order with multiple product items and automated line totals.</p>
    </div>
    <a href="<?= BASE_URL ?>purchase_orders/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Please fix the errors:</h6>
        <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" id="po-form">
    <?= csrf_input() ?>

    <!-- PO Header Details Card -->
    <div class="card-saas mb-4">
        <div class="card-saas-header">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-invoice text-primary me-2"></i> 1. Order Header Information</h6>
        </div>
        <div class="card-saas-body">
            <div class="row g-3">
                
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">PO Number *</label>
                    <input type="text" name="po_number" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['po_number'] ?? $po_number_default) ?>" required>
                </div>

                <div class="col-12 col-md-5">
                    <label class="form-label small fw-semibold text-secondary">Supplier Partner *</label>
                    <select name="supplier_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Active Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (isset($_POST['supplier_id']) && (int)$_POST['supplier_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['supplier_code']) ?> - <?= htmlspecialchars($s['supplier_name']) ?> (<?= htmlspecialchars($s['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Order Date *</label>
                    <input type="date" name="order_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['order_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Expected Delivery *</label>
                    <input type="date" name="expected_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['expected_date'] ?? date('Y-m-d', strtotime('+10 days'))) ?>" required>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Initial PO Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="Pending">Pending Approval</option>
                        <option value="Approved" selected>Approved</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- PO Line Items Table Card -->
    <div class="card-saas mb-4">
        <div class="card-saas-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-ol text-primary me-2"></i> 2. Order Line Items</h6>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="add-item-row-btn">
                <i class="fa-solid fa-plus me-1"></i> Add Another Product
            </button>
        </div>
        <div class="card-saas-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-bordered mb-0" id="po-items-table">
                    <thead>
                        <tr>
                            <th style="min-width: 280px;">Product Component *</th>
                            <th style="width: 140px;">Quantity *</th>
                            <th style="width: 160px;">Unit Price ($) *</th>
                            <th style="width: 160px;">Total Price ($)</th>
                            <th style="width: 60px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="po-items-tbody">
                        <tr class="po-item-row">
                            <td>
                                <select name="product_id[]" class="form-select form-select-sm product-select" required>
                                    <option value="">-- Choose Product Item --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['standard_price'] ?>" data-unit="<?= $p['unit'] ?>">
                                            <?= htmlspecialchars($p['product_code']) ?> - <?= htmlspecialchars($p['product_name']) ?> (<?= format_currency($p['standard_price']) ?> / <?= htmlspecialchars($p['unit']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" min="1" step="1" name="quantity[]" class="form-control form-control-sm qty-input" value="100" required>
                            </td>
                            <td>
                                <input type="number" min="0" step="0.01" name="unit_price[]" class="form-control form-control-sm price-input" value="0.00" required>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm row-total bg-light font-monospace fw-bold" value="$0.00" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-light border text-danger remove-row-btn" title="Remove line"><i class="fa-solid fa-xmark"></i></button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Grand Total Purchase Order Amount:</td>
                            <td colspan="2">
                                <span class="h5 fw-bold text-dark font-monospace mb-0" id="grand-total-display">$0.00</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="text-end d-flex justify-content-end gap-2">
        <a href="<?= BASE_URL ?>purchase_orders/index.php" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 shadow-sm d-flex align-items-center gap-2">
            <i class="fa-solid fa-check"></i>
            <span>Submit & Issue Purchase Order</span>
        </button>
    </div>

</form>

<!-- Pass products catalog to JS for instant row cloning -->
<script>
const productsCatalog = <?= json_encode($products) ?>;

function attachRowEvents(row) {
    const pSelect = row.querySelector(".product-select");
    const qtyInput = row.querySelector(".qty-input");
    const priceInput = row.querySelector(".price-input");
    const rowTotal = row.querySelector(".row-total");
    const removeBtn = row.querySelector(".remove-row-btn");

    pSelect.addEventListener("change", function() {
        const opt = this.options[this.selectedIndex];
        const stdPrice = opt.getAttribute("data-price");
        if (stdPrice) {
            priceInput.value = parseFloat(stdPrice).toFixed(2);
        }
        recalcRow();
    });

    qtyInput.addEventListener("input", recalcRow);
    priceInput.addEventListener("input", recalcRow);

    function recalcRow() {
        const q = parseFloat(qtyInput.value) || 0;
        const p = parseFloat(priceInput.value) || 0;
        const tot = q * p;
        rowTotal.value = "$" + tot.toFixed(2);
        recalcGrandTotal();
    }

    if (removeBtn) {
        removeBtn.addEventListener("click", function() {
            const rows = document.querySelectorAll(".po-item-row");
            if (rows.length > 1) {
                row.remove();
                recalcGrandTotal();
            } else {
                alert("A purchase order must contain at least one line item.");
            }
        });
    }
}

function recalcGrandTotal() {
    let grand = 0;
    document.querySelectorAll(".po-item-row").forEach(r => {
        const q = parseFloat(r.querySelector(".qty-input").value) || 0;
        const p = parseFloat(r.querySelector(".price-input").value) || 0;
        grand += (q * p);
    });
    document.getElementById("grand-total-display").textContent = "$" + grand.toFixed(2);
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".po-item-row").forEach(attachRowEvents);

    document.getElementById("add-item-row-btn").addEventListener("click", () => {
        const tbody = document.getElementById("po-items-tbody");
        const newRow = document.createElement("tr");
        newRow.className = "po-item-row";

        let optionsHtml = '<option value="">-- Choose Product Item --</option>';
        productsCatalog.forEach(p => {
            optionsHtml += `<option value="${p.id}" data-price="${p.standard_price}" data-unit="${p.unit}">${p.product_code} - ${p.product_name} ($${p.standard_price} / ${p.unit})</option>`;
        });

        newRow.innerHTML = `
            <td>
                <select name="product_id[]" class="form-select form-select-sm product-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" min="1" step="1" name="quantity[]" class="form-control form-control-sm qty-input" value="100" required>
            </td>
            <td>
                <input type="number" min="0" step="0.01" name="unit_price[]" class="form-control form-control-sm price-input" value="0.00" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm row-total bg-light font-monospace fw-bold" value="$0.00" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light border text-danger remove-row-btn" title="Remove line"><i class="fa-solid fa-xmark"></i></button>
            </td>
        `;

        tbody.appendChild(newRow);
        attachRowEvents(newRow);
    });

    recalcGrandTotal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
