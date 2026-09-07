<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Shopkeeper & Retail Storefront Hub
 * Section 17 Implementation: Inbound Retail Stock & Received Deliveries
 */

$page_title = "Shopkeeper Storefront Hub";
require_once __DIR__ . '/../includes/header.php';
require_role(['shopkeeper', 'admin']);

$db = get_db();
$user_id = (int)$_SESSION['user_id'];
$shop_name = $_SESSION['user_shop'] ?? 'Luxe Glamour Beauty Boutique';

// ==========================================
// SECTION 17 METRICS:
// 1. Products Received
// 2. Available Products
// 3. Suppliers Count
// 4. Pending Inbound Shipments
// ==========================================
$recv_stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM product_transfers WHERE receiver_id = ? AND status = 'Received'");
$recv_stmt->execute([$user_id]);
$products_received_units = (int)$recv_stmt->fetchColumn();

$avail_stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM user_inventory WHERE user_id = ?");
$avail_stmt->execute([$user_id]);
$available_store_units = (int)$avail_stmt->fetchColumn();

$total_suppliers = (int)($db->query("SELECT COUNT(*) FROM suppliers WHERE status = 'Active'")->fetchColumn() ?: 0);

$pending_stmt = $db->prepare("SELECT COUNT(*) FROM product_transfers WHERE receiver_id = ? AND status = 'In Transit'");
$pending_stmt->execute([$user_id]);
$pending_inbound = (int)$pending_stmt->fetchColumn();

