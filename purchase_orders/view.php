<?php
/**
 * Supplier Performance Analysis and Management System
 * Purchase Order Invoice & Fulfillment Tracker
 */

$page_title = "Purchase Order Details";
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

// Fetch PO with Supplier details
$stmt = $db->prepare("
    SELECT po.*, s.supplier_name, s.supplier_code, s.contact_person, s.phone, s.email, s.address, s.city, s.state, s.category as supplier_category
    FROM purchase_orders po
    INNER JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Purchase Order not found.');
    header("Location: " . BASE_URL . "purchase_orders/index.php");
    exit;
}

// Fetch line items
$items_stmt = $db->prepare("
    SELECT oi.*, p.product_code, p.product_name, p.unit, p.category as product_category, p.standard_price
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    WHERE oi.purchase_order_id = ?
");
$items_stmt->execute([$id]);
$items = $items_stmt->fetchAll();

// Fetch Deliveries linked to this PO
$deliv_stmt = $db->prepare("
    SELECT d.*, qi.defect_rate, qi.quality_score, qi.quantity_accepted, qi.quantity_defective
    FROM deliveries d
    LEFT JOIN quality_inspections qi ON d.id = qi.delivery_id
    WHERE d.purchase_order_id = ?
    ORDER BY d.delivery_date DESC
");
$deliv_stmt->execute([$id]);
$deliveries = $deliv_stmt->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">PO Details: <span class="font-monospace text-primary"><?= htmlspecialchars($order['po_number']) ?></span></h1>
        <p class="text-muted small mb-0">Issued to <strong><?= htmlspecialchars($order['supplier_name']) ?></strong> on <?= format_date($order['order_date']) ?>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>purchase_orders/index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-print me-1"></i> Print Invoice
        </button>
        <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled'): ?>
            <a href="<?= BASE_URL ?>deliveries/add.php?po_id=<?= $id ?>" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box"></i> Receive Delivery
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Invoice Card -->
<div class="card-saas mb-4">
    <div class="card-saas-body p-4">
        
        <!-- Top Invoice Meta -->
        <div class="row g-3 pb-3 border-bottom mb-4">
            <div class="col-12 col-sm-6">
                <span class="text-uppercase extra-small fw-bold text-muted d-block">Issued To Supplier</span>
                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($order['supplier_name']) ?></h5>
                <div class="small text-muted"><code class="text-primary"><?= htmlspecialchars($order['supplier_code']) ?></code> &bull; <?= htmlspecialchars($order['supplier_category']) ?></div>
                <div class="small text-muted mt-1"><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($order['address'] ?: ($order['city'] . ', ' . $order['state'])) ?></div>
                <div class="small text-muted"><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($order['email']) ?> &bull; <i class="fa-solid fa-phone ms-2 me-1"></i> <?= htmlspecialchars($order['phone']) ?></div>
            </div>
            <div class="col-12 col-sm-6 text-sm-end">
                <span class="text-uppercase extra-small fw-bold text-muted d-block">Order Summary</span>
                <h4 class="fw-bold text-primary font-monospace mb-1"><?= htmlspecialchars($order['po_number']) ?></h4>
                <div class="small text-muted">Order Date: <strong><?= format_date($order['order_date']) ?></strong></div>
                <div class="small text-muted">Expected Delivery: <strong><?= format_date($order['expected_date']) ?></strong></div>
                <div class="mt-2">Status: <?= get_status_badge($order['status']) ?></div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-custom table-bordered mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Item</th>
                        <th>Category</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Contract Unit Price</th>
                        <th class="text-end">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $idx => $it): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($it['product_name']) ?></span>
                                <code class="extra-small text-muted d-block"><?= htmlspecialchars($it['product_code']) ?></code>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($it['product_category']) ?></span></td>
                            <td class="text-center fw-semibold"><?= number_format($it['quantity']) ?> <?= htmlspecialchars($it['unit']) ?></td>
                            <td class="text-end font-monospace"><?= format_currency($it['unit_price']) ?></td>
                            <td class="text-end font-monospace fw-bold text-dark"><?= format_currency($it['total_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="5" class="text-end fw-bold">Total Purchase Order Value:</td>
                        <td class="text-end font-monospace h5 fw-bold text-primary mb-0"><?= format_currency($order['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>

<!-- Linked Delivery & Inspection Log -->
<div class="card-saas">
    <div class="card-saas-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-truck-fast text-primary me-2"></i> Delivery Fulfillment & Quality Inspections</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Delivery Date</th>
                    <th>Qty Received</th>
                    <th>Delivery Status</th>
                    <th>Delay Days</th>
                    <th>Defect Rate %</th>
                    <th>Quality Score</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deliveries)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No delivery receipts recorded for this PO yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($deliveries as $d): ?>
                        <tr>
                            <td><?= format_date($d['delivery_date']) ?></td>
                            <td class="fw-bold"><?= number_format($d['quantity_received']) ?> units</td>
                            <td>
                                <span class="badge <?= $d['delivery_status'] === 'Delayed' ? 'bg-danger' : 'bg-success' ?>">
                                    <?= htmlspecialchars($d['delivery_status']) ?>
                                </span>
                            </td>
                            <td><?= $d['delay_days'] > 0 ? "<span class='text-danger fw-bold'>+{$d['delay_days']} days</span>" : "<span class='text-success'>0 days</span>" ?></td>
                            <td><?= isset($d['defect_rate']) ? $d['defect_rate'] . '%' : 'Pending QA' ?></td>
                            <td><?= isset($d['quality_score']) ? '<strong>' . $d['quality_score'] . '%</strong>' : 'Pending QA' ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($d['remarks'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
