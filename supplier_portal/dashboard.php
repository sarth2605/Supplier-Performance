<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Dedicated Supplier 360 Performance & Distribution Hub Portal
 * Section 16 Implementation: Supply Chain Distribution Dashboard
 */

$page_title = "Supplier 360 Performance Portal";
require_once __DIR__ . '/../includes/header.php';
require_role(['supplier', 'admin']);

$db = get_db();
$user_id = (int)$_SESSION['user_id'];
$supplier_id = (int)($_SESSION['user_supplier_id'] ?? 1);

// Fetch supplier profile
$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    $supplier = $db->query("SELECT * FROM suppliers ORDER BY id ASC LIMIT 1")->fetch();
    $supplier_id = (int)($supplier['id'] ?? 1);
}

// Latest performance scorecard
$score_stmt = $db->prepare("SELECT * FROM performance_scores WHERE supplier_id = ? ORDER BY id DESC LIMIT 1");
$score_stmt->execute([$supplier_id]);
$score = $score_stmt->fetch();

if (!$score) {
    recalculate_supplier_period_score($supplier_id, '2026-Q1');
    $score_stmt->execute([$supplier_id]);
    $score = $score_stmt->fetch();
}
$grade_info = get_supplier_grade($score['overall_score'] ?? 95);

// ==========================================
// SECTION 16 METRICS:
// 1. Products Received
// 2. Products Available (in inventory)
// 3. Products Transferred (to shopkeepers)
// 4. Manufacturers Count
// 5. Shopkeepers Count
// 6. Pending Inbound Transfers
// ==========================================
$recv_stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM product_transfers WHERE receiver_id = ? AND status = 'Received'");
$recv_stmt->execute([$user_id]);
$products_received = (int)$recv_stmt->fetchColumn();

$avail_stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM user_inventory WHERE user_id = ?");
$avail_stmt->execute([$user_id]);
$products_available = (int)$avail_stmt->fetchColumn();

$trf_stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM product_transfers WHERE sender_id = ?");
$trf_stmt->execute([$user_id]);
$products_transferred = (int)$trf_stmt->fetchColumn();

$total_manufacturers = (int)($db->query("SELECT COUNT(*) FROM users WHERE role = 'manufacturer'")->fetchColumn() ?: 0);
$total_shopkeepers = (int)($db->query("SELECT COUNT(*) FROM users WHERE role = 'shopkeeper'")->fetchColumn() ?: 0);

$pending_stmt = $db->prepare("SELECT COUNT(*) FROM product_transfers WHERE receiver_id = ? AND status = 'In Transit'");
$pending_stmt->execute([$user_id]);
$pending_inbound_count = (int)$pending_stmt->fetchColumn();