// Recent Inbound Transfers from Suppliers
$recent_transfers_stmt = $db->prepare("
    SELECT pt.*, p.product_name, p.product_code, p.category,
           u_sender.name as sender_name, u_sender.company_name as sender_company, u_sender.city as sender_city
    FROM product_transfers pt
    JOIN products p ON pt.product_id = p.id
    JOIN users u_sender ON pt.sender_id = u_sender.id
    WHERE pt.receiver_id = ?
    ORDER BY pt.id DESC LIMIT 6
");
$recent_transfers_stmt->execute([$user_id]);
$recent_transfers = $recent_transfers_stmt->fetchAll();

// Store Products Available
$store_products_stmt = $db->prepare("
    SELECT ui.quantity as in_stock, ui.batch_number, p.id, p.product_name, p.product_code, p.category, p.brand, p.standard_price
    FROM user_inventory ui
    JOIN products p ON ui.product_id = p.id
    WHERE ui.user_id = ? AND ui.quantity > 0
    ORDER BY ui.quantity DESC LIMIT 5
");
$store_products_stmt->execute([$user_id]);
$store_products = $store_products_stmt->fetchAll();
?>

<!-- Header & Actions as requested in Section 17 -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 animate-slide-up">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($shop_name) ?></h1>
            <span class="badge rounded-pill px-3 py-1 extra-small text-white" style="background-color: #8b5cf6;">Retail Boutique</span>
        </div>
        <p class="text-muted small mb-0">Cosmetics Retail Point: Inspect deliveries received from distribution hubs and track on-shelf store inventory.</p>
    </div>
    
    <!-- 3 Key Actions (Section 17: Shopkeeper does not transfer back) -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>transfers/index.php?status=In+Transit" class="btn btn-warning btn-sm rounded-3 shadow-xs fw-semibold text-dark">
            <i class="fa-solid fa-box-open me-1"></i> Receive Product (<?= $pending_inbound ?>)
        </a>
        <a href="<?= BASE_URL ?>products/index.php" class="btn btn-purple btn-sm rounded-3 shadow-sm text-white fw-semibold" style="background-color: #8b5cf6;">
            <i class="fa-solid fa-boxes-stacked me-1"></i> View Product Details
        </a>
        <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-light border btn-sm rounded-3 fw-semibold">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> View Transfer History
        </a>
    </div>
</div>

<!-- 4 Section 17 KPI Cards -->
<div class="row g-3 mb-4">
    
    <!-- 1. Products Received -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 40ms;">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Products Received</span>
                <div class="kpi-icon-pill text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $products_received_units ?>">0</div>
            <div class="kpi-subtext">Cumulative received units</div>
        </div>
    </div>

    <!-- 2. Available Products in Store -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 80ms;">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Available in Store</span>
                <div class="kpi-icon-pill bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $available_store_units ?>">0</div>
            <div class="kpi-subtext"><span class="text-success fw-semibold">pcs</span> on shelf ready to sell</div>
        </div>
    </div>

    <!-- 3. Active Suppliers -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 120ms;">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Available Suppliers</span>
                <div class="kpi-icon-pill bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-building"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $total_suppliers ?>">0</div>
            <div class="kpi-subtext">Cosmetics supplier hubs</div>
        </div>
    </div>

    <!-- 4. Pending Shipments -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 160ms;">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Pending Shipments</span>
                <div class="kpi-icon-pill text-warning" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $pending_inbound ?>">0</div>
            <div class="kpi-subtext text-warning fw-semibold">Awaiting your receipt</div>
        </div>
    </div>

</div>

<!-- Recent Deliveries from Suppliers Table -->
<div class="row g-4 mb-4">
    
    <!-- Left: Inbound Transfers from Suppliers -->
    <div class="col-12 col-lg-8">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="section-title"><i class="fa-solid fa-truck-fast text-purple" style="color: #8b5cf6;"></i> Inbound Deliveries from Distribution Hubs</h6>
                <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-sm btn-link text-decoration-none extra-small">View All &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Transfer Ref</th>
                            <th>Product Name</th>
                            <th>Source Supplier</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transfers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No deliveries received from suppliers yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transfers as $rt): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($rt['transfer_ref']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($rt['product_name']) ?></div>
                                        <div class="extra-small text-muted font-monospace"><?= htmlspecialchars($rt['product_code']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= htmlspecialchars($rt['sender_company'] ?: $rt['sender_name']) ?></div>
                                        <div class="extra-small text-muted"><?= htmlspecialchars($rt['sender_city'] ?: 'Distribution Hub') ?></div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold"><?= number_format($rt['quantity']) ?> pcs</td>
                                    <td class="text-center">
                                        <span class="badge <?= $rt['status'] === 'Received' ? 'badge-status-received' : 'badge-status-in-transit' ?> extra-small px-2 py-1 rounded-pill">
                                            <?= htmlspecialchars($rt['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($rt['status'] === 'In Transit'): ?>
                                            <form method="POST" action="<?= BASE_URL ?>transfers/receive.php" class="d-inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="transfer_id" value="<?= (int)$rt['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm btn-action-sm">
                                                    <i class="fa-solid fa-check"></i> Accept
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>transfers/chain.php?id=<?= $rt['id'] ?>" class="btn btn-outline-secondary btn-sm btn-action-sm">
                                                <i class="fa-solid fa-timeline"></i> Chain
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Available Products in Boutique -->
    <div class="col-12 col-lg-4">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="section-title"><i class="fa-solid fa-boxes-stacked text-success"></i> Boutique On-Shelf Stock</h6>
                <a href="<?= BASE_URL ?>products/index.php" class="btn btn-sm btn-link text-decoration-none extra-small">All Products &rarr;</a>
            </div>
            <div class="card-saas-body p-3">
                <?php if (empty($store_products)): ?>
                    <div class="text-center py-4 text-muted extra-small">
                        <i class="fa-solid fa-box-open fs-3 text-secondary opacity-50 mb-2 d-block"></i>
                        No products currently in store inventory.
                        <div class="mt-2">
                            <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-sm btn-outline-primary">Check Inbound Transfers</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($store_products as $sp): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                                <div>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($sp['product_name']) ?></div>
                                    <div class="extra-small text-muted"><?= htmlspecialchars($sp['brand']) ?> &bull; <span class="font-monospace"><?= htmlspecialchars($sp['product_code']) ?></span></div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success-subtle text-success font-monospace fw-bold px-2 py-1">
                                        <?= number_format($sp['in_stock']) ?> pcs
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
