<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Manufacturer Production & Procurement Command Center
 * Section 15 Implementation: Role-based Manufacturer Metrics & Dispatches
 */

$page_title = "Manufacturer Production Hub";
require_once __DIR__ . '/../includes/header.php';
require_role(['manufacturer', 'admin']);

$db = get_db();
$user_id = (int)$_SESSION['user_id'];
$company_name = $_SESSION['user_company'] ?? 'GlowTech Formulation Labs';

// 1. Total Products
$total_products = (int)($db->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0);

// 2. Products Transferred (Total units dispatched by this manufacturer)
$mfr_transfers_stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_transfers,
        COALESCE(SUM(quantity), 0) as total_units_transferred,
        SUM(CASE WHEN status = 'In Transit' THEN 1 ELSE 0 END) as pending_transfers
    FROM product_transfers 
    WHERE sender_id = ?
");
$mfr_transfers_stmt->execute([$user_id]);
$mfr_transfer_stats = $mfr_transfers_stmt->fetch();

$products_transferred = (int)$mfr_transfer_stats['total_units_transferred'];
$pending_transfers = (int)$mfr_transfer_stats['pending_transfers'];

// 3. Active Suppliers
$total_suppliers = (int)($db->query("SELECT COUNT(*) FROM suppliers WHERE status = 'Active'")->fetchColumn() ?: 0);

// 4. Recent Transfers Sent by Manufacturer
$recent_transfers_stmt = $db->prepare("
    SELECT pt.*, p.product_name, p.product_code, u.name as receiver_name, u.company_name as receiver_company
    FROM product_transfers pt
    JOIN products p ON pt.product_id = p.id
    JOIN users u ON pt.receiver_id = u.id
    WHERE pt.sender_id = ?
    ORDER BY pt.id DESC LIMIT 5
");
$recent_transfers_stmt->execute([$user_id]);
$recent_transfers = $recent_transfers_stmt->fetchAll();

// Top Supplier Partners
$top_suppliers = $db->query("
    SELECT s.id, s.supplier_code, s.supplier_name, s.category, ps.overall_score, ps.grade
    FROM performance_scores ps
    INNER JOIN suppliers s ON ps.supplier_id = s.id
    ORDER BY ps.overall_score DESC LIMIT 5
")->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 animate-slide-up">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h4 fw-bold text-dark mb-0"><?= htmlspecialchars($company_name) ?></h1>
            <span class="badge bg-primary rounded-pill px-3 py-1 extra-small">Manufacturer Portal</span>
        </div>
        <p class="text-muted small mb-0">Formulation R&D, Batch Production, and Direct Wholesale Stock Transfers to Suppliers.</p>
    </div>
    
    <!-- Quick Actions as requested in Section 15 -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>products/add.php" class="btn btn-outline-primary btn-sm rounded-3 shadow-xs fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> Add Product
        </a>
        <a href="<?= BASE_URL ?>transfers/create.php" class="btn btn-primary btn-sm rounded-3 shadow-sm fw-semibold">
            <i class="fa-solid fa-paper-plane me-1"></i> Transfer Product
        </a>
        <a href="<?= BASE_URL ?>suppliers/index.php" class="btn btn-light border btn-sm rounded-3 fw-semibold">
            <i class="fa-solid fa-truck-ramp-box me-1"></i> View Suppliers
        </a>
        <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-light border btn-sm rounded-3 fw-semibold">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> View Transfer History
        </a>
    </div>
</div>

<!-- 4 Key KPI Cards (Section 15) -->
<div class="row g-3 mb-4">
    
    <!-- 1. Total Products -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 40ms;">
        <div class="kpi-card" style="--kpi-color: #2563eb;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Total Products</span>
                <div class="kpi-icon-pill bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $total_products ?>">0</div>
            <div class="kpi-subtext">Catalog Formulations</div>
        </div>
    </div>

    <!-- 2. Products Transferred -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 80ms;">
        <div class="kpi-card" style="--kpi-color: #10b981;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Products Transferred</span>
                <div class="kpi-icon-pill bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $products_transferred ?>">0</div>
            <div class="kpi-subtext"><span class="text-success fw-semibold">pcs</span> dispatched to suppliers</div>
        </div>
    </div>

    <!-- 3. Pending Transfers -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 120ms;">
        <div class="kpi-card" style="--kpi-color: #f59e0b;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Pending Transfers</span>
                <div class="kpi-icon-pill text-warning" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $pending_transfers ?>">0</div>
            <div class="kpi-subtext">Awaiting supplier receipt</div>
        </div>
    </div>

    <!-- 4. Suppliers -->
    <div class="col-6 col-md-3 animate-slide-up" style="animation-delay: 160ms;">
        <div class="kpi-card" style="--kpi-color: #8b5cf6;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="kpi-title">Active Suppliers</span>
                <div class="kpi-icon-pill text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fa-solid fa-handshake"></i>
                </div>
            </div>
            <div class="kpi-value counter-value" data-target="<?= $total_suppliers ?>">0</div>
            <div class="kpi-subtext">Distribution network</div>
        </div>
    </div>

</div>

<!-- Recent Transfers Sent & Top Suppliers Grid -->
<div class="row g-4 mb-4">
    
    <!-- Left: Recent Transfers Table -->
    <div class="col-12 col-lg-7">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="section-title"><i class="fa-solid fa-arrow-right-arrow-left text-primary"></i> Recent Stock Dispatches to Suppliers</h6>
                <a href="<?= BASE_URL ?>transfers/index.php" class="btn btn-sm btn-link text-decoration-none extra-small">View All Transfers &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Transfer Ref</th>
                            <th>Product</th>
                            <th class="text-center">Quantity</th>
                            <th>Destination Supplier</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transfers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No stock transfers dispatched yet.
                                    <div class="mt-2">
                                        <a href="<?= BASE_URL ?>transfers/create.php" class="btn btn-primary btn-sm px-3">Transfer First Batch</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transfers as $rt): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($rt['transfer_ref']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($rt['product_name']) ?></div>
                                        <div class="extra-small text-muted font-monospace"><?= htmlspecialchars($rt['product_code']) ?></div>
                                    </td>
                                    <td class="text-center font-monospace fw-bold"><?= number_format($rt['quantity']) ?> pcs</td>
                                    <td><?= htmlspecialchars($rt['receiver_company'] ?: $rt['receiver_name']) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $rt['status'] === 'Received' ? 'badge-status-received' : 'badge-status-in-transit' ?> extra-small px-2 py-1 rounded-pill">
                                            <?= htmlspecialchars($rt['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>transfers/chain.php?id=<?= $rt['id'] ?>" class="btn btn-outline-secondary btn-sm btn-action-sm">
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

    <!-- Right: Top Audited Suppliers -->
    <div class="col-12 col-lg-5">
        <div class="card-saas h-100">
            <div class="card-saas-header">
                <h6 class="section-title"><i class="fa-solid fa-award text-warning"></i> Audited Supplier Partners</h6>
                <a href="<?= BASE_URL ?>suppliers/index.php" class="btn btn-sm btn-link text-decoration-none extra-small">Directory &rarr;</a>
            </div>
            <div class="card-saas-body p-3">
                <div class="list-group list-group-flush">
                    <?php foreach ($top_suppliers as $ts): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($ts['supplier_name']) ?></div>
                                <div class="extra-small text-muted"><?= htmlspecialchars($ts['category']) ?> &bull; <span class="font-monospace"><?= htmlspecialchars($ts['supplier_code']) ?></span></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold font-monospace text-success"><?= number_format($ts['overall_score'], 1) ?>%</div>
                                <?= get_grade_badge($ts['grade']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