// Recent Inbound & Outbound Transfers
$recent_transfers_stmt = $db->prepare("
    SELECT pt.*, p.product_name, p.product_code,
           u_sender.name as sender_name, u_sender.company_name as sender_company,
           u_recv.name as receiver_name, u_recv.shop_name as receiver_shop
    FROM product_transfers pt
    JOIN products p ON pt.product_id = p.id
    JOIN users u_sender ON pt.sender_id = u_sender.id
    JOIN users u_recv ON pt.receiver_id = u_recv.id
    WHERE pt.receiver_id = ? OR pt.sender_id = ?
    ORDER BY pt.id DESC LIMIT 5
");
$recent_transfers_stmt->execute([$user_id, $user_id]);
$recent_transfers = $recent_transfers_stmt->fetchAll();
?>

<!-- Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 animate-slide-up">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($supplier['supplier_name'] ?? 'Supplier Distribution Hub') ?></h1>
            <span class="badge bg-success rounded-pill px-3 py-1 extra-small">Supplier Portal</span>
            <?= get_grade_badge($score['grade'] ?? 'A+') ?>
        </div>
        <p class="text-muted small mb-0">Cosmetics Distribution Hub: Receive manufacturer formulation shipments and transfer to retail shopkeepers.</p>
    </div>
    
    <!-- 4 Key Actions as requested in Section 16 -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>transfers/index.php?status=In+Transit" class="btn btn-warning btn-sm rounded-3 shadow-xs fw-semibold text-dark">
            <i class="fa-solid fa-box-open me-1"></i> Receive Product (<?= $pending_inbound_count ?>)
        </a>
        <a href="<?= BASE_URL ?>transfers/create.php" class="btn btn-success btn-sm rounded-3 shadow-sm fw-semibold">
            <i class="fa-solid fa-paper-plane me-1"></i> Transfer Stock to Shopkeeper
        </a>
        <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-light border btn-sm rounded-3 fw-semibold">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> View Transfer History
        </a>
        <a href="<?= BASE_URL ?>suppliers/view.php?id=<?= $supplier_id ?>" class="btn btn-light border btn-sm rounded-3 fw-semibold">
            <i class="fa-solid fa-award me-1"></i> View Performance
        </a>
    </div>
</div>

<!-- 6 Section 16 KPI Cards -->
<div class="row g-3 mb-4">
    
    <!-- 1. Products Received -->
    <div class="col-6 col-md-4 col-xl-2 animate-slide-up" style="animation-delay: 30ms;">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <span class="kpi-title">Products Received</span>
            <div class="kpi-value counter-value" data-target="<?= $products_received ?>">0</div>
            <div class="kpi-subtext">From Manufacturers</div>
        </div>
    </div>

    <!-- 2. Products Available (In Stock) -->
    <div class="col-6 col-md-4 col-xl-2 animate-slide-up" style="animation-delay: 60ms;">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <span class="kpi-title">Products Available</span>
            <div class="kpi-value counter-value" data-target="<?= $products_available ?>">0</div>
            <div class="kpi-subtext">Hub Current Stock</div>
        </div>
    </div>

    <!-- 3. Products Transferred -->
    <div class="col-6 col-md-4 col-xl-2 animate-slide-up" style="animation-delay: 90ms;">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <span class="kpi-title">Products Transferred</span>
            <div class="kpi-value counter-value" data-target="<?= $products_transferred ?>">0</div>
            <div class="kpi-subtext">Sold to Shopkeepers</div>
        </div>
    </div>

    <!-- 4. Manufacturers -->
    <div class="col-6 col-md-4 col-xl-2 animate-slide-up" style="animation-delay: 120ms;">
        <div class="kpi-card" style="--kpi-color: #06b6d4;">
            <span class="kpi-title">Manufacturers</span>
            <div class="kpi-value counter-value" data-target="<?= $total_manufacturers ?>">0</div>
            <div class="kpi-subtext">Source Lab Partners</div>
        </div>
    </div>

    <!-- 5. Shopkeepers -->
    <div class="col-6 col-md-4 col-xl-2 animate-slide-up" style="animation-delay: 150ms;">
        <div class="kpi-card" style="--kpi-color: #ec4899;">
            <span class="kpi-title">Shopkeepers</span>
            <div class="kpi-value counter-value" data-target="<?= $total_shopkeepers ?>">0</div>
            <div class="kpi-subtext">Retail Boutiques</div>
        </div>
    </div>

    <!-- 6. Pending Inbound Transfers -->
    <div class="col-6 col-md-4 col-xl-2 animate-slide-up" style="animation-delay: 180ms;">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <span class="kpi-title">Pending Transfers</span>
            <div class="kpi-value counter-value" data-target="<?= $pending_inbound_count ?>">0</div>
            <div class="kpi-subtext text-warning fw-semibold">Awaiting Receipt</div>
        </div>
    </div>

</div>

<!-- Performance Scorecard Gauge & Recent Transfers Grid -->
<div class="row g-4 mb-4">
    
    <!-- Left: Scorecard 360 Summary -->
    <div class="col-12 col-lg-4">
        <div class="card-saas h-100 text-center p-4 d-flex flex-column align-items-center justify-content-center">
            <span class="text-uppercase extra-small fw-bold text-muted mb-2">Overall Evaluated Rating</span>
            <div class="circular-score-badge my-2" style="border-color: <?= $grade_info['color'] ?>; color: <?= $grade_info['color'] ?>; background: <?= $grade_info['bg'] ?>;">
                <?= $score['overall_score'] ?>%
            </div>
            <div class="mt-2">
                <?= get_grade_badge($score['grade']) ?>
                <span class="fw-bold ms-1" style="color: <?= $grade_info['color'] ?>;"><?= $grade_info['title'] ?></span>
            </div>
            <p class="extra-small text-muted mt-2 mb-3"><?= $grade_info['desc'] ?></p>

            <div class="w-100 border-top pt-3 extra-small">
                <div class="d-flex justify-content-between mb-1.5">
                    <span class="text-muted">Delivery Score:</span>
                    <strong><?= $score['delivery_score'] ?>%</strong>
                </div>
                <div class="d-flex justify-content-between mb-1.5">
                    <span class="text-muted">Quality Score:</span>
                    <strong><?= $score['quality_score'] ?>%</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Cost Efficiency:</span>
                    <strong><?= $score['cost_score'] ?>%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Recent Inbound & Outbound Transfers -->
    <div class="col-12 col-lg-8">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="section-title"><i class="fa-solid fa-arrow-right-arrow-left text-primary"></i> Hub Inbound & Outbound Transfers</h6>
                <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-sm btn-link text-decoration-none extra-small">View All &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Transfer Ref</th>
                            <th>Product</th>
                            <th>From &rarr; To</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transfers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No stock movements recorded for your distribution hub yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transfers as $trf): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($trf['transfer_ref']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($trf['product_name']) ?></div>
                                        <div class="extra-small text-muted font-monospace"><?= htmlspecialchars($trf['product_code']) ?></div>
                                    </td>
                                    <td class="extra-small">
                                        <div><strong>From:</strong> <?= htmlspecialchars($trf['sender_company'] ?: $trf['sender_name']) ?></div>
                                        <div><strong>To:</strong> <?= htmlspecialchars($trf['receiver_shop'] ?: ($trf['receiver_company'] ?: $trf['receiver_name'])) ?></div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold"><?= number_format($trf['quantity']) ?> pcs</td>
                                    <td class="text-center">
                                        <span class="badge <?= $trf['status'] === 'Received' ? 'badge-status-received' : 'badge-status-in-transit' ?> extra-small px-2 py-1 rounded-pill">
                                            <?= htmlspecialchars($trf['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>transfers/chain.php?id=<?= $trf['id'] ?>" class="btn btn-outline-secondary btn-sm btn-action-sm">
                                            <i class="fa-solid fa-timeline"></i> Chain
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
