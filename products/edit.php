<?php
/**
 * Supplier Performance Analysis and Management System
 * Edit Product Details
 */

$page_title = "Edit Product";
require_once __DIR__ . '/../includes/header.php';
require_role(['admin', 'manager']);

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Product not found.');
    header("Location: " . BASE_URL . "products/index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = "Security token mismatch.";
    } else {
        $code  = trim($_POST['product_code'] ?? '');
        $name  = trim($_POST['product_name'] ?? '');
        $cat   = trim($_POST['category'] ?? '');
        $unit  = trim($_POST['unit'] ?? '');
        $price = floatval($_POST['standard_price'] ?? 0);
        $desc  = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'Active');

        if (empty($code)) $errors[] = "Product Code is required.";
        if (empty($name)) $errors[] = "Product Name is required.";
        if ($price < 0) $errors[] = "Price cannot be negative.";

        // Duplicate code check
        if (empty($errors)) {
            $dup_stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ? AND id != ?");
            $dup_stmt->execute([$code, $id]);
            if ($dup_stmt->fetchColumn() > 0) {
                $errors[] = "Product Code '$code' is already used by another item.";
            }
        }

        if (empty($errors)) {
            try {
                $update_stmt = $db->prepare("
                    UPDATE products SET
                        product_code = ?, product_name = ?, category = ?, description = ?,
                        unit = ?, standard_price = ?, status = ?
                    WHERE id = ?
                ");
                $update_stmt->execute([$code, $name, $cat, $desc, $unit, $price, $status, $id]);

                log_activity($_SESSION['user_id'], 'Product Updated', 'Products', $id, "Updated product $name ($code)");
                set_flash('success', "Product '$name' updated successfully.");
                header("Location: " . BASE_URL . "products/view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $errors[] = "Update failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">Edit Product Item</h1>
        <p class="text-muted small mb-0">Modify specifications, standard benchmark pricing, and status for <?= htmlspecialchars($product['product_name']) ?>.</p>
    </div>
    <a href="<?= BASE_URL ?>products/view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Product
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
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Component Details</h6>
    </div>
    <div class="card-saas-body">
        <form action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="POST">
            <?= csrf_input() ?>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Product Code *</label>
                    <input type="text" name="product_code" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($_POST['product_code'] ?? $product['product_code']) ?>" required>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label small fw-semibold text-secondary">Product Name *</label>
                    <input type="text" name="product_name" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['product_name'] ?? $product['product_name']) ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Category *</label>
                    <?php $c = $_POST['category'] ?? $product['category']; ?>
                    <select name="category" class="form-select form-select-sm" required>
                        <option value="Electronics" <?= $c === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                        <option value="Components" <?= $c === 'Components' ? 'selected' : '' ?>>Components</option>
                        <option value="Raw Materials" <?= $c === 'Raw Materials' ? 'selected' : '' ?>>Raw Materials</option>
                        <option value="Hardware" <?= $c === 'Hardware' ? 'selected' : '' ?>>Hardware</option>
                        <option value="Packaging" <?= $c === 'Packaging' ? 'selected' : '' ?>>Packaging</option>
                        <option value="Services" <?= $c === 'Services' ? 'selected' : '' ?>>Services</option>
                        <option value="Other" <?= $c === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Unit *</label>
                    <input type="text" name="unit" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['unit'] ?? $product['unit']) ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Standard Price ($) *</label>
                    <input type="number" step="0.01" min="0" name="standard_price" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['standard_price'] ?? $product['standard_price']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold text-secondary">Technical Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Status</label>
                    <?php $s = $_POST['status'] ?? $product['status']; ?>
                    <select name="status" class="form-select form-select-sm">
                        <option value="Active" <?= $s === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $s === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end d-flex justify-content-end gap-2">
                <a href="<?= BASE_URL ?>products/view.php?id=<?= $id ?>" class="btn btn-light border btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
