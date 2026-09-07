<?php
/**
 * Supplier Performance Analysis and Management System
 * Add New Cosmetics & Beauty Product Item
 */

$page_title = "Add New Product";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager', 'manufacturer']);

$db = get_db();
$errors = [];

// Fetch suppliers
$suppliers = $db->query("SELECT id, supplier_name, supplier_code, category FROM suppliers WHERE status = 'Active' ORDER BY supplier_name ASC")->fetchAll();

$form = [
    'product_code'   => 'PRD-COS-' . rand(101, 999),
    'product_name'   => '',
    'category'       => 'Face Products',
    'sub_category'   => 'Foundation',
    'brand'          => 'BeautyGlow',
    'supplier_id'    => '',
    'description'    => '',
    'unit'           => 'pcs',
    'standard_price' => '',
    'wholesale_price'=> '',
    'stock_quantity' => '500',
    'reorder_level'  => '100',
    'min_order_qty'  => '10',
    'status'         => 'Active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        foreach ($form as $k => $v) {
            $form[$k] = trim($_POST[$k] ?? '');
        }

        if (empty($form['product_code'])) $errors[] = "Product Code / SKU is required.";
        if (empty($form['product_name'])) $errors[] = "Product Name is required.";
        if (empty($form['category'])) $errors[] = "Category is required.";
        if (empty($form['unit'])) $errors[] = "Unit of measurement is required.";
        if (!is_numeric($form['standard_price']) || floatval($form['standard_price']) < 0) {
            $errors[] = "Standard Price must be a valid non-negative number.";
        }

        if (empty($form['wholesale_price'])) {
            $form['wholesale_price'] = (float)$form['standard_price'] * 0.85;
        }

        // Check duplicate code
        if (empty($errors)) {
            $dup_stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
            $dup_stmt->execute([$form['product_code']]);
            if ($dup_stmt->fetchColumn() > 0) {
                $errors[] = "Product Code '{$form['product_code']}' already exists. Please enter a unique code.";
            }
        }

        if (empty($errors)) {
            try {
                $manufacturer_id = ($_SESSION['user_role'] === 'manufacturer') ? (int)$_SESSION['user_id'] : null;

                $stmt = $db->prepare("
                    INSERT INTO products (
                        product_code, product_name, category, sub_category, brand, supplier_id, manufacturer_id,
                        description, unit, standard_price, wholesale_price, stock_quantity, reorder_level, min_order_qty, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $form['product_code'],
                    $form['product_name'],
                    $form['category'],
                    $form['sub_category'],
                    $form['brand'],
                    !empty($form['supplier_id']) ? (int)$form['supplier_id'] : null,
                    $manufacturer_id,
                    $form['description'],
                    $form['unit'],
                    floatval($form['standard_price']),
                    floatval($form['wholesale_price']),
                    (int)$form['stock_quantity'],
                    (int)$form['reorder_level'],
                    (int)$form['min_order_qty'],
                    $form['status']
                ]);

                $new_id = $db->lastInsertId();

                // Initialize stock in user_inventory for manufacturer
                if ($manufacturer_id && (int)$form['stock_quantity'] > 0) {
                    require_once __DIR__ . '/../includes/transfer_functions.php';
                    adjust_user_stock($manufacturer_id, 'manufacturer', $new_id, (int)$form['stock_quantity'], 'BATCH-' . date('Ym') . '-01');
                }

                log_activity($_SESSION['user_id'], 'Product Added', 'Products', $new_id, "Added product {$form['product_name']} ({$form['product_code']})");

                set_flash('success', "Cosmetics Formulation / Product '{$form['product_name']}' created successfully with " . number_format($form['stock_quantity']) . " initial units.");
                header("Location: " . BASE_URL . "products/index.php");
                exit;
            } catch (Exception $e) {
                $errors[] = "Failed to save product: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Add Cosmetics Product</h1>
        <p class="text-muted small mb-0">Register a new beauty SKU, benchmark standard unit pricing, and link supplier.</p>
    </div>
    <a href="<?= BASE_URL ?>products/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Catalog
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

<div class="card-saas" style="max-width: 900px;">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> Beauty Product Information</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="row g-3">
                
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Product SKU / Code *</label>
                    <input type="text" name="product_code" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($form['product_code']) ?>" required>
                </div>

                <div class="col-12 col-md-8">
                    <label class="form-label small fw-semibold text-secondary">Product Name *</label>
                    <input type="text" name="product_name" class="form-control form-control-sm" placeholder="e.g. Matte Liquid Foundation (30ml)" value="<?= htmlspecialchars($form['product_name']) ?>" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Category *</label>
                    <select name="category" class="form-select form-select-sm" required>
                        <option value="Face Products" <?= $form['category'] === 'Face Products' ? 'selected' : '' ?>>💄 Face Products</option>
                        <option value="Eye Products" <?= $form['category'] === 'Eye Products' ? 'selected' : '' ?>>👁️ Eye Products</option>
                        <option value="Lip Products" <?= $form['category'] === 'Lip Products' ? 'selected' : '' ?>>💋 Lip Products</option>
                        <option value="Skincare Products" <?= $form['category'] === 'Skincare Products' ? 'selected' : '' ?>>🧴 Skincare Products</option>
                        <option value="Nail Products" <?= $form['category'] === 'Nail Products' ? 'selected' : '' ?>>💅 Nail Products</option>
                        <option value="Hair Beauty Products" <?= $form['category'] === 'Hair Beauty Products' ? 'selected' : '' ?>>💇 Hair Beauty Products</option>
                        <option value="Body Care Products" <?= $form['category'] === 'Body Care Products' ? 'selected' : '' ?>>🧼 Body Care Products</option>
                        <option value="Beauty Tools & Accessories" <?= $form['category'] === 'Beauty Tools & Accessories' ? 'selected' : '' ?>>🧑‍🦱 Beauty Tools & Accessories</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Sub-Category</label>
                    <input type="text" name="sub_category" class="form-control form-control-sm" placeholder="e.g. Primer, Foundation, Serum..." value="<?= htmlspecialchars($form['sub_category']) ?>">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Brand Name</label>
                    <input type="text" name="brand" class="form-control form-control-sm" placeholder="e.g. BeautyGlow, DermaLuxe" value="<?= htmlspecialchars($form['brand']) ?>">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Assigned Supplier Partner</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">-- Direct Formulation / In-House --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (string)$form['supplier_id'] === (string)$s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['supplier_name']) ?> (<?= htmlspecialchars($s['supplier_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Unit of Measure *</label>
                    <input type="text" name="unit" class="form-control form-control-sm" placeholder="pcs, ml, g, sets" value="<?= htmlspecialchars($form['unit']) ?>" required>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Standard Price (₹) *</label>
                    <input type="number" step="0.01" min="0" name="standard_price" class="form-control form-control-sm font-monospace fw-bold" placeholder="450.00" value="<?= htmlspecialchars($form['standard_price']) ?>" required>
                </div>

                <div class="col-4 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Current Stock Qty</label>
                    <input type="number" min="0" name="stock_quantity" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($form['stock_quantity']) ?>">
                </div>

                <div class="col-4 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Reorder Threshold</label>
                    <input type="number" min="0" name="reorder_level" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($form['reorder_level']) ?>">
                </div>

                <div class="col-4 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Min Order Qty (MOQ)</label>
                    <input type="number" min="1" name="min_order_qty" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($form['min_order_qty']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">Ingredients / Formulation Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="Enter key active ingredients, SPF protection, skin type suitability..."><?= htmlspecialchars($form['description']) ?></textarea>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="Active" <?= $form['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $form['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="mt-4 pt-3 border-top text-end d-flex justify-content-end gap-2">
                <a href="<?= BASE_URL ?>products/index.php" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Cosmetics Product</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
