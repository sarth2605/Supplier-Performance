<?php
/**
 * Supplier Performance Analysis and Management System
 * Shopkeeper Wholesale Order History & Tracking
 */

$page_title = "My Wholesale Orders";
require_once __DIR__ . '/../includes/header.php';
require_role(['shopkeeper', 'admin', 'manufacturer']);

$db = get_db();
$user_id = (int)$_SESSION['user_id'];

// Fetch all orders placed by this shopkeeper
$stmt = $db->prepare("
    SELECT po.*, s.supplier_name, s.supplier_code, s.category as supplier_category,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.purchase_order_id = po.id) as item_count
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.user_id = ?
    ORDER BY po.id DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">My Wholesale Orders & Tracking</h1>
        <p class="text-muted small mb-0">Track delivery status, view procurement invoices, and verify dispatched cosmetics shipments.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>shopkeeper/catalog.php" class="btn btn-purple btn-sm rounded-3 shadow-sm text-white" style="background-color: #8b5cf6;">
            <i class="fa-solid fa-plus me-1"></i> Place New Wholesale Order
        </a>
    </div>
</div>

<!-- Orders Table -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check text-purple me-2"></i> Wholesale Orders Register (<?= count($orders) ?> Total)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Order Code</th>
                    <th>Order Date</th>
                    <th>Supplier / Manufacturer</th>
                    <th>Items</th>
                    <th>Total Amount (₹)</th>
                    <th>Expected Delivery</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fa-solid fa-bag-shopping fs-2 text-secondary mb-2 d-block"></i>
                            <div>You have not placed any wholesale orders yet.</div>
                            <a href="<?= BASE_URL ?>shopkeeper/catalog.php" class="btn btn-sm btn-outline-purple mt-2" style="color: #8b5cf6; border-color: #8b5cf6;">Open Wholesale Catalog &rarr;</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($o['po_number']) ?></td>
                            <td><?= htmlspecialchars($o['order_date']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($o['supplier_name'] ?? 'Cosmetics Manufacturer') ?></div>
                                <div class="extra-small text-muted"><?= htmlspecialchars($o['supplier_code'] ?? '') ?> &bull; <?= htmlspecialchars($o['supplier_category'] ?? 'Beauty') ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $o['item_count'] ?> item(s)</span></td>
                            <td class="fw-bold text-dark font-monospace"><?= format_currency($o['total_amount']) ?></td>
                            <td><?= htmlspecialchars($o['expected_date']) ?></td>
                            <td><?= get_status_badge($o['status']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>purchase_orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-light py-1 px-2 extra-small border">
                                    <i class="fa-solid fa-file-invoice text-secondary me-1"></i> Invoice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
