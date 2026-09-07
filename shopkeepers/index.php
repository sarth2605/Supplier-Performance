<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Shopkeepers & Retail Stores Directory
 */

$page_title = 'Shopkeepers & Retailers Directory';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/transfer_functions.php';

$db = get_db();
$user_data = get_current_user_data();
$user_role = strtolower($user_data['role']);

// Query registered shopkeepers with incoming stock metrics
$query = "
    SELECT u.id, u.name, u.email, u.shop_name, u.phone, u.city, u.state, u.status, u.created_at,
           COUNT(DISTINCT pt.id) as total_deliveries_received,
           COALESCE(SUM(CASE WHEN pt.status = 'Received' THEN pt.quantity ELSE 0 END), 0) as total_units_in_store
    FROM users u
    LEFT JOIN product_transfers pt ON u.id = pt.receiver_id
    WHERE u.role = 'shopkeeper'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$shopkeepers = $db->query($query)->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-store text-purple me-2" style="color: #8b5cf6;"></i>Shopkeepers & Beauty Boutiques
        </h3>
        <p class="text-muted small mb-0">Tier 3 cosmetics retail storefronts, salons, and cosmetic merchants in the supply chain.</p>
    </div>
    
    <div>
        <a href="<?= BASE_URL ?>auth/shopkeeper_register.php" class="btn btn-outline-purple btn-sm px-3 py-2 rounded-3 fw-semibold" style="color: #8b5cf6; border-color: #8b5cf6;">
            <i class="fa-solid fa-plus me-1"></i> Register New Store
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted extra-small fw-bold text-uppercase">Registered Boutiques</span>
                <span class="p-2 rounded-3 text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="fa-solid fa-store"></i></span>
            </div>
            <h4 class="fw-extrabold text-dark mb-0"><?= count($shopkeepers) ?></h4>
            <span class="extra-small text-muted">Active retail stores</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary extra-small text-uppercase">
                <tr>
                    <th class="ps-3 py-3">Store / Boutique Name</th>
                    <th>Store Owner</th>
                    <th>City & State</th>
                    <th>Contact Phone / Email</th>
                    <th class="text-center">Transfers Received</th>
                    <th>Units in Store</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($shopkeepers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-store fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold mb-1">No Registered Shopkeepers Yet</h6>
                            <p class="small text-muted mb-3">Retail shopkeepers can register through the Shopkeeper Registration portal.</p>
                            <a href="<?= BASE_URL ?>auth/shopkeeper_register.php" class="btn text-white btn-sm px-3 py-1.5 rounded-3" style="background-color: #8b5cf6;">
                                Register First Store
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($shopkeepers as $s): ?>
                        <tr>
                            <td class="ps-3 py-3">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['shop_name'] ?: $s['name']) ?></div>
                                <div class="extra-small text-muted">Registered <?= date('M Y', strtotime($s['created_at'])) ?></div>
                            </td>
                            <td class="fw-semibold text-secondary">
                                <?= htmlspecialchars($s['name']) ?>
                            </td>
                            <td>
                                <i class="fa-solid fa-location-dot text-danger me-1"></i>
                                <?= htmlspecialchars($s['city'] ?: 'Bengaluru') ?>, <?= htmlspecialchars($s['state'] ?: 'Karnataka') ?>
                            </td>
                            <td>
                                <div class="text-dark"><i class="fa-solid fa-envelope extra-small text-muted me-1"></i><?= htmlspecialchars($s['email']) ?></div>
                                <?php if (!empty($s['phone'])): ?>
                                    <div class="extra-small text-muted"><i class="fa-solid fa-phone extra-small text-muted me-1"></i><?= htmlspecialchars($s['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-monospace fw-bold">
                                <?= number_format($s['total_deliveries_received']) ?>
                            </td>
                            <td class="font-monospace fw-bold text-purple" style="color: #7c3aed;">
                                <?= number_format($s['total_units_in_store']) ?> pcs
                            </td>
                            <td>
                                <span class="badge <?= $s['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?> extra-small px-2 py-1">
                                    <?= htmlspecialchars($s['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= BASE_URL ?>transfers/index.php?search=<?= urlencode($s['name']) ?>" class="btn btn-outline-secondary btn-sm px-2.5 py-1">
                                    <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Transfers
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
