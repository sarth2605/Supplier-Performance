<?php
/**
 * Supplier Performance Analysis and Management System
 * View Product Details & Order Utilization
 */

$page_title = "Product Details";
require_once __DIR__ . '/../includes/header.php';

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

// Fetch PO usage for this product
$orders_stmt = $db->prepare("
    SELECT oi.*, po.po_number, po.order_date, po.status as po_status, s.supplier_name, s.supplier_code
    FROM order_items oi
    INNER JOIN purchase_orders po ON oi.purchase_order_id = po.id
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE oi.product_id = ?
    ORDER BY po.order_date DESC
");
$orders_stmt->execute([$id]);
$product_orders = $orders_stmt->fetchAll();

$total_qty_ordered = array_sum(array_column($product_orders, 'quantity'));
$total_spend = array_sum(array_column($product_orders, 'total_price'));
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1"><?= htmlspecialchars($product['product_name']) ?></h1>
        <p class="text-muted small mb-0"><code class="text-primary fw-bold"><?= htmlspecialchars($product['product_code']) ?></code> &bull; <?= htmlspecialchars($product['category']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>products/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Catalog
        </a>
        <?php if ($_SESSION['user_role'] !== 'staff'): ?>
        <a href="<?= BASE_URL ?>products/edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-pen"></i> Edit Product
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Overview KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="text-uppercase extra-small fw-bold text-muted">Standard Benchmark Price</span>
            <h3 class="fw-bold text-dark my-1"><?= format_currency($product['standard_price']) ?></h3>
            <span class="extra-small text-muted">Per <?= htmlspecialchars($product['unit']) ?></span>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Quantity Procured</span>
            <h3 class="fw-bold text-dark my-1"><?= number_format($total_qty_ordered) ?></h3>
            <span class="extra-small text-muted"><?= htmlspecialchars($product['unit']) ?> across <?= count($product_orders) ?> POs</span>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <span class="text-uppercase extra-small fw-bold text-muted">Total Procurement Spend</span>
            <h3 class="fw-bold text-dark my-1"><?= format_currency($total_spend) ?></h3>
            <span class="extra-small text-muted">Status: <?= get_status_badge($product['status']) ?></span>
        </div>
    </div>
</div>

<!-- Product Details & Description -->
<div class="card-saas mb-4">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-circle-info text-primary me-2"></i> Specification & Technical Overview</h6>
    </div>
    <div class="card-saas-body">
        <div class="row g-3">
            <div class="col-12 col-md-8">
                <p class="text-secondary small mb-0"><?= nl2br(htmlspecialchars($product['description'] ?: 'No detailed technical description provided.')) ?></p>
            </div>
            <div class="col-12 col-md-4 border-start-md">
                <div class="extra-small text-muted mb-1">Registered: <?= format_date($product['created_at']) ?></div>
                <div class="extra-small text-muted mb-1">Category: <span class="badge bg-light text-dark border"><?= htmlspecialchars($product['category']) ?></span></div>
                <div class="extra-small text-muted">Unit Type: <?= htmlspecialchars($product['unit']) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Orders History Table -->
<div class="card-saas">
    <div class="card-saas-header">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Procurement Order History (<?= count($product_orders) ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Quantity</th>
                    <th>Contract Unit Price</th>
                    <th>Line Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($product_orders)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No purchase orders found containing this product.</td></tr>
                <?php else: ?>
                    <?php foreach ($product_orders as $po): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $po['purchase_order_id'] ?>" class="text-decoration-none fw-bold font-monospace text-primary">
                                    <?= htmlspecialchars($po['po_number']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($po['supplier_name']) ?></span>
                                <code class="extra-small text-muted">(<?= htmlspecialchars($po['supplier_code']) ?>)</code>
                            </td>
                            <td><?= format_date($po['order_date']) ?></td>
                            <td><?= number_format($po['quantity']) ?></td>
                            <td class="font-monospace"><?= format_currency($po['unit_price']) ?></td>
                            <td class="font-monospace fw-bold"><?= format_currency($po['total_price']) ?></td>
                            <td><?= get_status_badge($po['po_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
